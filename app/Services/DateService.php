<?php

namespace App\Services;

use App\Models\GamePlay;
use App\Models\Setting;
use App\Models\SpecialDraw;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DateService
{
    public function all($request): JsonResponse
    {
        try {
            $count = 7;
            $dates = [];
            $date = Carbon::now();
            $tempArr = [];
            $tempCount = 1;
            $mainDate = $date;
            $url = url('').'/public/game/';
            $hasOnGoing = false;
            for ($i = 0; $i < $count; $i++) {
                //today condition
                if ($i == 0) {

                    $settings = Setting::all();
                    $currentTime = $date->format('H:i');
                    $currentDay = $date->format('l');
                    foreach ($settings as $key => $setting) {
                        $settingValues = $setting->value;
                        if ($settingValues) {
                            foreach ($settingValues as $k => $settingValue) {
                                if ($settingValue->day == $currentDay) {

                                    $closingTime = $settingValue->closing;
                                    if ($closingTime <= $currentTime) {
                                        if($currentDay == 'Sunday')
                                            $count = 8; //increasing count for avoid losing sunday on next week
                                        goto skip;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($mainDate->format('l') == 'Saturday' || $mainDate->format('l') == 'Sunday' || $mainDate->format('l') == 'Wednesday') {
                    $temp = [];
                    $temp['id'] = $tempCount;
                    $temp['date'] = $mainDate->format('d M, Y');
                    $temp['day'] = $mainDate->format('l');
                    $temp['status'] = 'regular';

                    //all the games
                    $games = GamePlay::select(
                        'id',
                        'name',
                        'abbreviation',
                        'logo_path',
                        'logo_url As image'
                    )->whereStatus('Active')->get();
                    $temp['games'] = $games;
                    $tempArr[] = $temp;

                    //stop when end day of week to prevent showing next week days
                    if ($mainDate->format('l') == 'Sunday') break;

                    $tempCount++;
                }
                //apend special draw date to play date
                else {
                    $specailDate = $mainDate->format('Y-m-d');
                    $spacialDraw = SpecialDraw::whereDate('draw_date', $specailDate)->whereStatus('ongoing')->first();

                    if ($spacialDraw) {

                        $hasOnGoing = true;
                        $temp = [];
                        $temp['id'] = $tempCount;
                        $temp['date'] = $mainDate->format('d M, Y');
                        $temp['day'] = $mainDate->format('l');
                        $temp['status'] = $spacialDraw->status;
                        //attach game
                        $gamePlays = GamePlay::join('game_play_special_draw', 'game_play_special_draw.game_play_id', 'game_plays.id')
                                                ->where('game_play_special_draw.special_draw_id', $spacialDraw->id)
                                                ->select(
                                                    'id',
                                                    'name',
                                                    'abbreviation',
                                                    'logo_path',
                                                    'logo_url as image'
                                                )
                                                ->get();
                        $temp['games'] = $gamePlays;
                        $tempArr[] = $temp;

                        $tempCount++;
                    }
                    //append special draw by upcoming status
                    //elseif ($mainDate->format('l') == 'Friday' && $hasOnGoing == false) {
                        // $tempDate = Carbon::createFromFormat('Y-m-d',$mainDate->format('Y-m-d'));
                        // $nextWeekDate = $tempDate->addDay(3);
                        // $specialDraw = SpecialDraw::whereStatus('upcoming')->whereDate('draw_date','>=',$nextWeekDate->format('Y-m-d'))->orderBy('draw_date','asc')->first();

                        // if($specialDraw){
                        //     $temp = [];
                        //     $temp['id'] = $tempCount;
                        //     $specialDrawDate = $specialDraw->draw_date;
                        //     $temp['date'] =  $specialDrawDate->format('d M, Y');
                        //     $temp['day'] = $specialDrawDate->format('l');
                        //     $temp['status'] = $specialDraw->status;

                        //     $gamePlays = GamePlay::join('game_play_special_draw','game_play_special_draw.game_play_id','game_plays.id')
                        //                         ->where('game_play_special_draw.special_draw_id', $specialDraw->id)
                        //                         ->select(
                        //                             "id",
                        //                             "name",
                        //                             "abbreviation",
                        //                             "logo_path",
                        //                             "logo_url as image"
                        //                         )
                        //                         ->get();
                        //     // $games = $specialDraw->gamePlays;
                        //     $temp['games'] = $gamePlays;
                        //     $tempArr[] = $temp;

                        //     $tempCount++;
                        // }
                    //}
                }
                skip:
                $mainDate = $date->addDay();
            }
            $dates['status'] = 200;
            $dates['data'] = $tempArr;

            return response()->json($dates, 200);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            var_dump('Exception Message: '.$message);
            $code = $e->getCode();
            var_dump('Exception Code: '.$code);
            $string = $e->__toString();
            var_dump('Exception String: '.$string);

            return generalErrorResponse($e);
        }
    }

    public function allNew($request): JsonResponse
    {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $nextWeekEndDate = $now->endOfWeek()->format('Y-m-d');
        /* Current & Next Week Data  */
        $datas = SpecialDraw::with(['gamePlays' => function ($query) {
            $query->select('id', 'name', 'abbreviation', 'logo_path', 'logo_url As image');
        }])->where(function ($query) use ($weekStartDate, $nextWeekEndDate) {
            $query->whereBetween('draw_date', [$weekStartDate, $nextWeekEndDate]);
        })->get();

        $response = [];
        /* Current Week Data  */
        if (! empty($datas->toArray())) {
            foreach ($datas as $data) {
                $date = Carbon::parse($data->draw_date)->format('d-m-Y');
                if ($now >= $date) {
                    $row['id'] = $data->id;
                    $row['draw_date'] = $date;
                    $row['day'] = Carbon::parse($data->draw_date)->format('l');
                    $row['status'] = $data->status;
                    if ($row['day'] == 'Saturday' || $row['day'] == 'Sunday' || $row['day'] == 'Wednesday') {
                        $games = GamePlay::select('id', 'name', 'abbreviation', 'logo_path', 'logo_url As image')->whereStatus('Active')->get();
                        $row['games'] = $games;
                    } else {
                        $row['games'] = $data->gamePlays;
                    }
                    $response[] = $row;
                }
            }
        } else {
            /* Next  Week First  Data  */
            $weekStartDate = $now->startOfWeek()->next('Sunday')->format('Y-m-d');
            $nextWeekEndDate = $now->endOfWeek()->next('Saturday')->format('Y-m-d');
            $data = SpecialDraw::with(['gamePlays' => function ($query) {
                $query->select('id', 'name', 'abbreviation', 'logo_path', 'logo_url As image');
            }])->where(function ($query) use ($weekStartDate, $nextWeekEndDate) {
                $query->whereBetween('draw_date', [$weekStartDate, $nextWeekEndDate]);
            })->first();

            $row['id'] = $data->id;
            $row['draw_date'] = Carbon::parse($data->draw_date)->format('d-m-Y');
            $row['day'] = Carbon::parse($data->draw_date)->format('l');
            $row['status'] = $data->status;
            if ($row['day'] == 'Saturday' || $row['day'] == 'Sunday' || $row['day'] == 'Wednesday') {
                $games = GamePlay::select('id', 'name', 'abbreviation', 'logo_path', 'logo_url As image')->whereStatus('Active')->get();
                $row['games'] = $games;
            } else {
                $row['games'] = $data->gamePlays;
            }
            $response[] = $row;
        }

        return response()->json($response, 200);
    }
}
