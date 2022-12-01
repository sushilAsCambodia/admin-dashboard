<?php

namespace App\Jobs;

use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = file_get_contents('https://check4dresults.com/wp-content/themes/4d/result.php');
        $resultArray = json_decode($response);
        foreach ($resultArray as $key => $result) {
            if ($key == 'M' || $key == 'D' || $key == 'T') {
                if ($key == 'M') {
                    $game_play_id = 1;
                } elseif ($key == 'D') {
                    $game_play_id = 2;
                } else {
                    $game_play_id = 3;
                }
                $special = [];
                $special[] = $result->S1;
                $special[] = $result->S2;
                $special[] = $result->S3;
                $special[] = $result->S4;
                $special[] = $result->S5;
                $special[] = $result->S6;
                $special[] = $result->S7;
                $special[] = $result->S8;
                $special[] = $result->S9;
                $special[] = $result->S10;
                $special[] = @$result->S11;
                $special[] = @$result->S12;
                $special[] = @$result->S13;
                foreach ($special as $key => $value) {
                    if (!is_numeric($value)) {
                        unset($special[$key]);
                    }
                }
                $new_special = array_values($special);
                $part = explode(' ', $result->DD);
                $fetching_date = date('Y-m-d', strtotime($part['0']));
                $records = Result::where('reference_number', '=', $result->DN)->where('game_play_id', '=', $game_play_id)->first();
                if ($records === null) {
                    $data['game_play_id'] = $game_play_id;
                    $data['fetching_date'] = $fetching_date;
                    $data['result_date'] = $result->DD;
                    $data['reference_number'] = $result->DN;
                    $data['prize1'] = $result->P1;
                    $data['prize2'] = $result->P2;
                    $data['prize3'] = $result->P3;
                    $data['special1'] = $new_special[0];
                    $data['special2'] = $new_special[1];
                    $data['special3'] = $new_special[2];
                    $data['special4'] = $new_special[3];
                    $data['special5'] = $new_special[4];
                    $data['special6'] = $new_special[5];
                    $data['special7'] = $new_special[6];
                    $data['special8'] = $new_special[7];
                    $data['special9'] = $new_special[8];
                    $data['special10'] = $new_special[9];
                    $data['consolation1'] = $result->C1;
                    $data['consolation2'] = $result->C2;
                    $data['consolation3'] = $result->C3;
                    $data['consolation4'] = $result->C4;
                    $data['consolation5'] = $result->C5;
                    $data['consolation6'] = $result->C6;
                    $data['consolation7'] = $result->C7;
                    $data['consolation8'] = $result->C8;
                    $data['consolation9'] = $result->C9;
                    $data['consolation10'] = $result->C10;
                    $data['confirm'] = 'No';
                    Result::create($data);
                }
            }
        }

        return response()->json([
            'success' => true,
            'messages' => ['Result created successfully'],
        ], 200);
    }
}
