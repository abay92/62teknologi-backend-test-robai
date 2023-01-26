<?php

if (!function_exists('uploadFileBase64')) {
    function uploadFileBase64($folderPath, $file_data, $data_old = '')
    {
        $base64Image = explode(";base64,", $file_data);
        $explodeImage = explode("image/", $base64Image[0]);
        $imageType = $explodeImage[1];
        $image_base64 = base64_decode($base64Image[1]);
        $file = $folderPath . uniqid() . '.' . $imageType;

        try {
            $storage = config('filesystems.default');
            Illuminate\Support\Facades\Storage::disk($storage)->put($file, $image_base64);

            $exists = Illuminate\Support\Facades\Storage::disk($storage)->exists($data_old);

            if ($exists) {
                Illuminate\Support\Facades\Storage::disk($storage)->delete($data_old);
            }

            return $file;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('deleteFile')) {
    function deleteFile($file)
    {
        $storage = config('filesystems.default');
        $exists = Illuminate\Support\Facades\Storage::disk($storage)->exists($file);

        if ($exists) {
            Illuminate\Support\Facades\Storage::disk($storage)->delete($file);
        }

        return true;
    }
}

if (!function_exists('getImage')) {
    function getImage($file)
    {
        $storage = config('filesystems.default');
        $exists = Illuminate\Support\Facades\Storage::disk($storage)->exists($file);

        if ($exists) {
            return Illuminate\Support\Facades\Storage::disk($storage)->url($file);
        }

        return null;
    }
}
