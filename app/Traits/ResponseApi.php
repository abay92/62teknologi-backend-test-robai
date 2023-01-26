<?php

namespace App\Traits;

trait ResponseApi
{
    protected static $response = [
        'status'  => true,
        'message' => null,
        'data'    => [],
    ];

    public function resSuccess($data = [], $message = null)
    {
        self::$response['message'] = $message;
        self::$response['data']    = $data;

        return response()->json(self::$response, 200);
    }

    public function resError($message = null, $code = 400, $data = [])
    {
        self::$response['status']  = false;
        self::$response['message'] = $message;
        self::$response['data']    = $data;

        return response()->json(self::$response, $code);
    }

    public function resValidation($data = [])
    {
        self::$response['status'] = false;
        self::$response['data']   = $data;

        return response()->json(self::$response, 422);
    }
}
