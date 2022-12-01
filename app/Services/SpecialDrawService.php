<?php

namespace App\Services;

use App\Models\SpecialDraw;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SpecialDrawService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'draw_date';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new SpecialDraw())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->draw_date, function ($query) use ($request) {
                $query->whereDate('draw_date', Carbon::parse($request->draw_date)->format('Y-m-d'));
            });

            $results = $query->with('gamePlays')->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($data) {
                $specialDraw = SpecialDraw::create($data);
                $specialDraw->gamePlays()->attach($data['game_plays']);
            });

            return response()->json([
                'messages' => ['Special Draw created successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($specialDraw, array $data): JsonResponse
    {
        try {
            //check special draw status
            if ($specialDraw->status == 'drawed') {
                return response()->json([
                    'messages' => ['You cannot update, special draw status is drawed'],
                ], 403);
            }

            DB::transaction(function () use ($specialDraw, $data) {
                $specialDraw->update($data);
                $specialDraw->gamePlays()->sync($data['game_plays']);
            });

            return response()->json([
                'messages' => ['Special Draw updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($specialdraw): JsonResponse
    {
        try {
            if ($specialdraw->status == 'drawed' || $specialdraw->status == 'ongoing') {
                return response()->json([
                    'messages' => ['You cannot delete, special draw status is drawed'],
                ], 403);
            }
            DB::table('special_draws')->where('id', '=', $specialdraw['id'])->delete();
            DB::table('game_play_special_draw')->where('special_draw_id', '=', $specialdraw['id'])->delete();


            return response()->json([
                'messages' => ['Special Draw deleted successfully', $specialdraw],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function latest(): JsonResponse
    {
        try {
            $specialDrawData = SpecialDraw::whereStatus('ongoing')->where('draw_date', '>=', Carbon::today())->orderBy('draw_date', 'asc')->first();
            if (! $specialDrawData) {
                $specialDrawData = SpecialDraw::whereStatus('upcoming')->where('draw_date', '>=', Carbon::today())->orderBy('draw_date', 'asc')->first();
            }

            if (! empty($specialDrawData)) {
                $specialDraw['draw_date_formatted'] = Carbon::parse($specialDrawData->draw_date)->isoformat('Do MMMM (dddd)');
                $specialDraw['draw_day_formatted'] = Carbon::parse($specialDrawData->draw_date)->isoformat('dddd');
                $specialDraw['draw_month_formatted'] = Carbon::parse($specialDrawData->draw_date)->isoformat('MMMM');
                $specialDraw['draw_date_only_formatted'] = Carbon::parse($specialDrawData->draw_date)->isoformat('Do');
                $specialDraw['draw_date'] = Carbon::parse($specialDrawData->draw_date)->format('Y-m-d');
                $specialDraw['id'] = $specialDrawData->id;
                $specialDraw['game_play'] = $specialDrawData->gamePlays;

                return response()->json([
                    'data' => $specialDraw,
                    'messages' => ['Latest Special Draw fetched successfully'],
                ], 200);
            } else {
                return response()->json([
                    'messages' => ['No Special Draw'],
                ], 200);
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
