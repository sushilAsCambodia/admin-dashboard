<?php

namespace App\Http\Controllers;

use App\Services\FileUploadService;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function __construct(private FileUploadService $uploadService)
    {
    }

    public function upload(Request $request, $folder)
    {
        if ($request->file('file')) {
            return $this->uploadService->upload($request->file('file'), $folder);
        }

        return response()->json(['messages' => ['Please select file']], 400);
    }
}
