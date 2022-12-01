<?php

namespace App\Services;

use App\Models\GamePlay;
use App\Models\Merchant;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketSlave;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    private $result = [];

    public function store($request): JsonResponse
    {
        $memberId = $request->member_id;
        $merchantId = $request->merchant_id;

        try {
            DB::beginTransaction();

            $gameDate = $request->game_dates;
            $options = $request->options;
            $LastNumber = DB::table('tickets')->max('id') + 1;
            $append = zeroappend($LastNumber);
            $ticketNo = 'T'.$append.$LastNumber;

            //check if balance in wallet enough
            $userWallet = Wallet::whereMemberId($memberId)->whereMerchantId($merchantId)->first();
            $totalAmount = 0;
            foreach ($gameDate as $key => $gD) {
                // code...
                $options = @$gD['options'];
                foreach ($options as $key => $value) {
                    $totalAmount += $value['amount'];
                }
            }
            if ($userWallet) {
                if ($userWallet->amount < $totalAmount) {
                    $message = ['insuffient_balance'];

                    return response()->json([
                        'message_id' => 406,
                        'messages' => $message,
                    ], 200);
                }
            } else {
                return response()->json([
                    'messages' => ['not_wallet'],
                ], 200);
            }
            $result = [];
            $total = 0;
            $acp_bet = 0;
            $rebat = 0;
            $netAmount = 0;
            $merchant = Merchant::find($merchantId);

            //check mercant status
            if ($merchant->status != 'Active') {
                $message = ['merchant has disabled']; // Merchant is temporary deactivated. Please contact customer service.

                return response()->json([
                    'message_id' => 406,
                    'messages' => $message,
                ], 200);
            }
            //check ticket status
            if ($merchant->market->status != 'Active') {
                $message = ['market has disabled']; // market has disabled => Market is closed. No bet is allowed(this message will print in front-end)

                return response()->json([
                    'message_id' => 406,
                    'messages' => $message,
                ], 200);
            }
            foreach ($gameDate as $k => $v) {
                $bettingDay = Carbon::createFromFormat('d M, Y',$v['date'])->format('l');
                $settings = Setting::all();
                $currentTime = date('H:i');
                foreach ($settings as $key => $setting) {
                    $settingValues = $setting->value;
                    if ($settingValues) {
                        foreach ($settingValues as $k => $settingValue) {
                            if ($settingValue->day == $bettingDay) {

                                $closingTime = $settingValue->closing;

                                if ($closingTime <= $currentTime) {

                                    $message = ['market has disabled']; // Closing time. bet is not allowed
                                    return response()->json([
                                        'message_id' => 406,
                                        'messages' => $message,
                                    ], 200);
                                }
                            }
                        }
                    }
                }
                $options = @$v['options'];
                foreach ($options as $key => $value) {
                    $LastNumber = DB::table('tickets')->max('id') + 1;
                    $append = zeroappend($LastNumber);
                    $ticketNo = 'T'.$append.$LastNumber;
                    $this->result = [];
                    $ticketData = [];
                    $ticketData['member_id'] = $memberId;
                    $ticketData['merchant_id'] = $merchantId;
                    $ticketData['ticket_no'] = $ticketNo;
                    $ticketData['bet_number'] = @$value['number'];

                    $bigBet = @$value['big_bet'];

                    if (gettype($bigBet) == 'string') {
                        $bigBet = ltrim($bigBet, 0);
                    }

                    $smallBet = @$value['small_bet'];
                    if (gettype($smallBet) == 'string') {
                        $smallBet = ltrim($smallBet, 0);
                    }
                    $boxType = '';
                    if (@$value['box'] == 'on') {
                        $boxType = '0';
                    } elseif (@$value['ibox'] == 'on') {
                        $boxType = '1';
                    } elseif (@$value['reverse'] == 'on') {
                        $boxType = '2';
                    } else {
                        $boxType = '3'; // nothing
                    }
                    $ticketData['bet_type'] = $boxType;
                    $ticketData['total_amount'] = @$value['amount'];
                    $ticketDate = Carbon::createFromFormat('d M, Y', $v['date'])->format('Y-m-d');
                    $ticketData['betting_date'] = $ticketDate;
                    $ticketData['draw_date'] = $ticketDate;

                    $ticketData['ticket_status'] = 'UNSETTLED';
                    $ticketStatus = 'IN_PROGRESS';
                    $closingTime = '18:55';
                    // check if today
                    if (date('Y-m-d') == $ticketDate) {
                        if (date('H:i') >= $closingTime) {
                            $ticketStatus = 'REJECTED';
                            $ticketData['message'] = 'Exceeded closing time';
                        }
                    }
                    $ticketData['progress_status'] = $ticketStatus;
                    $ticket = Ticket::create($ticketData);

                    foreach ($v['games'] as $key => $gameId) {
                        // code...
                        $oddSetting = $merchant->market->oddSettings->where('game_play_id', $gameId)->first();
                        $gameAbbreviation = GamePlay::find($gameId)->abbreviation;
                        $slaveData = [];
                        $slaveData['ticket_id'] = $ticket->id;
                        $slaveData['merchant_id'] = $merchantId;
                        $slaveData['game_play_id'] = $gameId;
                        $slaveData['status'] = 'in-process';
                        $slaveData['betting_date'] = $ticketDate;

                        if (strlen(@$value['number']) > 3) {
                            if (@$value['box'] == 'on' || @$value['ibox'] == 'on') {
                                $str = @$value['number'];
                                $n = strlen($str);
                                $this->permute($str, 0, $n - 1);

                                $temp = [];
                                for ($j = 0; $j < count($this->result); $j++) {
                                    if (! in_array($this->result[$j], $temp)) {
                                        array_push($temp, $this->result[$j]);
                                    }
                                }

                                $lotteryAmt = (float) $bigBet + (float) $smallBet;
                                if (@$value['box'] == 'on') {
                                    $slaveData['bet_amount'] = $lotteryAmt;
                                    $bigbet_slav = $bigBet;
                                    $smallbet_slav = $smallBet;
                                } else {
                                    $slaveData['bet_amount'] = @$lotteryAmt / count($temp);
                                    $bigbet_slav = (float) $bigBet / count($temp);
                                    $smallbet_slav = (float) $smallBet / count($temp);
                                }

                                foreach ($temp as $key => $row) {
                                    $slaveData['lottery_number'] = $row;

                                    if (@$bigbet_slav || @$smallbet_slav) {
                                        $slaveData['big_bet_amount'] = $bigbet_slav;
                                        $slaveData['small_bet_amount'] = $smallbet_slav;

                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                        $slaveData['game_type'] = '4D';

                                        $totalBig = TicketSlave::where('lottery_number', $row)->where('game_play_id', $gameId)->sum('big_bet_amount');
                                        $tempBig = (float) $totalBig + (float) $bigbet_slav;
                                        $bigBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_big;

                                        $tempbR = 'ACCEPTED';
                                        if (((float) $totalBig + (float) $bigbet_slav) > $bigBetLimit) {
                                            if ($totalBig < $bigBetLimit) {
                                                $tempbR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;

                                                $result[$key]['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['big'] = 'Big';
                                            } else {
                                                $tempbR = 'REJECTED';
                                                $slaveData['big_bet_amount'] = 0;

                                                $result[$key]['big_bet_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['big'] = 'Big';
                                            }
                                        } else {
                                            if ($tempBig < $bigBetLimit) {
                                                $tempbR = 'ACCEPTED';
                                            } else {
                                                if ($tempBig > $bigBetLimit) {
                                                    $tempbR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                    $result[$key]['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['big'] = 'Big';
                                                }
                                            }
                                        }

                                        $totalSmall = TicketSlave::where('lottery_number', $row)->where('game_play_id', $gameId)->sum('small_bet_amount');
                                        $tempSmall = (float) $totalSmall + (float) $smallbet_slav;
                                        $smallBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_small;

                                        $tempbS = 'ACCEPTED';
                                        if (((float) $totalSmall + (float) $smallbet_slav) > $smallBetLimit) {
                                            if ($totalSmall < $smallBetLimit) {
                                                $tempbS = 'PARTIALLY_ACCEPTED';
                                                $slaveData['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                $result[$key]['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['small'] = 'Small';
                                                $result[$key]['game'] = $gameAbbreviation;
                                            } else {
                                                $tempbS = 'REJECTED';
                                                $tempbS = 'REJECTED';
                                                $slaveData['small_bet_amount'] = 0;
                                                $result[$key]['small_bet_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['small'] = 'Small';
                                                $result[$key]['game'] = $gameAbbreviation;
                                            }
                                        } else {
                                            if ($tempSmall < $smallBetLimit) {
                                                $tempbS = 'ACCEPTED';
                                            } else {
                                                if ($totalSmall > $smallBetLimit) {
                                                    $tempbS = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                    $result[$key]['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['small'] = 'Small';
                                                    $result[$key]['game'] = $gameAbbreviation;
                                                }
                                            }
                                        }
                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempbR == 'ACCEPTED' && $tempbS == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            $result[$key]['number'] = @$value['number'];
                                            $result[$key]['date'] = $ticketDate;
                                            $result[$key]['three_c'] = '3C';
                                            $result[$key]['three_a'] = '3A';
                                            $result[$key]['three_c_amount'] = 0;
                                            $result[$key]['three_a_amount'] = 0;
                                            $result[$key]['big_bet_amount'] = $slaveData['big_bet_amount'];
                                            $result[$key]['small_bet_amount'] = $slaveData['small_bet_amount'];
                                            $result[$key]['game'] = $gameAbbreviation;
                                            if ($tempbR == 'REJECTED' && $tempbS == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $slaveData['bet_amount'] = (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];
                                        $slaveData['bet_net_amount'] = (float) $slaveData['bet_amount'] - (float) ($slaveData['bet_amount'] * $oddSetting->rebate_4d / 100);
                                        $slaveData['rebate_amount'] = (float) $slaveData['bet_amount'] - (float) $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];
                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                        $total = (float) $total + (float) $bigbet_slav + (float) $smallbet_slav;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];

                                        if ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] == 0) {
                                            $slaveData['bet_size'] = 'Big';
                                        } else {
                                            $slaveData['bet_size'] = 'Small';
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;
                                        TicketSlave::create($slaveData);
                                    }

                                    //save 3d slave
                                    if (@$value['3a_bet'] || @$value['3c_bet']) {
                                        $slaveData['three_a_amount'] = @$value['3a_bet'];
                                        $slaveData['three_c_amount'] = @$value['3c_bet'];
                                        $slaveData['big_bet_amount'] = 0;
                                        $slaveData['small_bet_amount'] = 0;
                                        if (strlen($row) > 3) {
                                            $slaveData['lottery_number'] = substr($row, 1, 3);
                                        } else {
                                            $slaveData['lottery_number'] = $row;
                                        }

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];
                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                        $slaveData['game_type'] = '3D';

                                        $threeASlave = @$value['3a_bet'];
                                        $total3AAmount = TicketSlave::where('lottery_number', $row)->where('game_play_id', $gameId)->sum('three_a_amount');
                                        $temp3A = (float) $total3AAmount + (float) $threeASlave;
                                        $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                        $tempThreeAR = 'ACCEPTED';
                                        if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                            if ($total3AAmount < $threeABetLimit) {
                                                $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            } else {
                                                $tempThreeAR = 'REJECTED';
                                                $slaveData['three_a_amount'] = 0;
                                                $result[$key]['three_a_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            }
                                        } else {
                                            if ($temp3A < $threeABetLimit) {
                                                $tempThreeAR = 'ACCEPTED';
                                            } else {
                                                if ($temp3A > $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            }
                                        }

                                        $threeCSlave = @$value['3c_bet'];
                                        $total3CAmount = TicketSlave::where('lottery_number', $row)->where('game_play_id', $gameId)->sum('three_c_amount');
                                        $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                        $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                        $tempThreeCR = 'ACCEPTED';
                                        if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                            if ($total3CAmount < $threeCBetLimit) {
                                                $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            } else {
                                                $tempThreeCR = 'REJECTED';
                                                $slaveData['three_c_amount'] = 0;
                                                $result[$key]['three_c_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            }
                                        } else {
                                            if ($temp3C < $threeCBetLimit) {
                                                $tempThreeCR = 'ACCEPTED';
                                            } else {
                                                if ($temp3C > $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                        $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];

                                        if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                            $slaveData['bet_size'] = '3C';
                                        } else {
                                            $slaveData['bet_size'] = '3A';
                                        }
                                        TicketSlave::create($slaveData);
                                    }
                                }
                            } elseif (@$value['reverse'] == 'on') {
                                if (@$bigBet || @$smallBet) {
                                    $bigbet_slav = $bigBet;
                                    $smallbet_slav = $smallBet;

                                    $tempLOt = [];
                                    $tempLOt[0] = @$value['number'];
                                    $tempLOt[1] = strrev(@$value['number']);
                                    for ($i = 0; $i < count($tempLOt); $i++) {
                                        $tempLNum = $tempLOt[$i];
                                        $slaveData['lottery_number'] = $tempLNum;
                                        $slaveData['big_bet_amount'] = $bigbet_slav;
                                        $slaveData['small_bet_amount'] = $smallbet_slav;

                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                        $slaveData['game_type'] = '4D';

                                        // check here
                                        $totalBig = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('big_bet_amount');
                                        $tempBig = (float) $totalBig + (float) $bigbet_slav;
                                        $bigBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_big;

                                        $tempbR = 'ACCEPTED';
                                        if (((float) $totalBig + (float) $bigbet_slav) > $bigBetLimit) {
                                            if ($totalBig < $bigBetLimit) {
                                                $tempbR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;

                                                $result[$key]['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['big'] = 'Big';
                                            } else {
                                                $tempbR = 'REJECTED';
                                                $tempbR = 'REJECTED';
                                                $slaveData['big_bet_amount'] = 0;
                                                $result[$key]['big_bet_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['big'] = 'Big';
                                            }
                                        } else {
                                            if ($tempBig < $bigBetLimit) {
                                                $tempbR = 'ACCEPTED';
                                            } else {
                                                if ($tempBig > $bigBetLimit) {
                                                    $tempbR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                    $result[$key]['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['big'] = 'Big';
                                                }
                                            }
                                        }

                                        $totalSmall = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('small_bet_amount');
                                        $tempSmall = (float) $totalSmall + (float) $smallbet_slav;
                                        $smallBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_small;

                                        $tempbS = 'ACCEPTED';
                                        if (((float) $totalSmall + (float) $smallbet_slav) > $smallBetLimit) {
                                            if ($totalSmall < $smallBetLimit) {
                                                $tempbS = 'PARTIALLY_ACCEPTED';
                                                $slaveData['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                $result[$key]['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['small'] = 'Small';
                                                $result[$key]['game'] = $gameAbbreviation;
                                            } else {
                                                $tempbS = 'REJECTED';
                                                $tempbS = 'REJECTED';
                                                $slaveData['small_bet_amount'] = 0;
                                                $result[$key]['small_bet_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['small'] = 'Small';
                                                $result[$key]['game'] = $gameAbbreviation;
                                            }
                                        } else {
                                            if ($tempSmall < $smallBetLimit) {
                                                $tempbS = 'ACCEPTED';
                                            } else {
                                                if ($totalSmall > $smallBetLimit) {
                                                    $tempbS = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                    $result[$key]['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['small'] = 'Small';
                                                    $result[$key]['game'] = $gameAbbreviation;
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempbR == 'ACCEPTED' && $tempbS == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            $result[$key]['number'] = @$value['number'];
                                            $result[$key]['date'] = $ticketDate;
                                            $result[$key]['three_c'] = '3C';
                                            $result[$key]['three_a'] = '3A';
                                            $result[$key]['three_c_amount'] = 0;
                                            $result[$key]['three_a_amount'] = 0;
                                            $result[$key]['big_bet_amount'] = $slaveData['big_bet_amount'];
                                            $result[$key]['small_bet_amount'] = $slaveData['small_bet_amount'];
                                            $result[$key]['game'] = $gameAbbreviation;

                                            if ($tempbR == 'REJECTED' && $tempbS == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $slaveData['bet_amount'] = (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];
                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_4d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];
                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                        $total = (float) $total + (float) $bigBet + (float) $smallBet;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];

                                        if ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] == 0) {
                                            $slaveData['bet_size'] = 'Big';
                                        } else {
                                            $slaveData['bet_size'] = 'Small';
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;
                                        TicketSlave::create($slaveData);
                                    }

                                    //save 3d slave
                                    if (@$value['3a_bet'] || @$value['3c_bet']) {
                                        $slaveData['three_a_amount'] = @$value['3a_bet'];
                                        $slaveData['three_c_amount'] = @$value['3c_bet'];
                                        $slaveData['big_bet_amount'] = 0;
                                        $slaveData['small_bet_amount'] = 0;
                                        if (strlen($tempLNum) > 3) {
                                            $slaveData['lottery_number'] = substr($tempLNum, 1, 3);
                                        } else {
                                            $slaveData['lottery_number'] = $tempLNum;
                                        }

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];
                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                        $slaveData['game_type'] = '3D';

                                        $threeASlave = @$value['3a_bet'];
                                        $total3AAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_a_amount');
                                        $temp3A = (float) $total3AAmount + (float) $threeASlave;
                                        $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                        $tempThreeAR = 'ACCEPTED';
                                        if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                            if ($total3AAmount < $threeABetLimit) {
                                                $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            } else {
                                                $tempThreeAR = 'REJECTED';
                                                $slaveData['three_a_amount'] = 0;
                                                $result[$key]['three_a_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            }
                                        } else {
                                            if ($temp3A < $threeABetLimit) {
                                                $tempThreeAR = 'ACCEPTED';
                                            } else {
                                                if ($temp3A > $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            }
                                        }

                                        $threeCSlave = @$value['3c_bet'];
                                        $total3CAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_c_amount');
                                        $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                        $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                        $tempThreeCR = 'ACCEPTED';
                                        if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                            if ($total3CAmount < $threeCBetLimit) {
                                                $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            } else {
                                                $tempThreeCR = 'REJECTED';
                                                $slaveData['three_c_amount'] = 0;
                                                $result[$key]['three_c_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            }
                                        } else {
                                            if ($temp3C < $threeCBetLimit) {
                                                $tempThreeCR = 'ACCEPTED';
                                            } else {
                                                if ($temp3C > $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                        $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];

                                        if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                            $slaveData['bet_size'] = '3C';
                                        } else {
                                            $slaveData['bet_size'] = '3A';
                                        }

                                        TicketSlave::create($slaveData);
                                    }
                                }
                            } else {
                                if (preg_match('/R/i', @$value['number'])) {
                                    if (@$bigBet || @$smallBet) {
                                        $bigbet_slav = $bigBet;
                                        $smallbet_slav = $smallBet;
                                        for ($i = 0; $i <= 9; $i++) {
                                            $tempLNum = str_replace('R', $i, @$value['number']);
                                            $slaveData['lottery_number'] = $tempLNum;
                                            $slaveData['big_bet_amount'] = $bigbet_slav;
                                            $slaveData['small_bet_amount'] = $smallbet_slav;

                                            $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                            $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                            $slaveData['game_type'] = '4D';

                                            // check here
                                            $totalBig = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('big_bet_amount');
                                            $tempBig = (float) $totalBig + (float) $bigbet_slav;
                                            $bigBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_big;

                                            $tempbR = 'ACCEPTED';
                                            if (((float) $totalBig + (float) $bigbet_slav) > $bigBetLimit) {
                                                if ($totalBig < $bigBetLimit) {
                                                    $tempbR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;

                                                    $result[$key]['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['big'] = 'Big';
                                                } else {
                                                    $tempbR = 'REJECTED';
                                                    $tempbR = 'REJECTED';
                                                    $slaveData['big_bet_amount'] = 0;
                                                    $result[$key]['big_bet_amount'] = 0;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['big'] = 'Big';
                                                }
                                            } else {
                                                if ($tempBig < $bigBetLimit) {
                                                    $tempbR = 'ACCEPTED';
                                                } else {
                                                    if ($tempBig > $bigBetLimit) {
                                                        $tempbR = 'PARTIALLY_ACCEPTED';
                                                        $slaveData['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                        $result[$key]['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                        $result[$key]['number'] = @$value['number'];
                                                        $result[$key]['date'] = $ticketDate;
                                                        $result[$key]['big'] = 'Big';
                                                    }
                                                }
                                            }

                                            $totalSmall = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('small_bet_amount');
                                            $tempSmall = (float) $totalSmall + (float) $smallbet_slav;
                                            $smallBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_small;

                                            $tempbS = 'ACCEPTED';
                                            if (((float) $totalSmall + (float) $smallbet_slav) > $smallBetLimit) {
                                                if ($totalSmall < $smallBetLimit) {
                                                    $tempbS = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                    $result[$key]['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['small'] = 'Small';
                                                    $result[$key]['game'] = $gameAbbreviation;
                                                } else {
                                                    $tempbS = 'REJECTED';
                                                    $tempbS = 'REJECTED';
                                                    $slaveData['small_bet_amount'] = 0;
                                                    $result[$key]['small_bet_amount'] = 0;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['small'] = 'Small';
                                                    $result[$key]['game'] = $gameAbbreviation;
                                                }
                                            } else {
                                                if ($tempSmall < $smallBetLimit) {
                                                    $tempbS = 'ACCEPTED';
                                                } else {
                                                    if ($totalSmall > $smallBetLimit) {
                                                        $tempbS = 'PARTIALLY_ACCEPTED';
                                                        $slaveData['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                        $result[$key]['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                        $result[$key]['number'] = @$value['number'];
                                                        $result[$key]['date'] = $ticketDate;
                                                        $result[$key]['small'] = 'Small';
                                                        $result[$key]['game'] = $gameAbbreviation;
                                                    }
                                                }
                                            }
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                            if ($tempbR == 'ACCEPTED' && $tempbS == 'ACCEPTED') {
                                                $slaveData['progress_status'] = 'ACCEPTED';
                                            } else {
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                                $result[$key]['three_a'] = '3A';
                                                $result[$key]['three_c_amount'] = 0;
                                                $result[$key]['three_a_amount'] = 0;
                                                $result[$key]['big_bet_amount'] = $slaveData['big_bet_amount'];
                                                $result[$key]['small_bet_amount'] = $slaveData['small_bet_amount'];
                                                $result[$key]['game'] = $gameAbbreviation;
                                                if ($tempbR == 'REJECTED' && $tempbS == 'REJECTED') {
                                                    $slaveData['progress_status'] = 'REJECTED';
                                                } else {
                                                    $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                                }
                                            }

                                            $slaveData['bet_amount'] = (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];
                                            $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ((float) $slaveData['bet_amount'] * $oddSetting->rebate_4d / 100);
                                            $slaveData['rebate_amount'] = (float) $slaveData['bet_amount'] - (float) $slaveData['bet_net_amount'];
                                            $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];
                                            $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                            $total = (float) $total + (float) $bigBet + (float) $smallBet;
                                            $acp_bet = (float) $acp_bet + (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];
                                            if ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] != 0) {
                                                $slaveData['bet_size'] = 'Both';
                                            } elseif ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] == 0) {
                                                $slaveData['bet_size'] = 'Big';
                                            } else {
                                                $slaveData['bet_size'] = 'Small';
                                            }

                                            $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                            $append = zeroappend($LastNumber);
                                            $childTicketNo = 'C'.$append.$LastNumber;
                                            $slaveData['child_ticket_no'] = $childTicketNo;
                                            TicketSlave::create($slaveData);
                                        }

                                        if (@$value['3a_bet'] || @$value['3c_bet']) {
                                            $slaveData['three_a_amount'] = @$value['3a_bet'];
                                            $slaveData['three_c_amount'] = @$value['3c_bet'];
                                            $slaveData['big_bet_amount'] = 0;
                                            $slaveData['small_bet_amount'] = 0;
                                            if (strlen($tempLNum) > 3) {
                                                $slaveData['lottery_number'] = substr($tempLNum, 1, 3);
                                            } else {
                                                $slaveData['lottery_number'] = $tempLNum;
                                            }
                                            $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                            $slaveData['game_type'] = '3D';

                                            $threeASlave = @$value['3a_bet'];
                                            $total3AAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_a_amount');
                                            $temp3A = (float) $total3AAmount + (float) $threeASlave;
                                            $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                            $tempThreeAR = 'ACCEPTED';
                                            if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                                if ($total3AAmount < $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                    $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                } else {
                                                    $tempThreeAR = 'REJECTED';
                                                    $slaveData['three_a_amount'] = 0;
                                                    $result[$key]['three_a_amount'] = 0;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            } else {
                                                if ($temp3A < $threeABetLimit) {
                                                    $tempThreeAR = 'ACCEPTED';
                                                } else {
                                                    if ($temp3A > $threeABetLimit) {
                                                        $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                        $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                        $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                        $result[$key]['number'] = @$value['number'];
                                                        $result[$key]['date'] = $ticketDate;
                                                        $result[$key]['three_a'] = '3A';
                                                    }
                                                }
                                            }

                                            $threeCSlave = @$value['3c_bet'];
                                            $total3CAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_c_amount');
                                            $temp3C = $total3CAmount + $threeCSlave;
                                            $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                            $tempThreeCR = 'ACCEPTED';
                                            if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                                if ($total3CAmount < $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                    $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                } else {
                                                    $tempThreeCR = 'REJECTED';
                                                    $slaveData['three_c_amount'] = 0;
                                                    $result[$key]['three_c_amount'] = 0;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            } else {
                                                if ($temp3C < $threeCBetLimit) {
                                                    $tempThreeCR = 'ACCEPTED';
                                                } else {
                                                    if ($temp3C > $threeCBetLimit) {
                                                        $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                        $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                        $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                        $result[$key]['number'] = @$value['number'];
                                                        $result[$key]['date'] = $ticketDate;
                                                        $result[$key]['three_c'] = '3C';
                                                    }
                                                }
                                            }

                                            $slaveData['progress_status'] = 'ACCEPTED';
                                            if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                                $slaveData['progress_status'] = 'ACCEPTED';
                                            } else {
                                                if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                    $slaveData['progress_status'] = 'REJECTED';
                                                } else {
                                                    $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                                }
                                            }

                                            $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                            $append = zeroappend($LastNumber);
                                            $childTicketNo = 'C'.$append.$LastNumber;
                                            $slaveData['child_ticket_no'] = $childTicketNo;
                                            $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ((float) $slaveData['bet_amount'] * (float) $oddSetting->rebate_3d / 100);
                                            $slaveData['rebate_amount'] = (float) $slaveData['bet_amount'] - (float) $slaveData['bet_net_amount'];
                                            $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                            $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                            $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                            if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                                $slaveData['bet_size'] = 'Both';
                                            } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                                $slaveData['bet_size'] = '3C';
                                            } else {
                                                $slaveData['bet_size'] = '3A';
                                            }

                                            TicketSlave::create($slaveData);
                                        }
                                    }
                                } else {
                                    if ($bigBet || $smallBet) {
                                        $bigbet_slav = $bigBet;
                                        $smallbet_slav = $smallBet;
                                        $slaveData['lottery_number'] = @$value['number'];
                                        $slaveData['bet_amount'] = @$value['amount'];
                                        $slaveData['big_bet_amount'] = $bigbet_slav;
                                        $slaveData['small_bet_amount'] = $smallbet_slav;
                                        $slaveData['game_type'] = '4D';

                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;

                                        // check here
                                        $totalBig = TicketSlave::where('lottery_number', @$value['number'])->where('game_play_id', $gameId)->sum('big_bet_amount');
                                        $tempBig = (float) $totalBig + (float) $bigbet_slav;
                                        $bigBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_big;

                                        $tempbR = 'ACCEPTED';
                                        if (((float) $totalBig + (float) $bigbet_slav) > $bigBetLimit) {
                                            if ($totalBig < $bigBetLimit) {
                                                $tempbR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;

                                                $result[$key]['big_bet_amount'] = (float) $bigBetLimit - (float) $totalBig;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['big'] = 'Big';
                                            } else {
                                                $tempbR = 'REJECTED';
                                                $tempbR = 'REJECTED';
                                                $slaveData['big_bet_amount'] = 0;
                                                $result[$key]['big_bet_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['big'] = 'Big';
                                            }
                                        } else {
                                            if ($tempBig < $bigBetLimit) {
                                                $tempbR = 'ACCEPTED';
                                            } else {
                                                if ($tempBig > $bigBetLimit) {
                                                    $tempbR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                    $result[$key]['big_bet_amount'] = (float) $tempBig - (float) $bigBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['big'] = 'Big';
                                                }
                                            }
                                        }

                                        $totalSmall = TicketSlave::where('lottery_number', @$value['number'])->where('game_play_id', $gameId)->sum('small_bet_amount');
                                        $tempSmall = (float) $totalSmall + (float) $smallbet_slav;
                                        $smallBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_small;

                                        $tempbS = 'ACCEPTED';
                                        if (((float) $totalSmall + (float) $smallbet_slav) > $smallBetLimit) {
                                            if ($totalSmall < $smallBetLimit) {
                                                $tempbS = 'PARTIALLY_ACCEPTED';
                                                $slaveData['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                $result[$key]['small_bet_amount'] = (float) $smallBetLimit - (float) $totalSmall;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['small'] = 'Small';
                                                $result[$key]['game'] = $gameAbbreviation;
                                            } else {
                                                $tempbS = 'REJECTED';
                                                $tempbS = 'REJECTED';
                                                $slaveData['small_bet_amount'] = 0;
                                                $result[$key]['small_bet_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['small'] = 'Small';
                                                $result[$key]['game'] = $gameAbbreviation;
                                            }
                                        } else {
                                            if ($tempSmall < $smallBetLimit) {
                                                $tempbS = 'ACCEPTED';
                                            } else {
                                                if ($totalSmall > $smallBetLimit) {
                                                    $tempbS = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                    $result[$key]['small_bet_amount'] = (float) $tempSmall - (float) $smallBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['small'] = 'Small';
                                                    $result[$key]['game'] = $gameAbbreviation;
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempbR == 'ACCEPTED' && $tempbS == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            $result[$key]['number'] = @$value['number'];
                                            $result[$key]['date'] = $ticketDate;
                                            $result[$key]['three_c'] = '3C';
                                            $result[$key]['three_a'] = '3A';
                                            $result[$key]['three_c_amount'] = 0;
                                            $result[$key]['three_a_amount'] = 0;
                                            $result[$key]['big_bet_amount'] = $slaveData['big_bet_amount'];
                                            $result[$key]['small_bet_amount'] = $slaveData['small_bet_amount'];
                                            $result[$key]['game'] = $gameAbbreviation;

                                            if ($tempbR == 'REJECTED' && $tempbS == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $slaveData['bet_amount'] = (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_4d / 100);

                                        $slaveData['rebate_amount'] = (float) $slaveData['bet_amount'] - (float) $slaveData['bet_net_amount'];

                                        $rebat = (float) $rebat + $slaveData['rebate_amount'];

                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_4d;
                                        $total = (float) $total + (float) $bigBet + (float) $smallBet;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['big_bet_amount'] + (float) $slaveData['small_bet_amount'];

                                        if ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['big_bet_amount'] != 0 && (float) $slaveData['small_bet_amount'] == 0) {
                                            $slaveData['bet_size'] = 'Big';
                                        } else {
                                            $slaveData['bet_size'] = 'Small';
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;
                                        TicketSlave::create($slaveData);
                                    }

                                    //save 3d slave
                                    if (@$value['3a_bet'] || @$value['3c_bet']) {
                                        $slaveData['three_a_amount'] = @$value['3a_bet'];
                                        $slaveData['three_c_amount'] = @$value['3c_bet'];
                                        $slaveData['big_bet_amount'] = 0;
                                        $slaveData['small_bet_amount'] = 0;
                                        if (strlen(@$value['number']) > 3) {
                                            $slaveData['lottery_number'] = substr(@$value['number'], 1, 3);
                                        } else {
                                            $slaveData['lottery_number'] = @$value['number'];
                                        }

                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                        $slaveData['game_type'] = '3D';

                                        $threeASlave = @$value['3a_bet'];
                                        $total3AAmount = TicketSlave::where('lottery_number', @$value['number'])->where('game_play_id', $gameId)->sum('three_a_amount');
                                        $temp3A = (float) $total3AAmount + (float) $threeASlave;
                                        $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                        $tempThreeAR = 'ACCEPTED';
                                        if (($total3AAmount + $threeASlave) > $threeABetLimit) {
                                            if ($total3AAmount < $threeABetLimit) {
                                                $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            } else {
                                                $tempThreeAR = 'REJECTED';
                                                $slaveData['three_a_amount'] = 0;
                                                $result[$key]['three_a_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            }
                                        } else {
                                            if ($temp3A < $threeABetLimit) {
                                                $tempThreeAR = 'ACCEPTED';
                                            } else {
                                                if ($temp3A > $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            }
                                        }

                                        $threeCSlave = @$value['3c_bet'];
                                        $total3CAmount = TicketSlave::where('lottery_number', @$value['number'])->where('game_play_id', $gameId)->sum('three_c_amount');
                                        $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                        $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                        $tempThreeCR = 'ACCEPTED';
                                        if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                            if ($total3CAmount < $threeCBetLimit) {
                                                $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            } else {
                                                $tempThreeCR = 'REJECTED';
                                                $slaveData['three_c_amount'] = 0;
                                                $result[$key]['three_c_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            }
                                        } else {
                                            if ($temp3C < $threeCBetLimit) {
                                                $tempThreeCR = 'ACCEPTED';
                                            } else {
                                                if ($temp3C > $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }
                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;

                                        $slaveData['bet_amount'] = (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                        $slaveData['bet_net_amount'] = (float) $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = (float) $slaveData['bet_amount'] - (float) $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                        $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                        if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                            $slaveData['bet_size'] = '3C';
                                        } elseif ((float) $slaveData['three_c_amount'] == 0 && (float) $slaveData['three_a_amount'] != 0) {
                                            $slaveData['bet_size'] = '3A';
                                        }

                                        TicketSlave::create($slaveData);
                                    }
                                }
                            }
                        } else {
                            if (@$value['box'] == 'on' || @$value['ibox'] == 'on') {
                                $str = @$value['number'];
                                $n = strlen($str);
                                $this->permute($str, 0, $n - 1);

                                $temp = [];
                                for ($j = 0; $j < count($this->result); $j++) {
                                    if (! in_array($this->result[$j], $temp)) {
                                        array_push($temp, $this->result[$j]);
                                    }
                                }

                                $lotteryAmt = (float) $bigBet + (float) $smallBet;
                                if (@$value['box'] == 'on') {
                                    $slaveData['bet_amount'] = $lotteryAmt;
                                    $bigbet_slav = $bigBet;
                                    $smallbet_slav = $smallBet;
                                } else {
                                    $slaveData['bet_amount'] = @$lotteryAmt / count($temp);
                                    $bigbet_slav = $bigBet / count($temp);
                                    $smallbet_slav = $smallBet / count($temp);
                                }

                                foreach ($temp as $key => $row) {
                                    $slaveData['lottery_number'] = $row;
                                    $slaveData['big_bet_amount'] = $bigbet_slav;
                                    $slaveData['small_bet_amount'] = $smallbet_slav;

                                    //save 3d slave
                                    if (@$value['3a_bet'] || @$value['3c_bet']) {
                                        $slaveData['three_a_amount'] = @$value['3a_bet'];
                                        $slaveData['three_c_amount'] = @$value['3c_bet'];

                                        $slaveData['big_bet_amount'] = 0;
                                        $slaveData['small_bet_amount'] = 0;
                                        if (strlen($row) > 3) {
                                            $slaveData['lottery_number'] = substr($row, 1, 3);
                                        } else {
                                            $slaveData['lottery_number'] = $row;
                                        }

                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                        $slaveData['game_type'] = '3D';

                                        $threeASlave = @$value['3a_bet'];
                                        $total3AAmount = TicketSlave::where('lottery_number', $row)->where('game_play_id', $gameId)->sum('three_a_amount');
                                        $temp3A = (float) $total3AAmount + (float) $threeASlave;
                                        $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                        $tempThreeAR = 'ACCEPTED';
                                        if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                            if ($total3AAmount < $threeABetLimit) {
                                                $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            } else {
                                                $tempThreeAR = 'REJECTED';
                                                $slaveData['three_a_amount'] = 0;
                                                $result[$key]['three_a_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            }
                                        } else {
                                            if ($temp3A < $threeABetLimit) {
                                                $tempThreeAR = 'ACCEPTED';
                                            } else {
                                                if ($temp3A > $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            }
                                        }

                                        $threeCSlave = @$value['3c_bet'];
                                        $total3CAmount = TicketSlave::where('lottery_number', $row)->where('game_play_id', $gameId)->sum('three_c_amount');
                                        $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                        $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                        $tempThreeCR = 'ACCEPTED';
                                        if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                            if ($total3CAmount < $threeCBetLimit) {
                                                $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            } else {
                                                $tempThreeCR = 'REJECTED';
                                                $slaveData['three_c_amount'] = 0;
                                                $result[$key]['three_c_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            }
                                        } else {
                                            if ($temp3C < $threeCBetLimit) {
                                                $tempThreeCR = 'ACCEPTED';
                                            } else {
                                                if ($temp3C > $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            $result[$key]['number'] = @$value['number'];
                                            $result[$key]['date'] = $ticketDate;
                                            $result[$key]['three_c'] = '3C';
                                            $result[$key]['three_a'] = '3A';
                                            $result[$key]['three_c_amount'] = $slaveData['three_c_amount'];
                                            $result[$key]['three_a_amount'] = $slaveData['three_a_amount'];
                                            $result[$key]['big_bet_amount'] = 0;
                                            $result[$key]['small_bet_amount'] = 0;
                                            $result[$key]['game'] = $gameAbbreviation;
                                            if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;
                                        $slaveData['bet_amount'] = (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                        $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];

                                        if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                            $slaveData['bet_size'] = '3C';
                                        } else {
                                            $slaveData['bet_size'] = '3A';
                                        }
                                        TicketSlave::create($slaveData);
                                    }
                                }
                            } elseif (@$value['reverse'] == 'on') {
                                $bigbet_slav = $bigBet;
                                $smallbet_slav = $smallBet;

                                $tempLOt = [];
                                $tempLOt[0] = @$value['number'];
                                $tempLOt[1] = strrev(@$value['number']);
                                for ($i = 0; $i < count($tempLOt); $i++) {
                                    $tempLNum = $tempLOt[$i];
                                    $slaveData['lottery_number'] = $tempLNum;

                                    if (@$value['3a_bet'] || @$value['3c_bet']) {
                                        $slaveData['three_a_amount'] = @$value['3a_bet'];
                                        $slaveData['three_c_amount'] = @$value['3c_bet'];
                                        $slaveData['big_bet_amount'] = 0;
                                        $slaveData['small_bet_amount'] = 0;
                                        if (strlen($tempLNum) > 3) {
                                            $slaveData['lottery_number'] = substr($tempLNum, 1, 3);
                                        } else {
                                            $slaveData['lottery_number'] = $tempLNum;
                                        }
                                        $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                        $slaveData['game_type'] = '3D';

                                        $threeASlave = @$value['3a_bet'];
                                        $total3AAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_a_amount');
                                        $temp3A = $total3AAmount + $threeASlave;
                                        $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                        $tempThreeAR = 'ACCEPTED';
                                        if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                            if ($total3AAmount < $threeABetLimit) {
                                                $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            } else {
                                                $tempThreeAR = 'REJECTED';
                                                $slaveData['three_a_amount'] = 0;
                                                $result[$key]['three_a_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_a'] = '3A';
                                            }
                                        } else {
                                            if ($temp3A < $threeABetLimit) {
                                                $tempThreeAR = 'ACCEPTED';
                                            } else {
                                                if ($temp3A > $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            }
                                        }

                                        $threeCSlave = @$value['3c_bet'];
                                        $total3CAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_c_amount');
                                        $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                        $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                        $tempThreeCR = 'ACCEPTED';
                                        if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                            if ($total3CAmount < $threeCBetLimit) {
                                                $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            } else {
                                                $tempThreeCR = 'REJECTED';
                                                $slaveData['three_c_amount'] = 0;
                                                $result[$key]['three_c_amount'] = 0;
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                            }
                                        } else {
                                            if ($temp3C < $threeCBetLimit) {
                                                $tempThreeCR = 'ACCEPTED';
                                            } else {
                                                if ($temp3C > $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            }
                                        }

                                        $slaveData['progress_status'] = 'ACCEPTED';
                                        if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                            $slaveData['progress_status'] = 'ACCEPTED';
                                        } else {
                                            $result[$key]['number'] = @$value['number'];
                                            $result[$key]['date'] = $ticketDate;
                                            $result[$key]['three_c'] = '3C';
                                            $result[$key]['three_a'] = '3A';
                                            $result[$key]['three_c_amount'] = $slaveData['three_c_amount'];
                                            $result[$key]['three_a_amount'] = $slaveData['three_a_amount'];
                                            $result[$key]['big_bet_amount'] = 0;
                                            $result[$key]['small_bet_amount'] = 0;
                                            $result[$key]['game'] = $gameAbbreviation;
                                            if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                $slaveData['progress_status'] = 'REJECTED';
                                            } else {
                                                $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            }
                                        }

                                        $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                        $append = zeroappend($LastNumber);
                                        $childTicketNo = 'C'.$append.$LastNumber;
                                        $slaveData['child_ticket_no'] = $childTicketNo;
                                        $slaveData['bet_amount'] = (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];

                                        $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                        $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                        $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                        $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                        $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                        if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                            $slaveData['bet_size'] = 'Both';
                                        } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                            $slaveData['bet_size'] = '3C';
                                        } else {
                                            $slaveData['bet_size'] = '3A';
                                        }
                                        TicketSlave::create($slaveData);
                                    }
                                }
                            } else {
                                if (preg_match('/R/i', @$value['number'])) {
                                    $bigbet_slav = $bigBet;
                                    $smallbet_slav = $smallBet;
                                    for ($i = 0; $i <= 9; $i++) {
                                        $tempLNum = str_replace('R', $i, @$value['number']);
                                        $slaveData['lottery_number'] = $tempLNum;

                                        if (@$value['3a_bet'] || @$value['3c_bet']) {
                                            $slaveData['three_a_amount'] = @$value['3a_bet'];
                                            $slaveData['three_c_amount'] = @$value['3c_bet'];
                                            $slaveData['big_bet_amount'] = 0;
                                            $slaveData['small_bet_amount'] = 0;
                                            if (strlen($tempLNum) > 3) {
                                                $slaveData['lottery_number'] = substr($tempLNum, 1, 3);
                                            } else {
                                                $slaveData['lottery_number'] = $tempLNum;
                                            }

                                            $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                            $slaveData['game_type'] = '3D';

                                            $threeASlave = @$value['3a_bet'];
                                            $total3AAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_a_amount');
                                            $temp3A = (float) $total3AAmount + (float) $threeASlave;
                                            $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                            $tempThreeAR = 'ACCEPTED';
                                            if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                                if ($total3AAmount < $threeABetLimit) {
                                                    $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;

                                                    $result[$key]['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                } else {
                                                    $tempThreeAR = 'REJECTED';
                                                    $slaveData['three_a_amount'] = 0;
                                                    $result[$key]['three_a_amount'] = 0;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_a'] = '3A';
                                                }
                                            } else {
                                                if ($temp3A < $threeABetLimit) {
                                                    $tempThreeAR = 'ACCEPTED';
                                                } else {
                                                    if ($temp3A > $threeABetLimit) {
                                                        $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                        $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                        $result[$key]['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                                        $result[$key]['number'] = @$value['number'];
                                                        $result[$key]['date'] = $ticketDate;
                                                        $result[$key]['three_a'] = '3A';
                                                    }
                                                }
                                            }

                                            $threeCSlave = @$value['3c_bet'];
                                            $total3CAmount = TicketSlave::where('lottery_number', $tempLNum)->where('game_play_id', $gameId)->sum('three_c_amount');
                                            $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                            $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                            $tempThreeCR = 'ACCEPTED';
                                            if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                                if ($total3CAmount < $threeCBetLimit) {
                                                    $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                    $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;

                                                    $result[$key]['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                } else {
                                                    $tempThreeCR = 'REJECTED';
                                                    $slaveData['three_c_amount'] = 0;
                                                    $result[$key]['three_c_amount'] = 0;
                                                    $result[$key]['number'] = @$value['number'];
                                                    $result[$key]['date'] = $ticketDate;
                                                    $result[$key]['three_c'] = '3C';
                                                }
                                            } else {
                                                if ($temp3C < $threeCBetLimit) {
                                                    $tempThreeCR = 'ACCEPTED';
                                                } else {
                                                    if ($temp3C > $threeCBetLimit) {
                                                        $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                        $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                        $result[$key]['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                                        $result[$key]['number'] = @$value['number'];
                                                        $result[$key]['date'] = $ticketDate;
                                                        $result[$key]['three_c'] = '3C';
                                                    }
                                                }
                                            }

                                            $slaveData['progress_status'] = 'ACCEPTED';
                                            if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                                $slaveData['progress_status'] = 'ACCEPTED';
                                            } else {
                                                $result[$key]['number'] = @$value['number'];
                                                $result[$key]['date'] = $ticketDate;
                                                $result[$key]['three_c'] = '3C';
                                                $result[$key]['three_a'] = '3A';
                                                $result[$key]['three_c_amount'] = $slaveData['three_c_amount'];
                                                $result[$key]['three_a_amount'] = $slaveData['three_a_amount'];
                                                $result[$key]['big_bet_amount'] = 0;
                                                $result[$key]['small_bet_amount'] = 0;
                                                $result[$key]['game'] = $gameAbbreviation;
                                                if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                                    $slaveData['progress_status'] = 'REJECTED';
                                                } else {
                                                    $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                                }
                                            }

                                            $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                            $append = zeroappend($LastNumber);
                                            $childTicketNo = 'C'.$append.$LastNumber;
                                            $slaveData['child_ticket_no'] = $childTicketNo;
                                            $slaveData['bet_amount'] = (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                            $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                            $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                            $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                            $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                            $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                            if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                                $slaveData['bet_size'] = 'Both';
                                            } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                                $slaveData['bet_size'] = '3C';
                                            } else {
                                                $slaveData['bet_size'] = '3A';
                                            }

                                            TicketSlave::create($slaveData);
                                        }
                                    }
                                } else {
                                    $slaveData['three_a_amount'] = @$value['3a_bet'];
                                    $slaveData['three_c_amount'] = @$value['3c_bet'];
                                    $slaveData['big_bet_amount'] = 0;
                                    $slaveData['small_bet_amount'] = 0;
                                    if (strlen(@$value['number']) > 3) {
                                        $slaveData['lottery_number'] = substr(@$value['number'], 1, 3);
                                    } else {
                                        $slaveData['lottery_number'] = @$value['number'];
                                    }

                                    $slaveData['rebate_percentage'] = $oddSetting->rebate_3d;
                                    $slaveData['game_type'] = '3D';

                                    $threeASlave = @$value['3a_bet'];
                                    $total3AAmount = TicketSlave::where('lottery_number', @$value['number'])->where('game_play_id', $gameId)->sum('three_a_amount');
                                    $temp3A = $total3AAmount + $threeASlave;
                                    $threeABetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_a;

                                    $tempThreeAR = 'ACCEPTED';
                                    if (((float) $total3AAmount + (float) $threeASlave) > $threeABetLimit) {
                                        if ($total3AAmount < $threeABetLimit) {
                                            $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                            $slaveData['three_a_amount'] = (float) $threeABetLimit - (float) $total3AAmount;
                                        } else {
                                            $tempThreeAR = 'REJECTED';
                                            $slaveData['three_a_amount'] = 0;
                                        }
                                    } else {
                                        if ($temp3A < $threeABetLimit) {
                                            $tempThreeAR = 'ACCEPTED';
                                        } else {
                                            if ($temp3A > $threeABetLimit) {
                                                $tempThreeAR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_a_amount'] = (float) $temp3A - (float) $threeABetLimit;
                                            }
                                        }
                                    }

                                    $threeCSlave = @$value['3c_bet'];
                                    $total3CAmount = TicketSlave::where('lottery_number', @$value['number'])->where('game_play_id', $gameId)->sum('three_c_amount');
                                    $temp3C = (float) $total3CAmount + (float) $threeCSlave;
                                    $threeCBetLimit = $merchant->betLimit->limitSettings->where('game_play_id', $gameId)->first()->game_limit_three_c;

                                    $tempThreeCR = 'ACCEPTED';
                                    if (((float) $total3CAmount + (float) $threeASlave) > $threeCBetLimit) {
                                        if ($total3CAmount < $threeCBetLimit) {
                                            $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                            $slaveData['three_c_amount'] = (float) $threeCBetLimit - (float) $total3CAmount;
                                        } else {
                                            $tempThreeCR = 'REJECTED';
                                            $slaveData['three_c_amount'] = 0;
                                        }
                                    } else {
                                        if ($temp3C < $threeCBetLimit) {
                                            $tempThreeCR = 'ACCEPTED';
                                        } else {
                                            if ($temp3C > $threeCBetLimit) {
                                                $tempThreeCR = 'PARTIALLY_ACCEPTED';
                                                $slaveData['three_c_amount'] = (float) $temp3C - (float) $threeCBetLimit;
                                            }
                                        }
                                    }

                                    $slaveData['progress_status'] = 'ACCEPTED';
                                    if ($tempThreeAR == 'ACCEPTED' && $tempThreeCR == 'ACCEPTED') {
                                        $slaveData['progress_status'] = 'ACCEPTED';
                                    } else {
                                        $result[$key]['number'] = @$value['number'];
                                        $result[$key]['date'] = $ticketDate;
                                        $result[$key]['three_c'] = '3C';
                                        $result[$key]['three_a'] = '3A';
                                        $result[$key]['three_c_amount'] = $slaveData['three_c_amount'];
                                        $result[$key]['three_a_amount'] = $slaveData['three_a_amount'];
                                        $result[$key]['big_bet_amount'] = 0;
                                        $result[$key]['small_bet_amount'] = 0;
                                        $result[$key]['game'] = $gameAbbreviation;
                                        if ($tempThreeAR == 'REJECTED' && $tempThreeCR == 'REJECTED') {
                                            $slaveData['progress_status'] = 'REJECTED';
                                            $result[$key]['type'] = 'REJECTED';
                                        } else {
                                            $slaveData['progress_status'] = 'PARTIALLY_ACCEPTEDD';
                                            $result[$key]['type'] = 'PARTIALLY_ACCEPTEDD';
                                        }
                                    }
                                    $LastNumber = DB::table('ticket_slaves')->max('id') + 1;
                                    $append = zeroappend($LastNumber);
                                    $childTicketNo = 'C'.$append.$LastNumber;
                                    $slaveData['child_ticket_no'] = $childTicketNo;

                                    $slaveData['bet_amount'] = (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                    $slaveData['bet_net_amount'] = $slaveData['bet_amount'] - ($slaveData['bet_amount'] * $oddSetting->rebate_3d / 100);
                                    $slaveData['rebate_amount'] = $slaveData['bet_amount'] - $slaveData['bet_net_amount'];
                                    $rebat = (float) $rebat + (float) $slaveData['rebate_amount'];

                                    if ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] != 0) {
                                        $slaveData['bet_size'] = 'Both';
                                    } elseif ((float) $slaveData['three_c_amount'] != 0 && (float) $slaveData['three_a_amount'] == 0) {
                                        $slaveData['bet_size'] = '3C';
                                    } else {
                                        $slaveData['bet_size'] = '3A';
                                    }

                                    $total = (float) $total + (float) @$threeASlave + (float) $threeCSlave;
                                    $acp_bet = (float) $acp_bet + (float) $slaveData['three_c_amount'] + (float) $slaveData['three_a_amount'];
                                    TicketSlave::create($slaveData);
                                }
                            }
                        }
                    }
                    // }
                }
                // }
            }

            //deduct amount from wallet
            $userWallet->amount = (float) $userWallet->amount - (float) $acp_bet;
            $userWallet->update();

            //add transaction record
            $LastNumber = DB::table('transactions')->max('id') + 1;
            $append = zeroappend($LastNumber);
            $transactionNo = 'TRA'.$append.$LastNumber;

            $transactionData['transaction_id'] = $transactionNo;
            $transactionData['member_id'] = $memberId;
            $transactionData['merchant_id'] = $merchantId;
            $transactionData['transaction_type'] = 'Debit';
            $transactionData['transaction_from'] = 'betting';
            $transactionData['amount'] = $totalAmount;
            $transactionData['currency'] = $merchant->currency->code;
            $transactionData['message'] = 'bet ticket transaction';
            $transactionData['status'] = 'Complete';
            Transaction::create($transactionData);
            DB::commit();
            $finalResult = [];

            $finalResult['total'] = (string) $total;
            $finalResult['acp_bet'] = (string) $acp_bet;
            $finalResult['rebat'] = (string) $rebat;

            $finalResult['netAmount'] = (string) ($acp_bet - $rebat);
            $finalResult['rejected'] = $result;
        } catch (\Exception $th) {
            Log::error($th);

            return generalErrorResponse($th);
        }

        return response()->json([
            'data' => $finalResult,
            'message_id' => 200,
            'messages' => ['tickect_success'],
        ], 201);
    }

    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'id';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            if (! empty($request->ticket_status)) {
                $query->where('ticket_status', $request->ticket_status);
            }
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    // public function store(array $data): JsonResponse
    // {
    //     try {
    //             $ticket = Ticket::create($data);
    //         return response()->json([
    //             'messages' => ['Ticket created successfully'],
    //         ], 200);
    //     } catch (\Exception$e) {
    //         return generalErrorResponse($e);
    //     }
    // }

    public function update($ticket, array $data): JsonResponse
    {
        try {
            $ticket->update($data);

            return response()->json([
                'messages' => ['Ticket updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($ticket): JsonResponse
    {
        try {
            $ticket->delete();

            return response()->json([
                'messages' => ['Ticket deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getTicket($request)
    {
        $memberId = $request->member_id;
        $ticket = Ticket::with('ticketSlaves')
            ->withSum('ticketSlaves as total_big_bet_amount', 'big_bet_amount')
            ->withSum('ticketSlaves as total_small_bet_amount', 'small_bet_amount')
            ->withSum('ticketSlaves as total_three_a_amount', 'three_a_amount')
            ->withSum('ticketSlaves as total_three_c_amount', 'three_c_amount')
            ->withSum('ticketSlaves as total_bet_amount', 'bet_amount')
            ->withSum('ticketSlaves as total_bet_net_amount', 'bet_net_amount')
            ->whereMemberId($memberId);
        if (! empty($request->ticket_status)) {
            $ticket->whereTicketStatus($request->ticket_status);
        }
        if (! empty($request->ticket_no)) {
            $ticket->whereTicketNo($request->ticket_no);
        }
        if (! empty($request->id)) {
            $ticket->whereId($request->id);
        }
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $dateFrom = ! empty($dateFrom) ? date('Y-m-d', strtotime($dateFrom)) : null;
        $dateTo = ! empty($dateTo) ? date('Y-m-d', strtotime($dateTo)) : null;

        if (! empty($dateFrom) && ! empty($dateTo)) {
            $ticket->whereBetween('betting_date', [$dateFrom, $dateTo]);
        } elseif (! empty($dateFrom) && empty($dateTo)) {
            $ticket->whereBettingDate('betting_date', $dateFrom);
        } elseif (empty($dateFrom) && ! empty($dateTo)) {
            $ticket->whereBettingDate('betting_date', $dateTo);
        }
        $ticket = $ticket->get();

        if (! empty($ticket)) {
            return response()->json([
                'data' => $ticket,
                'success' => true,
                'messages' => ['Ticket fetched successfully'],
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'messages' => 'No Ticket',
            ], 200);
        }
    }

    public function permute($str, $l, $r)
    {
        if ($l == $r) {
            //echo $str. "\n";
            $this->result[] = $str;
        } else {
            for ($i = $l; $i <= $r; $i++) {
                $str = $this->swap($str, $l, $i);
                $this->permute($str, $l + 1, $r);
                $str = $this->swap($str, $l, $i);
            }
        }
        //return $result;
    }

    /* Swap Characters at position @param
    a string value @param i position 1
    @param j position 2 @return swapped
    string */
    public function swap($a, $i, $j)
    {
        $charArray = str_split($a);
        $temp = $charArray[$i];
        $charArray[$i] = $charArray[$j];
        $charArray[$j] = $temp;

        return implode($charArray);
    }

    public function betList($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 1000;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'id';
            //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $sortOrder = 'desc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);
            $memberId = $request->member_id;

            if (! empty($request->ticket_status)) {
                $query->whereTicketStatus($request->ticket_status);
            }
            if (! empty($request->ticket_no)) {
                $query->whereTicketNo($request->ticket_no);
            }
            if (! empty($request->id)) {
                $query->whereId($request->id);
            }

            $query->when($request->date_range, function ($query) use ($request) {
                $dates = explode('-', $request->date_range);
                $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                if (! empty($dateFrom) && ! empty($dateTo)) {
                    $query->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);
                } elseif (! empty($dateFrom) && empty($dateTo)) {
                    $query->where(DB::raw('DATE(created_at)'), $dateFrom);
                } elseif (empty($dateFrom) && ! empty($dateTo)) {
                    $query->where(DB::raw('DATE(created_at)'), $dateTo);
                }
            });
            $results = $query->withSum('ticketSlaves as big_bet_amount', 'big_bet_amount')
            ->withSum('ticketSlaves as small_bet_amount', 'small_bet_amount')
            ->withSum('ticketSlaves as three_a_amount', 'three_a_amount')
            ->withSum('ticketSlaves as three_c_amount', 'three_c_amount')
            ->withSum('ticketSlaves as bet_amount', 'bet_amount')
            ->withSum('ticketSlaves as rebate_amount', 'rebate_amount')
            ->withSum('ticketSlaves as bet_net_amount', 'bet_net_amount')
            ->withSum('ticketSlaves as winning_amount', 'winning_amount')
            ->whereMemberId($memberId)
            ->paginate($perPage, ['*'], 'page', $page);

            if (! empty($results)) {
                $datas = $results->toArray();
                foreach ($datas['data'] as $key => $value) {
                    if (! empty($this->getAllGames($value['id']))) {
                        $datas['data'][$key]['games'] = $this->getAllGames($value['id']);
                        $datas['data'][$key]['games'] = ! empty($this->getAllGames($value['id'])) ? array_values(array_unique($datas['data'][$key]['games'])) : [];
                    } else {
                        $datas['data'][$key]['games'] = [];
                    }
                }
                $result['success'] = true;
                if (! empty($datas)) {
                    $result['messsage'] = 'Bet Details fetched successfully';
                    $result += $datas;
                } else {
                    $result['messsage'] = 'No Data';
                }
            }

            return response()->json($result, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getAllGames($id)
    {
        $ticketslaves = TicketSlave::whereTicketId($id)->get();
        $response = [];
        if (! empty($ticketslaves)) {
            foreach ($ticketslaves as $key => $value) {
                $response[] = ! empty(GamePlay::whereId($value['game_play_id'])->first()) ? GamePlay::whereId($value['game_play_id'])->first() : '';
            }
        }

        return $response;
    }

    public function betListById($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 1000;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'id';
            //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $sortOrder = 'desc';

            $query = (new TicketSlave())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->id, function ($query) use ($request) {
                $query->where('ticket_id', $request->id);
            });
            $query->when($request->child_ticket_no, function ($query) use ($request) {
                $query->where('child_ticket_no', $request->child_ticket_no);
            });
            $query->when($request->game_type, function ($query) use ($request) {
                $query->where('game_type', $request->game_type);
            });
            $query->when($request->game_play_id, function ($query) use ($request) {
                $query->where('game_play_id', $request->game_play_id);
            });

            $results = $query->with(['ticket', 'game'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function betListDetails($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'ticket_slaves.id';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new TicketSlave())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->member_id, function ($query) use ($request) {
                $query->select(['ticket_slaves.*', 'odd_settings.*'])
                      ->leftJoin('tickets', 'tickets.id', '=', 'ticket_slaves.ticket_id')
                      ->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                      ->where('tickets.member_id', $request->member_id);
            });
            if ($request->is('frontend-api/betListWinning') == true) {
                $query->where('ticket_slaves.status', 'finished');
                $query->where('ticket_slaves.winning_amount', '>', '0');
            }
            if ($request->is('frontend-api/betListReject') == true) {
                $query->where('ticket_slaves.progress_status', 'REJECTED');
            }
            if (! empty($request->prize_type)) {
                $query->where('ticket_slaves.prize_type', $request->prize_type);
            }

            $query->when($request->date_range, function ($query) use ($request) {
                $dates = explode('-', $request->date_range);
                $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                if (! empty($dateFrom) && ! empty($dateTo)) {
                    $query->whereBetween(DB::raw('DATE(tickets.created_at)'), [$dateFrom, $dateTo]);
                } elseif (! empty($dateFrom) && empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateFrom);
                } elseif (empty($dateFrom) && ! empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateTo);
                }
            });

            if (! empty($request->ticket_no)) {
                $query->whereChildTicketNo($request->ticket_no);
            }

            $results = $query->leftJoin('odd_settings', function ($join) {
                $join->on('odd_settings.game_play_id', '=', 'ticket_slaves.game_play_id');
                $join->on('merchants.market_id', '=', 'odd_settings.market_id');
            })->with(['ticket', 'game'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function checkTicketStatus($request)
    {
        $ticket = Ticket::where('ticket_status', $request->status)->where('merchant_id', $request->merchantid)->get()->first();
        if (! empty($ticket)) {
            return response()->json([
                'status' => true,
                'data' => $ticket,
                'messages' => ['Ticket have unsettled'],
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'messages' => ['Ticket do not have unsettled'],
            ], 200);
        }
    }

public function fetchBettingReport($request): JsonResponse
{
    try {
        $perPage = $request->rowsPerPage ?: 1000;
        $page = $request->page ?: 1;
        $sortBy = $request->sortBy ?: 'tickets.id';
        //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
        $sortOrder = 'desc';

        $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

        if (! empty($request->ticket_status)) {
            $query->whereTicketStatus($request->ticket_status);
        }
        if (! empty($request->ticket_no)) {
            $query->whereTicketNo($request->ticket_no);
        }
        if (! empty($request->merchant_id)) {
            $query->where('tickets.merchant_id', $request->merchant_id);
        }
        if (! empty($request->customer_id)) {
            $query->where('members.customer_id', $request->customer_id);
        }

        $query->when($request->date_range, function ($query) use ($request) {
            $dates = explode('-', $request->date_range);
            $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
            $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

            if (! empty($dateFrom) && ! empty($dateTo)) {
                $query->whereBetween(DB::raw('DATE(tickets.created_at)'), [$dateFrom, $dateTo]);
            } elseif (! empty($dateFrom) && empty($dateTo)) {
                $query->where(DB::raw('DATE(tickets.created_at)'), $dateFrom);
            } elseif (empty($dateFrom) && ! empty($dateTo)) {
                $query->where(DB::raw('DATE(tickets.created_at)'), $dateTo);
            }
        });

        $selectData = [

            'tickets.id as ticket_id',
            'tickets.merchant_id',
            'merchants.code as merchant_code',
            'tickets.ticket_no',
            'tickets.bet_number',
            'tickets.bet_type',
            'tickets.total_amount',
            'tickets.net_amount',
            'tickets.rebate_amount',
            'tickets.rebate_percentage',
            'tickets.draw_date',
            'tickets.draw_number',
            'tickets.ticket_status',
            'tickets.message',
            'members.customer_id',
            'members.customer_name',

        ];
        $results = $query->select($selectData)
        ->leftJoin('members', 'members.id', '=', 'tickets.member_id')
        ->leftJoin('merchants', 'merchants.id', '=', 'members.merchant_id')
        ->get();
        if (! empty($results)) {
            foreach ($results as $key => $value) {
                $select = [

                    'game_play_id',
                    'child_ticket_no',
                    'lottery_number',
                    'big_bet_amount',
                    'small_bet_amount',
                    'three_a_amount',
                    'three_c_amount',
                    'bet_amount',
                    'bet_net_amount',
                    'rebate_amount',
                    'rebate_percentage',
                    'big_3a_amount',
                    'small_3c_amount',
                    'game_type',
                    'prize_type',
                    'winning_amount',
                    'progress_status',
                    'created_at',
                ];
                $results[$key]['ticket_slaves'] = DB::table('ticket_slaves')->select($select)->where(['ticket_id' => $value->ticket_id])->get();
                //$results[$key]['game'] =$this->getGame($value->id,$request);
            }
            $result['success'] = true;
            if (! empty($results)) {
                $result['messsage'] = 'Retrieved lottery detail list  fetched successfully';
                $result['data'] = $results;
            } else {
                $result['messsage'] = 'No Data';
            }
        }

        return response()->json($result, 200);
    } catch (\Exception$e) {
        return generalErrorResponse($e);
    }
}

    public function getGame($id, $request)
    {
        $array = [];
        $ticketSlaves = DB::table('ticket_slaves')->where(['ticket_id' => $id])->get();
        if (! empty($ticketSlaves)) {
            foreach ($ticketSlaves as $key => $value) {
                if (! empty($request->game_play_id)) {
                    $result = DB::table('game_plays')->select(['id', 'name', 'abbreviation'])->where(['id' => $request->game_play_id])->first();
                } else {
                    $result = DB::table('game_plays')->select(['id', 'name', 'abbreviation'])->where(['id' => $value->game_play_id])->first();
                }

                if (! in_array($result, $array)) {
                    $array[] = $result;
                }
            }
        }

        return $array;
    }

public function fetchBettingReportByCustomerId($request): JsonResponse
{
    try {
        $perPage = $request->rowsPerPage ?: 1000;
        $page = $request->page ?: 1;
        $sortBy = $request->sortBy ?: 'tickets.id';
        //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
        $sortOrder = 'desc';

        $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);
        $memberId = $request->member_id;

        if (! empty($request->ticket_status)) {
            $query->whereTicketStatus($request->ticket_status);
        }
        if (! empty($request->ticket_no)) {
            $query->whereTicketNo($request->ticket_no);
        }
        if (! empty($request->id)) {
            $query->where('tickets.id', $request->id);
        }
        if (! empty($request->customer_id)) {
            $query->where('members.customer_id', '=', $request->customer_id);
        }

        $query->when($request->date_range, function ($query) use ($request) {
            $dates = explode('-', $request->date_range);
            $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
            $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

            if (! empty($dateFrom) && ! empty($dateTo)) {
                $query->whereBetween(DB::raw('DATE(tickets.created_at)'), [$dateFrom, $dateTo]);
            } elseif (! empty($dateFrom) && empty($dateTo)) {
                $query->where(DB::raw('DATE(tickets.created_at)'), $dateFrom);
            } elseif (empty($dateFrom) && ! empty($dateTo)) {
                $query->where(DB::raw('DATE(tickets.created_at)'), $dateTo);
            }
        });

        $results = $query->leftJoin('members', 'members.id', '=', 'tickets.member_id');
        if (! empty($results)) {
            foreach ($results as $key => $value) {
                $results[$key]['ticket_slaves'] = DB::table('ticket_slaves')->where(['ticket_id' => $value->id])->get();
                $results[$key]['game'] = $this->getGame($value->id, $request);
            }
            $result['success'] = true;
            if (! empty($results)) {
                $result['messsage'] = 'Retrieved lottery detail list  fetched successfully';
                $result['data'] = $results;
            } else {
                $result['messsage'] = 'No Data';
            }
        }

        return response()->json($result, 200);
    } catch (\Exception$e) {
        return generalErrorResponse($e);
    }
}

    public function fetchBettingReportByRefNumber($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 1000;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'tickets.id';
            //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $sortOrder = 'desc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);
            $memberId = $request->member_id;

            if (! empty($request->ticket_status)) {
                $query->whereTicketStatus($request->ticket_status);
            }
            if (! empty($request->ticket_no)) {
                $query->whereTicketNo($request->ticket_no);
            }
            if (! empty($request->id)) {
                $query->where('tickets.id', $request->id);
            }
            if (! empty($request->customer_id)) {
                $query->where('members.customer_id', '=', $request->customer_id);
            }

            $query->when($request->date_range, function ($query) use ($request) {
                $dates = explode('-', $request->date_range);
                $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                if (! empty($dateFrom) && ! empty($dateTo)) {
                    $query->whereBetween(DB::raw('DATE(tickets.created_at)'), [$dateFrom, $dateTo]);
                } elseif (! empty($dateFrom) && empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateFrom);
                } elseif (empty($dateFrom) && ! empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateTo);
                }
            });

            $results = $query->leftJoin('members', 'members.id', '=', 'tickets.member_id');
            if (! empty($results)) {
                foreach ($results as $key => $value) {
                    $results[$key]['ticket_slaves'] = DB::table('ticket_slaves')->where(['ticket_id' => $value->id])->get();
                    $results[$key]['game'] = $this->getGame($value->id, $request);
                }
                $result['success'] = true;
                if (! empty($results)) {
                    $result['messsage'] = 'Retrieved lottery detail list  fetched successfully';
                    $result['data'] = $results;
                } else {
                    $result['messsage'] = 'No Data';
                }
            }

            return response()->json($result, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function fetchBettingReportByDate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 1000;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'tickets.id';
            //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $sortOrder = 'desc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);
            $memberId = $request->member_id;

            if (! empty($request->ticket_status)) {
                $query->whereTicketStatus($request->ticket_status);
            }
            if (! empty($request->ticket_no)) {
                $query->whereTicketNo($request->ticket_no);
            }
            if (! empty($request->id)) {
                $query->where('tickets.id', $request->id);
            }
            if (! empty($request->customer_id)) {
                $query->where('members.customer_id', '=', $request->customer_id);
            }

            $query->when($request->date_range, function ($query) use ($request) {
                $dates = explode('-', $request->date_range);
                $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                if (! empty($dateFrom) && ! empty($dateTo)) {
                    $query->whereBetween(DB::raw('DATE(tickets.created_at)'), [$dateFrom, $dateTo]);
                } elseif (! empty($dateFrom) && empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateFrom);
                } elseif (empty($dateFrom) && ! empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateTo);
                }
            });

            $results = $query->leftJoin('members', 'members.id', '=', 'tickets.member_id');
            if (! empty($results)) {
                foreach ($results as $key => $value) {
                    $results[$key]['ticket_slaves'] = DB::table('ticket_slaves')->where(['ticket_id' => $value->id])->get();
                    $results[$key]['game'] = $this->getGame($value->id, $request);
                }
                $result['success'] = true;
                if (! empty($results)) {
                    $result['messsage'] = 'Retrieved lottery detail list  fetched successfully';
                    $result['data'] = $results;
                } else {
                    $result['messsage'] = 'No Data';
                }
            }if (! empty($results)) {
                foreach ($results as $key => $value) {
                    $results[$key]['ticket_slaves'] = DB::table('ticket_slaves')->where(['ticket_id' => $value->id])->get();
                    $results[$key]['game'] = $this->getGame($value->id, $request);
                }
                $result['success'] = true;
                if (! empty($results)) {
                    $result['messsage'] = 'Retrieved lottery detail list  fetched successfully';
                    $result['data'] = $results;
                } else {
                    $result['messsage'] = 'No Data';
                }
            }

            return response()->json($result, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function fetchWinningReport($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 1000;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'tickets.id';
            //$sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $sortOrder = 'desc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            if (! empty($request->ticket_status)) {
                $query->whereTicketStatus($request->ticket_status);
            }
            if (! empty($request->ticket_no)) {
                $query->whereTicketNo($request->ticket_no);
            }
            if (! empty($request->id)) {
                $query->where('tickets.id', $request->id);
            }
            if (! empty($request->customer_id)) {
                $query->where('members.customer_id', '=', $request->customer_id);
            }

            $query->when($request->date_range, function ($query) use ($request) {
                $dates = explode('-', $request->date_range);
                $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                if (! empty($dateFrom) && ! empty($dateTo)) {
                    $query->whereBetween(DB::raw('DATE(tickets.created_at)'), [$dateFrom, $dateTo]);
                } elseif (! empty($dateFrom) && empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateFrom);
                } elseif (empty($dateFrom) && ! empty($dateTo)) {
                    $query->where(DB::raw('DATE(tickets.created_at)'), $dateTo);
                }
            });

            $gamePlayId = ! empty($request->game_play_id) ? $request->game_play_id : '';
            $results = $query->leftJoin('members', 'members.id', '=', 'tickets.member_id')
            ->with(['ticketSlaves' => function ($query) use ($gamePlayId) {
                $query->where('status', 'finished')->where('winning_amount', '>', '0');
                if (! empty($gamePlayId)) {
                    $query->whereGamePlayId($gamePlayId);
                }
            }, 'ticketSlaves.game'])
            ->paginate($perPage, ['*'], 'page', $page);

            if (! empty($results)) {
                $datas = $results->toArray();

                $result['success'] = true;
                if (! empty($datas)) {
                    $result['messsage'] = 'Retrieved lottery detail list  fetched successfully';
                    $result += $datas;
                } else {
                    $result['messsage'] = 'No Data';
                }
            }

            return response()->json($result, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
