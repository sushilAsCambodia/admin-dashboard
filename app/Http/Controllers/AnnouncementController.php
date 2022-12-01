<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnouncementFormRequest;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcementService)
    {
    }

    public function store(AnnouncementFormRequest $request)
    {
        return $this->announcementService->store($request->all());
    }

    public function update(AnnouncementFormRequest $request, Announcement $announcement)
    {
        return $this->announcementService->update($announcement, $request->all());
    }

    public function delete(Announcement $announcement)
    {
        return $this->announcementService->delete($announcement);
    }

    public function get(Announcement $announcement)
    {
        return response()->json($announcement, 200);
    }

    public function all()
    {
        return response()->json(Announcement::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->announcementService->paginate($request);
    }

    public function latest(Request $request)
    {
        return $this->announcementService->latest($request);
    }
}
