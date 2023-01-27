<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\business;
use App\Models\Category;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class BusinessController extends Controller
{
    public const PATH = '/image-business/';

    public function index(Request $request)
    {
        $getLatLong = $this->getLatLong();
        $userLatitude = $getLatLong['latitude'];
        $userLongitude = $getLatLong['longitude'];

        $distance = '(
            6371000 * Acos (Cos (Radians(' . $userLatitude . ')) * Cos(Radians(latitude)) * Cos(Radians(longitude) - Radians(' . $userLongitude . ')) + Sin (Radians(' . $userLatitude . ') * Sin(Radians(latitude))))
        )';

        $select = [
            'businesses.id' => 'id',
            'businesses.alias' => 'alias',
            'businesses.name' => 'name',
            'businesses.image' => 'image',
            'businesses.phone' => 'phone',
            'businesses.price' => 'price',
            'businesses.url' => 'url',
            'business_locations.address1' => 'address1',
            'business_locations.address2' => 'address2',
            'business_locations.address3' => 'address3',
            'business_locations.city' => 'city',
            'business_locations.zip_code' => 'zip_code',
            'business_locations.country' => 'country',
            'business_locations.state' => 'state',
            $distance => 'distance',
            "JSON_ARRAYAGG(JSON_OBJECT('title', categories.title, 'alias', categories.alias))" => 'category'
        ];

        $searchField = [
            'businesses.name',
            'businesses.alias'
        ];

        $validator = $this->validatorFilter($request, $select);

        if ($validator->fails()) :
            return $this->resValidation($validator->errors());
        endif;

        $join = [
            'business_locations' => [
                'type' => 'leftJoin',
                'pk' => 'businesses.id',
                'operation' => '=',
                'fk' => 'business_locations.business_id'
            ],
            'business_categories' => [
                'type' => 'leftJoin',
                'pk' => 'businesses.id',
                'operation' => '=',
                'fk' => 'business_categories.business_id'
            ],
            'categories' => [
                'type' => 'leftJoin',
                'pk' => 'categories.id',
                'operation' => '=',
                'fk' => 'business_categories.category_id'
            ],
        ];

        $query = Business::onQuery($request, [
            'select'      => $select,
            'searchField' => $searchField,
            'join'        => $join
        ]);

        if ($request->latitude && $request->longitude) {
            $query = $query->whereBetween('latitude', [$request->latitude, $request->latitude])
                ->whereBetween('longitude', [$request->longitude, $request->longitude]);
        }

        if ($request->radius && $getLatLong['is_lat_long']) {
            $query = $query->where($distance, '<', $request->radius);
        }

        if ($request->term) {
            $searchTerm = $request->term;
            $searchable = [
                'businesses.name',
                'categories.alias',
                'categories.title'
            ];

            $query = $query->where(function ($query) use ($searchTerm, $searchable) {
                foreach ($searchable as $column) :
                    $query->orWhere($column, 'like', '%' . $searchTerm . '%');
                endforeach;
            });
        }

        if ($request->categories) {
            $searchCat = explode(',', strtolower($request->categories));
            $searchCat = array_map('trim', $searchCat);
            $categoryList = Category::whereIn(DB::raw('lower(title)'), $searchCat)
                ->orWhereIn(DB::raw('lower(title)'), $searchCat)->pluck('id')->toArray();

            if ($categoryList) {
                $query = $query->whereIn('categories.id', $categoryList)
                    ->orWhereIn('categories.parent_id', $categoryList);
            }
        }

        if ($request->location) {
            $searchTerm = $request->location;
            $searchable = [
                'business_locations.address1',
                'business_locations.address2',
                'business_locations.address3',
                'business_locations.country',
                'business_locations.state',
                'business_locations.city'
            ];

            $query = $query->where(function ($query) use ($searchTerm, $searchable) {
                foreach ($searchable as $column) :
                    $query->orWhere($column, 'like', '%' . $searchTerm . '%');
                endforeach;
            });
        }

        if ($request->price) {
            $query = $query->where('businesses.price', $request->price);
        }

        $perPage = (int) $request->limit;
        $perPage = $perPage ? $perPage : $this->default_limit;
        $query = $query->groupBy('businesses.id')->paginate($perPage);
        $collection = BusinessResource::collection($query)->response()->getData(true);
        $collection['region'] = [
            'center' => [
                'longitude' => $userLatitude,
                'latitude' => $userLongitude
            ]
        ];

        return $this->resSuccess($collection, __('message.success'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) :
            return $this->resValidation($validator->errors());
        endif;

        $payload = $request->only('name', 'phone', 'price', 'url');
        $payload['image'] = uploadFileBase64(self::PATH, $request->image);

        $model = DB::transaction(function () use ($request, $payload) {
            $model = business::create($payload);

            $model->location()->create($request->location);

            $model->categories()->attach($request->categories);

            foreach ($request->transactions as $transaction) {
                $model->transactions()->create([
                    'name' => $transaction
                ]);
            }

            $photos = [];
            foreach ($request->photos as $photo) {
                $photos[] = [
                    'image' => uploadFileBase64(self::PATH, $photo)
                ];
            }
            $model->photos()->createMany($photos);

            return $model;
        });

        return $this->resSuccess($model->only('id', 'name'), __('message.business.created', ['key' => $model->name]));
    }

    public function show($id)
    {
        $model = business::findOrFail($id);

        return $this->resSuccess(new BusinessResource($model, true), __('message.success'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) :
            return $this->resValidation($validator->errors());
        endif;

        $model = business::findOrFail($id);

        $payload = $request->only('name', 'phone', 'price');
        $payload['image'] = uploadFileBase64(self::PATH, $request->image, $model->image);

        DB::transaction(function () use ($request, $model, $payload) {
            $model->update($payload);

            $model->location()->update($request->location);

            $model->categories()->detach();
            $model->categories()->attach($request->categories);

            if ($request->is_update_photos) {
                $model->photos()->map(function ($item) {
                    deleteFile($item->name);
                });
                $model->photos()->delete();

                $photos = [];
                foreach ($request->photos as $photo) {
                    $photos[] = [
                        'image' => uploadFileBase64(self::PATH, $photo)
                    ];
                }
                $model->photos()->createMany($photos);
            }

            return $model;
        });

        return $this->resSuccess($model->only('id', 'name'), __('message.business.updated', ['key' => $model->name]));
    }

    public function destroy($id)
    {
        $model = Business::findOrFail($id);

        DB::transaction(function () use ($model) {
            return $model->delete();
        });

        return $this->resSuccess($model, __('message.business.deleted', ['key' => $model->name]));
    }

    public function storeRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|string|exists:businesses,id',
            'comment' => 'required|string|max:255',
            'star_rating' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) :
            return $this->resValidation($validator->errors());
        endif;

        $model = business::findOrFail($request->business_id);
        $payload = $request->only('comment', 'star_rating');

        DB::transaction(function () use ($model, $payload) {
            $model->reviews()->create($payload);
        });

        return $this->resSuccess($model->only('id', 'name'), __('message.business.rating', ['key' => $model->name]));

    }

    public function getLatLong()
    {
        // $ip = request()->ip();
        // $location = Location::get($ip);

        $userLatitude = 0;
        $userLongitude = 0;
        $is_lat_long = false;

        // if ($location) {
        //     $is_lat_long = true;
        //     $userLatitude = $location->latitude;
        //     $userLongitude = $location->longitude;
        // }

        return [
            'is_lat_long' => $is_lat_long,
            'latitude' => $userLatitude,
            'longitude' => $userLongitude,
        ];
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string',
            'image' => 'required|string|base64image',
            'phone' => 'required|string',
            'url' => 'required|string|url',
            'price' => 'required|string|in:1,2,3,4',
            'categories' => 'required|array|distinct',
            'categories.*' => 'required|string|exists:categories,id',
            'transactions' => 'required|array|distinct',
            'transactions.*' => 'required|string|in:delivery,pickup',
            'location' => 'required|array',
            'location.address1' => 'required_without:address2|required_without:address3|string',
            'location.address2' => 'required_without:address1|required_without:address3|string',
            'location.address3' => 'required_without:address1|required_without:address2|string',
            'location.country' => 'required|string|exists:countries,code',
            'location.state' => 'required|string',
            'location.city' => 'required|string',
            'location.zip_code' => 'required|string',
            'location.latitude' => 'required|latitude',
            'location.longitude' => 'required|longitude',
            'is_update_photos' => 'nullable|boolean',
            'photos' => 'nullable|array|distinct',
            'photos.*' => 'nullable|string|base64image',
        ];

        return $rules;
    }
}
