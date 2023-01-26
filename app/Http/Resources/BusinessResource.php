<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public $full = false;

    public function __construct($resource, $full = false)
    {
        $this->resource = $resource;
        $this->full = $full;
    }

    public function toArray($request)
    {
        $location = $this->location;

        $display_address = [
            $location->address1,
            $location->address2,
            $location->address3,
            "{$location->city}, {$location->state} {$location->zip_code}"
        ];

        $location->display_address = array_filter($display_address);

        switch ($this->price) {
            case '1':
                $price = '$';
                break;
            case '2':
                $price = '$$';
                break;
            case '3':
                $price = '$$$';
                break;
            case '4':
                $price = '$$$$';
                break;

            default:
                $price = null;
                break;
        }

        $data = [
            'id' => $this->id,
            'alias' => $this->alias,
            'name' => $this->name,
            'price' => $price,
            'phone' => $this->phone,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'transactions' => $this->transactions->pluck('name'),
            'categories' => $this->category ? json_decode($this->category) : $this->categories->transform(function ($item) {
                return [
                    'alias' => $item->alias,
                    'title' => $item->title
                ];
            }),
            'coordinates' => [
                'latitude' => $this->location->latitude,
                'longitude' => $this->location->longitude
            ],
            'location' => $location->only([
                'address1', 'address2', 'address3', 'city', 'zip_code', 'country', 'state', 'display_address'
            ]),
            'review_count' => $this->reviews->count(),
            'rating' => $this->reviews->avg('star_rating'),
        ];

        if ($this->full) {
            $data = array_merge($data, [
                'photos' => $this->photos->pluck('image_url'),
                'hours' => []
            ]);
        } else {
            $data['distance'] = $this->distance;
        }

        return $data;
    }
}
