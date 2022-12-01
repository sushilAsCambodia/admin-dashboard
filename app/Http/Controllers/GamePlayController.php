<?php

namespace App\Http\Controllers;

use App\Http\Requests\GamePlayFormRequest;
use App\Models\GamePlay;
use App\Services\GamePlayService;
use Illuminate\Http\Request;

class GamePlayController extends Controller
{
    public function __construct(private GamePlayService $gamePlayService)
    {
    }

    public function store(GamePlayFormRequest $request)
    {
        return $this->gamePlayService->store($request->all());
    }

    public function update(GamePlayFormRequest $request, GamePlay $gamePlay)
    {
        return $this->gamePlayService->update($gamePlay, $request->all());
    }

    public function delete(GamePlay $gamePlay)
    {
        return $this->gamePlayService->delete($gamePlay);
    }

    public function get(GamePlay $gamePlay)
    {
        return response()->json($gamePlay, 200);
    }

    public function all()
    {
        return response()->json(GamePlay::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->gamePlayService->paginate($request);
    }
}
