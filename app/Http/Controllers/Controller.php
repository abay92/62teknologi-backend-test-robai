<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseApi;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use ResponseApi;

    public $default_limit  = 10;

    public function validatorFilter($request, $data)
    {
        return Validator::make($request->all(), [
            'term'        => 'nullable|string',
            'sort_column' => 'nullable|string|in:' . join(',', $data),
            'sort_type'   => 'nullable|in:asc,desc',
            'offset'      => 'nullable|integer',
            'limit'       => 'nullable|integer',
            'filter'      => 'nullable|array',
            'filter.*'    => 'required_with:filter|string',
            'location'    => 'required_without:latitude|required_without:longitude',
            'latitude'    => 'nullable|required_with:longitude|latitude',
            'longitude'   => 'nullable|required_with:latitude|longitude',
            'radius'      => 'nullable|min:0|max:40000'
        ])->after(function ($validator) use ($request, $data) {
            $perPage = $request->limit;
            if ($perPage != 'all' && ($perPage != null && (int) $perPage == 0)) {
                $validator->errors()->add('limit', __('message.must_be_integer'));
            }

            $filter = $request->input('filter', []);
            foreach ($filter as $key => $value) :
                if (!in_array($key, $data)) {
                    $validator->errors()->add('limit', __('message.filter_invalid', [
                        'key' => $key
                    ]));
                }
            endforeach;
        });
    }
}
