<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function upload($file, $folder): JsonResponse
    {
        try {
            $path = $file->store("public/$folder");

            return response()->json([
                'url' => asset(Storage::url($path)),
                'path' => $path,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($path): JsonResponse
    {
        try {
            // $path = $file->store("public/$folder");
            // return response()->json([
            //     'url'  => asset(Storage::url($path)),
            //     'path' => $path,
            // ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
