<?php

namespace App\Services;

use App\Jobs\DrawJob;
use App\Jobs\ResultJob;
use App\Models\Member;
use App\Models\Merchant;
use App\Models\Result;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'id';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $query = (new Result())->newQuery()->orderBy($sortBy, $sortOrder);
            $query->when($request->game_play_id, function ($query) use ($request) {
                $query->where('game_play_id', $request->game_play_id);
            });
            $query->when($request->date_from, function ($query) use ($request) {
                $query->where('fetching_date', '>=', $request->date_from);
            });
            $query->when($request->date_to, function ($query) use ($request) {
                $query->where('fetching_date', '<=', $request->date_to);
            });

            $results = $query->with(['merchant', 'product', 'gamePlay'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            Result::create($data);

            return response()->json([
                'messages' => ['Result created successfully'],
            ], 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function update($resultId, array $data): JsonResponse
    {
        //info( '"$result_id"');
        try {
            if ($result = Result::find($resultId)) {
                $result->update($data);
                $result_id = $data['id'];
            } else {
                $id = Result::create($data);
                $result_id = $id->id;
                // echo $result_id;die;
            }
            //info( $result_id);
            // DB::enableQueryLog();
            $result_data = Result::where('id', $result_id)->first();
            // info(DB::getQueryLog());die;
            if (!empty($result_data)) {
                $fetching_date = $result_data->fetching_date;
                $game_play_id = $result_data->game_play_id;
                $draw_number = $result_data->reference_number;
                $this->lotterySettled($fetching_date, $result_id, $draw_number);
                if ($result_data->prize1 != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '4D')
                        ->where('lottery_number', $result_data->prize1)
                        ->where('game_play_id', $game_play_id)
                        ->update(['prize_type' => 'P1']);

                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '3D')
                        ->where('lottery_number', substr($result_data->prize1, -3))
                        ->where('game_play_id', $game_play_id)
                        ->update(['prize_type' => 'P1']);
                }
                if ($result_data->prize2 != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '4D')
                        ->where('lottery_number', $result_data->prize2)
                        ->where('game_play_id', $game_play_id)
                        ->update(['prize_type' => 'P2']);

                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '3D')
                        //->where('bet_size', '3C')
                        ->where('bet_size', '!=', '3A')
                        ->where('lottery_number', substr($result_data->prize2, -3))
                        ->where('game_play_id', $game_play_id)
                        ->update(['prize_type' => 'P2']);
                }
                if ($result_data->prize3 != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '4D')
                        ->where('lottery_number', $result_data->prize3)
                        ->where('game_play_id', $game_play_id)
                        ->update(['prize_type' => 'P3']);

                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '3D')
                        //->where('bet_size', '3C')
                        ->where('bet_size', '!=', '3A')
                        ->where('lottery_number', substr($result_data->prize3, -3))
                        ->where('game_play_id', $game_play_id)
                        ->update(['prize_type' => 'P3']);
                }
                if ($result_data->special1 != '' || $result_data->special2 != '' || $result_data->special3 != '' || $result_data->special4 != '' || $result_data->special5 != '' || $result_data->special6 != '' || $result_data->special7 != '' || $result_data->special8 != '' || $result_data->special9 != '' || $result_data->special9 != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '4D')
                        //->where('lottery_number',$result_data->special1)
                        ->where(function ($query1) use ($result_data) {
                            $query1->where('lottery_number', $result_data->special1)
                                ->orWhere('lottery_number', $result_data->special2)
                                ->orWhere('lottery_number', $result_data->special3)
                                ->orWhere('lottery_number', $result_data->special4)
                                ->orWhere('lottery_number', $result_data->special5)
                                ->orWhere('lottery_number', $result_data->special6)
                                ->orWhere('lottery_number', $result_data->special7)
                                ->orWhere('lottery_number', $result_data->special8)
                                ->orWhere('lottery_number', $result_data->special9)
                                ->orWhere('lottery_number', $result_data->special10);
                        })
                        ->where('game_play_id', $game_play_id)
                        ->where(function ($query) {
                            $query->where('bet_size', 'Big')
                                ->orWhere('bet_size', 'Both');
                        })
                        ->update(['prize_type' => 'S']);
                }

                if ($result_data->consolation1 != '' || $result_data->consolation2 != '' || $result_data->consolation3 != '' || $result_data->consolation4 != '' || $result_data->consolation5 != '' || $result_data->consolation6 != '' || $result_data->consolation7 != '' || $result_data->consolation8 != '' || $result_data->consolation9 != '' || $result_data->consolation10 != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('tickets.betting_date', $fetching_date)
                        ->where('game_type', '4D')
                        //->where('lottery_number',$result_data->consolation1)
                        ->where(function ($query1) use ($result_data) {
                            $query1->where('lottery_number', $result_data->consolation1)
                                ->orWhere('lottery_number', $result_data->consolation2)
                                ->orWhere('lottery_number', $result_data->consolation3)
                                ->orWhere('lottery_number', $result_data->consolation4)
                                ->orWhere('lottery_number', $result_data->consolation5)
                                ->orWhere('lottery_number', $result_data->consolation6)
                                ->orWhere('lottery_number', $result_data->consolation7)
                                ->orWhere('lottery_number', $result_data->consolation8)
                                ->orWhere('lottery_number', $result_data->consolation9)
                                ->orWhere('lottery_number', $result_data->consolation10);
                        })
                        ->where('game_play_id', $game_play_id)
                        ->where(function ($query) {
                            $query->where('bet_size', 'Big')
                                ->orWhere('bet_size', 'Both');
                        })
                        ->update(['prize_type' => 'C']);
                }
            }
            // DB::enableQueryLog();
            //$ticket_slaves = DB::table('ticket_slaves')->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
            //->where('game_play_id', $game_play_id)->where('tickets.betting_date', $fetching_date)->get();
            $ticket_slaves = DB::table('ticket_slaves')->where('game_play_id', $game_play_id)->where('betting_date', $fetching_date)->get();
            // info(DB::getQueryLog());die;
            if ($ticket_slaves->count() > 0) {
                $counter = 0;
                $previousId = '';
                foreach ($ticket_slaves as $val) {
                    $amount = $val->bet_amount;
                    $net_amount = $val->bet_net_amount;
                    $cashback_balance = $amount - $net_amount;
                    $cashback_balance_round = round($cashback_balance, 2);
                    $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                    $merchantId = $tickets->merchant_id;
                    //DB::enableQueryLog();
                    $merchants = Merchant::with(['market', 'market.oddSettings' => function ($q) use ($game_play_id) {
                        $q->whereGamePlayId($game_play_id);
                    }])
                        ->whereId($merchantId)->first();
                    //info(DB::getQueryLog());die;
                    //Log::info($merchants->market->oddSettings[0]->small_first);

                    if ($val->bet_size == 'Small') {
                        $first_prize = $merchants->market->oddSettings[0]->small_first;
                        $second_prize = $merchants->market->oddSettings[0]->small_second;
                        $third_prize = $merchants->market->oddSettings[0]->small_third;
                        $special_prize = $merchants->market->oddSettings[0]->small_special;
                        $consolation_prize = $merchants->market->oddSettings[0]->small_consolation;
                    } elseif ($val->bet_size == '3A') {
                        $first_prize = $merchants->market->oddSettings[0]->three_a_first;
                        $second_prize = 1;
                        $third_prize = 1;
                    } elseif ($val->bet_size == '3C') {
                        $first_prize = $merchants->market->oddSettings[0]->three_c_first;
                        $second_prize = $merchants->market->oddSettings[0]->three_c_second;
                        $third_prize = $merchants->market->oddSettings[0]->three_c_third;
                    } elseif ($val->bet_size == 'Both' && $val->game_type == '4D') {
                        $first_prize_big = $merchants->market->oddSettings[0]->big_first;
                        $first_prize_small = $merchants->market->oddSettings[0]->small_first;
                        $second_prize_big = $merchants->market->oddSettings[0]->big_second;
                        $second_prize_small = $merchants->market->oddSettings[0]->small_second;
                        $third_prize_big = $merchants->market->oddSettings[0]->big_third;
                        $third_prize_small = $merchants->market->oddSettings[0]->small_third;
                        $special_prize = $merchants->market->oddSettings[0]->big_special;
                        $consolation_prize = $merchants->market->oddSettings[0]->big_consolation;
                        $third_prize = 1;
                    } elseif ($val->bet_size == 'Both' && $val->game_type == '3D') {
                        $first_prize_big = $merchants->market->oddSettings[0]->three_c_first;
                        $first_prize_small = $merchants->market->oddSettings[0]->three_a_first;
                        $second_prize_big = $merchants->market->oddSettings[0]->three_c_second;
                        $second_prize_small = 1;
                        $third_prize_big = $merchants->market->oddSettings[0]->three_c_third;
                        $third_prize_small = 1;
                        $third_prize = 1;
                    } elseif ($val->bet_size == 'Big' && $val->game_type == '4D') {
                        $first_prize = $merchants->market->oddSettings[0]->big_first;
                        $second_prize = $merchants->market->oddSettings[0]->big_second;
                        $third_prize = $merchants->market->oddSettings[0]->big_third;
                        $special_prize = $merchants->market->oddSettings[0]->big_special;
                        $consolation_prize = $merchants->market->oddSettings[0]->big_consolation;
                    }

                    //DB::enableQueryLog();
                    //DB::table('wallets')->where('member_id', $tickets->member_id)->increment('cashback_balance', $cashback_balance_round);

                    if ($previousId !== '' && $previousId !== $val->ticket_id) {
                        DB::table('ticket_settlements')->insert(['ticket_id' => $val->ticket_id, 'member_id' => $tickets->member_id, 'amount' => $cashback_balance_round, 'type' => 'Rebate']);
                        DB::table('ticket_settlements')->insert(['ticket_id' => $val->ticket_id, 'member_id' => $tickets->member_id, 'amount' => 0, 'type' => 'Winning']);
                    }
                    $previousId = $val->ticket_id;

                    //info('scddsds');
                    //info(DB::getQueryLog());die;
                    DB::table('wallets')->where('member_id', $tickets->member_id)->increment('cashback_balance', $cashback_balance_round);
                    if ($val->prize_type == 'No') {
                        DB::table('ticket_slaves')->where('id', $val->id)->update(['winning_amount' => 0]);
                        $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                        //DB::table('wallets')->where('member_id', $tickets->member_id)->increment('winning_balance', 0);
                        DB::table('ticket_settlements')->where('ticket_id', $val->ticket_id)->where('type', 'Winning')->increment('amount', 0);
                    } elseif ($val->prize_type == 'P1') {
                        // DB::enableQueryLog();
                        if ($val->bet_size == 'Both') {
                            if ($val->game_type == '4D') {
                                $total_amount_p1 = $first_prize_big * $val->big_bet_amount + $first_prize_small * $val->small_bet_amount;
                            } else {
                                $total_amount_p1 = $first_prize_big * $val->three_a_amount + $first_prize_small * $val->three_c_amount;
                            }
                        } else {
                            $total_amount_p1 = $first_prize * $val->bet_amount;
                        }

                        DB::table('ticket_slaves')->where('id', $val->id)->update(['winning_amount' => $total_amount_p1]);
                        if ($val->game_type == '4D') {
                            if ($val->bet_size == 'Big') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_first]);
                            } elseif ($val->bet_size == 'Small') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_first]);
                            } else {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_first]);
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_first]);
                            }
                        } else {
                            if ($val->bet_size == '3A') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->three_a_first]);
                            } elseif ($val->bet_size == '3C') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->three_c_first]);
                            } else {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->three_a_first]);
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->three_c_first]);
                            }
                        }

                        $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                        //DB::table('wallets')->where('member_id', $tickets->member_id)->increment('winning_balance', $total_amount_p1);
                        DB::table('ticket_settlements')->where('ticket_id', $val->ticket_id)->where('type', 'Winning')->increment('amount', $total_amount_p1);
                        //info( '"$first_prize"');
                        //info(DB::getQueryLog());die;
                    } elseif ($val->prize_type == 'P2') {
                        if ($val->bet_size == 'Both') {
                            if ($val->game_type == '4D') {
                                $total_amount_p2 = $second_prize_big * $val->big_bet_amount + $second_prize_small * $val->small_bet_amount;
                            } else {
                                $total_amount_p2 = $second_prize_big * $val->three_c_amount;
                            }
                        } else {
                            $total_amount_p2 = $second_prize * $val->bet_amount;
                        }

                        // DB::enableQueryLog();
                        // echo $second_prize;
                        // echo $val->bet_amount;
                        DB::table('ticket_slaves')->where('id', $val->id)->update(['winning_amount' => $total_amount_p2]);

                        if ($val->game_type == '4D') {
                            if ($val->bet_size == 'Big') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_second]);
                            } elseif ($val->bet_size == 'Small') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_second]);
                            } else {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_second]);
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_second]);
                            }
                        } else {
                            if ($val->bet_size == '3A') {
                                //DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->three_a_first]);
                            } elseif ($val->bet_size == '3C') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->three_c_second]);
                            } else {
                                // DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->three_a_first]);
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->three_c_first]);
                            }
                        }

                        $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                        // DB::table('wallets')->where('member_id', $tickets->member_id)->increment('winning_balance', $total_amount_p2);
                        DB::table('ticket_settlements')->where('ticket_id', $val->ticket_id)->where('type', 'Winning')->increment('amount', $total_amount_p2);
                        //info(DB::getQueryLog());die;
                    } elseif ($val->prize_type == 'P3') {
                        if ($val->bet_size == 'Both') {
                            if ($val->game_type == '4D') {
                                $total_amount_p3 = $third_prize_big * $val->big_bet_amount + $third_prize_small * $val->small_bet_amount;
                            } else {
                                $total_amount_p3 = $third_prize_big * $val->three_c_amount;
                            }
                        } else {
                            $total_amount_p3 = $third_prize * $val->bet_amount;
                        }
                        // DB::enableQueryLog();

                        DB::table('ticket_slaves')->where('id', $val->id)->update(['winning_amount' => $total_amount_p3]);

                        if ($val->game_type == '4D') {
                            if ($val->bet_size == 'Big') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_third]);
                            } elseif ($val->bet_size == 'Small') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_third]);
                            } else {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_third]);
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_third]);
                            }
                        } else {
                            if ($val->bet_size == '3A') {
                                //DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->three_a_first]);
                            } elseif ($val->bet_size == '3C') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->three_c_third]);
                            } else {
                                // DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->three_a_first]);
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->three_c_third]);
                            }
                        }

                        $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                        //DB::table('wallets')->where('member_id', $tickets->member_id)->increment('winning_balance', $total_amount_p3);
                        DB::table('ticket_settlements')->where('ticket_id', $val->ticket_id)->where('type', 'Winning')->increment('amount', $total_amount_p3);
                        //info($third_prize);
                        // info(DB::getQueryLog());die;
                    } elseif ($val->prize_type == 'S') {
                        $total_amount_s = $special_prize * $val->big_bet_amount;
                        DB::table('ticket_slaves')->where('id', $val->id)->update(['winning_amount' => $total_amount_s]);

                        if ($val->game_type == '4D') {
                            if ($val->bet_size == 'Big') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_special]);
                            } elseif ($val->bet_size == 'Small') {
                                //DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_third]);
                            } else {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_special]);
                                // DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_third]);
                            }
                        }
                        //$tickets = DB::table('tickets')->where('id', $val->member_id)->first();
                        $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                        // DB::table('wallets')->where('member_id', $tickets->member_id)->increment('winning_balance', $total_amount_s);
                        DB::table('ticket_settlements')->where('ticket_id', $val->ticket_id)->where('type', 'Winning')->increment('amount', $total_amount_s);
                    } elseif ($val->prize_type == 'C') {
                        $total_amount_c = $consolation_prize * $val->big_bet_amount;
                        DB::table('ticket_slaves')->where('id', $val->id)->update(['winning_amount' => $total_amount_c]);
                        if ($val->game_type == '4D') {
                            if ($val->bet_size == 'Big') {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_consolation]);
                            } elseif ($val->bet_size == 'Small') {
                                //DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_third]);
                            } else {
                                DB::table('ticket_slaves')->where('id', $val->id)->update(['big_3a_amount' => $merchants->market->oddSettings[0]->big_consolation]);
                                // DB::table('ticket_slaves')->where('id', $val->id)->update(['small_3c_amount' => $merchants->market->oddSettings[0]->small_third]);
                            }
                        }
                        //$tickets = DB::table('tickets')->where('id', $val->member_id)->first();
                        $tickets = DB::table('tickets')->where('id', $val->ticket_id)->first();
                        // DB::table('wallets')->where('member_id', $tickets->member_id)->increment('winning_balance', $total_amount_c);
                        DB::table('ticket_settlements')->where('ticket_id', $val->ticket_id)->where('type', 'Winning')->increment('amount', $total_amount_c);
                    }
                    $counter = $counter + 1;
                }
            }

            $ticket_settlements = DB::table('ticket_settlements')->where('transaction_id', 0)->get();
            if ($ticket_settlements->count() > 0) {
                foreach ($ticket_settlements as $ticket_settlement) {
                    $member_id = $ticket_settlement->member_id;
                    $amount = $ticket_settlement->amount;
                    $ticket_id = $ticket_settlement->ticket_id;
                    $type = $ticket_settlement->type;

                    $tickets = DB::table('tickets')->where('id', $ticket_id)->first();
                    $merchantId = $tickets->merchant_id;
                    $ticket_no = $tickets->ticket_no;

                    $currencies_code = DB::table('merchants')->join('currencies', 'merchants.currency_id', '=', 'currencies.id')->where('merchants.id', $merchantId)->first();

                    DB::table('wallets')->where('member_id', $member_id)->increment('amount', $amount);
                    //add transaction record
                    $LastNumber = DB::table('transactions')->max('id') + 1;
                    $append = zeroappend($LastNumber);
                    $transactionNo = 'TRA' . $append . $LastNumber;

                    $transactionData['transaction_id'] = $transactionNo;
                    $transactionData['member_id'] = $member_id;
                    $transactionData['merchant_id'] = $merchantId;
                    $transactionData['transaction_type'] = 'Credit';
                    if ($type == 'Winning') {
                        $transactionData['transaction_from'] = 'pay-out';
                    } else {
                        $transactionData['transaction_from'] = 'rebate';
                    }

                    $transactionData['amount'] = $amount;
                    $transactionData['currency'] = $currencies_code->code;
                    $transactionData['message'] = $type . ' Amount credited against ticket id: ' . $ticket_no;
                    $transaction_id = Transaction::create($transactionData);
                    $tran_id = $transaction_id->id;
                    DB::table('ticket_settlements')->where('id', $ticket_settlement->id)->update(['transaction_id' => $tran_id]);
                }
            }

            //$result->update($data);
            return response()->json([
                'messages' => ['Result updated successfully'],
            ], 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($result): JsonResponse
    {
        try {
            return response()->json([
                'messages' => ['Result deleted successfully'],
            ], 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function getLatestResult($id, $status): JsonResponse
    {
        try {
            if ($status == 'all') {
                $result = Result::orderBy('id', 'desc')->first();
            } else {
                $result = Result::orderBy('id', 'desc')->where('confirm', $status)->first();
            }

            if (!empty($result)) {
                if ($status == 'all') {
                    $results = Result::with(['gamePlay'])->whereDate('fetching_date', $result->fetching_date)->orderBy('id', 'desc')->get();
                } else {
                    $results = Result::with(['gamePlay'])->where('confirm', $status)->whereDate('fetching_date', $result->fetching_date)->orderBy('id', 'desc')->get();
                }

                return response()->json([
                    'data' => $results,
                    'success' => true,
                    'messages' => 'Latest result fetched successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'data' => [$status],
                    'messages' => 'No Results',
                ], 200);
            }
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function lotterySettled($fetching_date, $result_id, $draw_number)
    {
        try {
            //DB::enableQueryLog();
            DB::table('results')->where('id', $result_id)
                ->update(['confirm' => 'Yes']);
            DB::table('special_draws')->where('draw_date', $fetching_date)
                ->update(['status' => 'drawn']);
            DB::table('ticket_slaves')
                ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                ->where('tickets.betting_date', $fetching_date)
                //->where('ticket_slaves.ticket_id', $fetching_date_new)
                ->update(['ticket_slaves.status' => 'finished', 'ticket_slaves.updated_at' => Carbon::now()]);
            // dd(DB::getQueryLog());
            DB::table('tickets')->where('betting_date', $fetching_date)
                ->update(['ticket_status' => 'SETTLED', 'draw_number' => $draw_number, 'draw_date' => $fetching_date]);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function confirm($result, $data): JsonResponse
    {
        try {
            $fetching_date = $data['fetching_date'];
            $game_play_id = $data['game_play_id'];
            //$merchant_id = $data['merchant_id'];
            // $merchants = DB::table('merchants')->where('id', $merchant_id)->where('game_play_id', $game_play_id)->get();

            // $first_prize = $merchants[0]->first_prize;
            // $second_prize = $merchants[0]->second_prize;
            // $third_prize = $merchants[0]->third_prize;
            // $special_prize = $merchants[0]->special_prize;
            // $consolation_prize = $merchants[0]->consolation_prize;
            // print_r($merchants[0]->first_prize);die;

            $special_prize = 1;
            $consolation_prize = 1;
            $first_prize = 1;
            $second_prize = 1;
            $third_prize = 1;
            $data->confirm = 'Yes';
            if ($data->save()) {
                //$this->lotterySettled($fetching_date);
                //start code to change prize type and announcement date
                if ($data['prize1'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['prize1'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        ->update(['prize_type' => 'P1']);
                }
                if ($data['prize2'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['prize2'])
                        ->where('game_play_id', $game_play_id)
                        // ->where('ticket_slaves.merchant_id', $merchant_id)
                        ->update(['prize_type' => 'P2']);
                }
                if ($data['prize3'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['prize3'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        ->update(['prize_type' => 'P3']);
                }
                if ($data['special1'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special1'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S1']);
                }
                if ($data['special2'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special2'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S2']);
                }
                if ($data['special3'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special3'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S3']);
                }
                if ($data['special4'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special4'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S4']);
                }
                if ($data['special5'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special5'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S5']);
                }
                if ($data['special6'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special6'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S6']);
                }
                if ($data['special7'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special7'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S7']);
                }
                if ($data['special8'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special8'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S8']);
                }
                if ($data['special9'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special9'])
                        ->where('game_play_id', $game_play_id)
                        // ->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S9']);
                }
                if ($data['special10'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['special10'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'S10']);
                }
                if ($data['consolation1'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation1'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C1']);
                }
                if ($data['consolation2'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation2'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C2']);
                }
                if ($data['consolation3'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation3'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C3']);
                }
                if ($data['consolation4'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation4'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C4']);
                }
                if ($data['consolation5'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation5'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C5']);
                }
                if ($data['consolation6'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation6'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C6']);
                }
                if ($data['consolation7'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation7'])
                        ->where('game_play_id', $game_play_id)
                        // ->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C7']);
                }
                if ($data['consolation8'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation8'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C8']);
                }
                if ($data['consolation9'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation9'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C9']);
                }
                if ($data['consolation10'] != '') {
                    DB::table('ticket_slaves')
                        ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                        ->where('betting_date', $fetching_date)
                        ->where('lottery_number', $data['consolation10'])
                        ->where('game_play_id', $game_play_id)
                        //->where('ticket_slaves.merchant_id', $merchant_id)
                        // ->where(function ($query) {
                        //     $query->where('bet_size', 'Big')
                        //         ->orWhere('bet_size', 'Both');
                        // })
                        ->update(['prize_type' => 'C10']);
                }
                $ticket_slaves = DB::table('ticket_slaves')->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
                    ->where('game_play_id', $game_play_id)->where('betting_date', $fetching_date)->get();
                if ($ticket_slaves->count() > 0) {
                    foreach ($ticket_slaves as $val) {
                        $amount = $val->amount;
                        $net_amount = $val->net_amount;
                        $cashback_balance = $amount - $net_amount;
                        $cashback_balance_round = round($cashback_balance, 2);
                        $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                        // DB::enableQueryLog();
                        DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('cashback_balance', $cashback_balance_round);
                        // print_r(DB::getQueryLog());die;
                        if ($val->prize_type == 'No') {
                            DB::table('ticket_slaves')->where('id', $val->id)->update(['total_amount' => 0]);
                            $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                            DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('winning_balance', 0);
                        } elseif ($val->prize_type == 'P1') {
                            $total_amount_p1 = $first_prize * $val->amount;
                            DB::table('ticket_slaves')->where('id', $val->id)->update(['total_amount' => $total_amount_p1]);
                            $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                            DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('winning_balance', $total_amount_p1);
                        } elseif ($val->prize_type == 'P2') {
                            $total_amount_p2 = $second_prize * $val->amount;
                            DB::table('ticket_slaves')->where('id', $val->id)->update(['total_amount' => $total_amount_p2]);
                            $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                            DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('winning_balance', $total_amount_p2);
                        } elseif ($val->prize_type == 'P3') {
                            $total_amount_p3 = $third_prize * $val->amount;
                            DB::table('ticket_slaves')->where('id', $val->id)->update(['total_amount' => $total_amount_p3]);
                            $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                            DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('winning_balance', $total_amount_p3);
                        } elseif ($val->prize_type == 'S1' || $val->prize_type == 'S2' || $val->prize_type == 'S3' || $val->prize_type == 'S4' || $val->prize_type == 'S5' || $val->prize_type == 'S6' || $val->prize_type == 'S7' || $val->prize_type == 'S8' || $val->prize_type == 'S9' || $val->prize_type == 'S10') {
                            $total_amount_s = $special_prize * $val->big_bet_amount;
                            DB::table('ticket_slaves')->where('id', $val->id)->update(['total_amount' => $total_amount_s]);
                            $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                            DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('winning_balance', $total_amount_s);
                        } elseif ($val->prize_type == 'C1' || $val->prize_type == 'C2' || $val->prize_type == 'C3' || $val->prize_type == 'C4' || $val->prize_type == 'C5' || $val->prize_type == 'C6' || $val->prize_type == 'C7' || $val->prize_type == 'C8' || $val->prize_type == 'C9' || $val->prize_type == 'C10') {
                            $total_amount_c = $consolation_prize * $val->big_bet_amount;
                            DB::table('ticket_slaves')->where('id', $val->id)->update(['total_amount' => $total_amount_c]);
                            $tickets = DB::table('tickets')->where('id', $val->customer_lottery_id)->first();
                            DB::table('wallets')->where('customer_id', $tickets->customer_id)->increment('winning_balance', $total_amount_c);
                        }
                    }
                }
            }

            return response()->json([
                'messages' => ['Result updated successfully'],
            ], 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public static function storeByApi()
    {
        $job = new  ResultJob();
        dispatch($job);

        // $response = file_get_contents("https://check4dresults.com/wp-content/themes/4d/result.php");
        // $resultArray = json_decode($response);
        // foreach ($resultArray as $key => $result) {
        //     if ($key == "M" || $key == "D" || $key == "T") {
        //         if ($key == "M") {
        //             $game_play_id = 1;
        //         } elseif ($key == "D") {
        //             $game_play_id = 2;
        //         } else {
        //             $game_play_id = 3;
        //         }
        //         $special=array();
        //         $special[] = $result->S1;
        //         $special[] = $result->S2;
        //         $special[] = $result->S3;
        //         $special[] = $result->S4;
        //         $special[] = $result->S5;
        //         $special[] = $result->S6;
        //         $special[] = $result->S7;
        //         $special[] = $result->S8;
        //         $special[] = $result->S9;
        //         $special[] = $result->S10;
        //         $special[] = @$result->S11;
        //         $special[] = @$result->S12;
        //         $special[] = @$result->S13;
        //         foreach ($special as $key => $value) {
        //             if (!is_numeric($value)) {
        //                 unset($special[$key]);
        //             }
        //         }
        //         $new_special=array_values($special);
        //         $part=explode(" ",$result->DD);
        //         $fetching_date=date("Y-m-d", strtotime($part['0']) );
        //         $records = Result::where('reference_number', '=', $result->DN)->first();
        //     if ($records === null) {
        //         $data['game_play_id'] = $game_play_id;
        //         $data['fetching_date'] = $fetching_date;
        //         $data['result_date'] = $result->DD;
        //         $data['reference_number'] = $result->DN;
        //         $data['prize1'] = $result->P1;
        //         $data['prize2'] = $result->P2;
        //         $data['prize3'] = $result->P3;
        //         $data['special1'] = $new_special[0];
        //         $data['special2'] = $new_special[1];
        //         $data['special3'] = $new_special[2];
        //         $data['special4'] = $new_special[3];
        //         $data['special5'] = $new_special[4];
        //         $data['special6'] = $new_special[5];
        //         $data['special7'] = $new_special[6];
        //         $data['special8'] = $new_special[7];
        //         $data['special9'] = $new_special[8];
        //         $data['special10'] = $new_special[9];
        //         $data['consolation1'] = $result->C1;
        //         $data['consolation2'] = $result->C2;
        //         $data['consolation3'] = $result->C3;
        //         $data['consolation4'] = $result->C4;
        //         $data['consolation5'] = $result->C5;
        //         $data['consolation6'] = $result->C6;
        //         $data['consolation7'] = $result->C7;
        //         $data['consolation8'] = $result->C8;
        //         $data['consolation9'] = $result->C9;
        //         $data['consolation10'] = $result->C10;
        //         $data['confirm'] = "No";
        //         Result::create($data);
        //     }
        //     }
        // }
        // return response()->json([
        //     'success'=>true,
        //     'messages' => ['Result created successfully'],
        // ], 200);
    }

    public static function updateSpecialDraw()
    {
        info('callled');

        $job = new  DrawJob();
        dispatch($job);
    }

    public function getRecentResult(): JsonResponse
    {
        try {
            $result = Result::with(['gamePlay'])->orderBy('id', 'desc')->first();
            if (!empty($result)) {
                return response()->json([
                    'data' => $result,
                    'success' => true,
                    'messages' => 'Latest result fetched successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'messages' => 'No Results',
                ], 200);
            }
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function getByDate($request): JsonResponse
    {
        try {
            $results = [];
            $date = $request->date;

            $sortBy = $request->sortBy ?: 'id';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Result())->newQuery()->orderBy($sortBy, $sortOrder);

            if (!$date) {
                $date = @Result::orderBy('fetching_date', 'DESC')->first()->fetching_date;
            }

            $query->when($date, function ($query) use ($date) {
                $query->whereDate('fetching_date', $date);
            });

            $results['data'] = $query->with(['merchant', 'product', 'gamePlay'])->get();
            // $resultDates = Result::whereConfirm('Yes')->limit(60)->orderBy('fetching_date', 'desc')->groupBy('fetching_date')->get();
            $resultDates = Result::whereConfirm('Yes')->orderBy('fetching_date', 'desc')->groupBy('fetching_date')->get();
            $result_dates = [];
            if (!empty($resultDates)) {
                foreach ($resultDates as $date) {
                    $result_dates[] = $date->fetching_date;
                }
                $results['result_dates'] = $result_dates;
            }

            return response()->json($results, 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }
    public function getSearchResult($request): JsonResponse
    {
        try {
            $where = ['confirm' => 'yes'];

            $results = Result::where($where);
            $whereCompany = $request->company;
            if (!empty($whereCompany)) {
                $results->where(function ($query) use ($whereCompany) {
                    $query->whereIN('game_play_id', $whereCompany);
                });
            }
            $date = $request->date_range;
            if (!empty($date)) {
                $results->where(function ($query) use ($date) {
                    if (!empty($date)) {
                        $dates = explode('-', $date);
                        $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                        $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                        if (!empty($dateFrom) && !empty($dateTo)) {
                            $query->whereBetween(DB::raw('DATE(fetching_date)'), [$dateFrom, $dateTo]);
                        } elseif (!empty($dateFrom) && empty($dateTo)) {
                            $query->where(DB::raw('DATE(fetching_date)'), $dateFrom);
                        } elseif (empty($dateFrom) && !empty($dateTo)) {
                            $query->where(DB::raw('DATE(fetching_date)'), $dateTo);
                        }
                    }
                });
            }
            $number = $request->number;
            $permutation = 0;
            if (isset($request->permutation)) {
                $permutation = $request->permutation;
            }


            $permutationNumbers = array();

            if (strlen($number) == 3) {
                for ($i = 0; $i < 10; $i++) {
                    array_push($permutationNumbers, $i . '' . $number);
                }

                for ($i = 0; $i < 10; $i++) {
                    array_push($permutationNumbers, $number . '' . $i);
                }

                $permutationNumbers = implode(', ', $permutationNumbers);
            }


            $resultWithFilter = $this->getFilterResults($number, $permutation, $results, $request);

            $result = $results;
            // $data = $result->select('*')->get();
            $prizes = [];
            if (isset($request->prize)) {
                $prizes = $request->prize;
            }

            if (count($whereCompany) > 0) {
                $whereCom = implode(',', $whereCompany);
                $whereCom = '(' . $whereCom . ')';
            }

            if (count($prizes) > 0) {
                if ($resultWithFilter['permutation'] && strlen($number) == 4) {
                    $permutationNumbers = implode(',', $resultWithFilter['permutation']);
                    //$permutationNumbers = '('.$permutationNumbers.')';

                    if (in_array('top3', $prizes)) {
                        if (in_array('special', $prizes)) {
                            if (in_array('consolation', $prizes)) {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ') ) AND (game_play_id in ' . $whereCom . ') AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ') ) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            } else {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ')
                                        or r1.special10 IN (' . $permutationNumbers . ')) AND (game_play_id in ' . $whereCom . ')  AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ')
                                        or r1.special10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            }
                        } else {
                            if (in_array('consolation', $prizes)) {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            } else {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')
                                        ) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')
                                        ) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            }
                        }
                    } elseif (in_array('special', $prizes)) {
                        if (in_array('consolation', $prizes)) {
                            if ($whereCompany) {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                    or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                    or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                    or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                    or r1.consolation10 IN (' . $permutationNumbers . ')) AND (game_play_id in ' . $whereCom . ')  AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            } else {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                    or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                    or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                    or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                    or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            }
                        } else {
                            if ($whereCompany) {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (
                                    r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers .
                                    ') or r1.special10 IN (' . $permutationNumbers . ')
                                    ) AND (game_play_id in ' . $whereCom . ')  AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            } else {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (
                                    r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers .
                                    ') or r1.special10 IN (' . $permutationNumbers . ')
                                    ) AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            }
                        }
                    } elseif (in_array('consolation', $prizes)) {
                        if ($whereCompany) {
                            $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                (r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                results.fetching_date
                              ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                        } else {
                            $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                (r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                results.fetching_date
                              ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                        }
                    }
                } else if (strlen($number) == 4) {
                    if (in_array('top3', $prizes)) {
                        if (in_array('special', $prizes)) {
                            if (in_array('consolation', $prizes)) {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                                        or r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                        r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                        or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '

                                        or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                        or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                        or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                        or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                                        or r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                        r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                        or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '

                                        or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                        or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                        or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                        or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            } else {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                                        or r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                        r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                        or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . '
                                        or r1.special10 = ' . $number . ') AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                                        or r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                        r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                        or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . '
                                        or r1.special10 = ' . $number . ') AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            }
                        } else {
                            if (in_array('consolation', $prizes)) {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                                        or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                        or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                        or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                        or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                                        or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                        or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                        or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                        or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            } else {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '
                                        ) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '
                                        ) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            }
                        }
                    } elseif (in_array('special', $prizes)) {
                        if (in_array('consolation', $prizes)) {
                            if ($whereCompany) {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                    r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                    or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '

                                    or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                    or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                    or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                    or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            } else {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                    r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                    or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '

                                    or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                    or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                    or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                    or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            }
                        } else {
                            if ($whereCompany) {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (
                                     r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                    r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                    or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '
                                    ) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            } else {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (
                                     r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                                    r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                                    or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '
                                    ) AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            }
                        }
                    } elseif (in_array('consolation', $prizes)) {
                        if ($whereCompany) {
                            $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                (r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                results.fetching_date
                              ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                        } else {
                            $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                (r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                                or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                                or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                                or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" LIMIT 1),
                                results.fetching_date
                              ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                        }
                    }
                } else {









                    if (in_array('top3', $prizes)) {
                        if (in_array('special', $prizes)) {
                            if (in_array('consolation', $prizes)) {
                                if ($whereCompany) {

                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ')
                                        or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ') ) AND (game_play_id in ' . $whereCom . ') AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ') ) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            } else {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ')
                                        or r1.special10 IN (' . $permutationNumbers . ')) AND (game_play_id in ' . $whereCom . ')  AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                        r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                        or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ')
                                        or r1.special10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            }
                        } else {
                            if (in_array('consolation', $prizes)) {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')

                                        or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                        or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                        or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                        or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            } else {
                                if ($whereCompany) {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')
                                        ) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                } else {
                                    $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                        (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                        (r1.prize1 IN (' . $permutationNumbers . ') or r1.prize2 IN (' . $permutationNumbers . ') or r1.prize3 IN (' . $permutationNumbers . ')
                                        ) AND r1.confirm = "yes" LIMIT 1),
                                        results.fetching_date
                                      ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                                }
                            }
                        }
                    } elseif (in_array('special', $prizes)) {
                        if (in_array('consolation', $prizes)) {
                            if ($whereCompany) {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                    or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                    or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                    or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                    or r1.consolation10 IN (' . $permutationNumbers . ')) AND (game_play_id in ' . $whereCom . ')  AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            } else {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers . ') or r1.special10 IN (' . $permutationNumbers . ')

                                    or r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                    or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                    or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                    or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            }
                        } else {
                            if ($whereCompany) {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (
                                    r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers .
                                    ') or r1.special10 IN (' . $permutationNumbers . ')
                                    ) AND (game_play_id in ' . $whereCom . ')  AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            } else {
                                $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                    (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                    (
                     r1.special1 IN (' . $permutationNumbers . ') or r1.special2 IN (' . $permutationNumbers . ') or r1.special3 IN (' . $permutationNumbers . ') or
                                    r1.special4 IN (' . $permutationNumbers . ') or r1.special5 IN (' . $permutationNumbers . ') or r1.special6 IN (' . $permutationNumbers . ')
                                    or r1.special7 IN (' . $permutationNumbers . ') or r1.special8 IN (' . $permutationNumbers . ') or r1.special9 IN (' . $permutationNumbers .
                                    ') or r1.special10 IN (' . $permutationNumbers . ')
                                    ) AND r1.confirm = "yes" LIMIT 1),
                                    results.fetching_date
                                  ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                            }
                        }
                    } elseif (in_array('consolation', $prizes)) {
                        if ($whereCompany) {
                            $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                (r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                                results.fetching_date
                              ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                        } else {
                            $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                                (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                                (r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                                or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                                or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                                or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                                results.fetching_date
                              ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                        }
                    }
                }
            } else {
                if (strlen($number) == 3) {
                    if ($whereCompany) {
                        $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                            (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                            (r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                            or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                            or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                            or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                            results.fetching_date
                          ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                    } else {
                        $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                            (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                            (r1.consolation1 IN (' . $permutationNumbers . ') or r1.consolation2 IN (' . $permutationNumbers . ') or r1.consolation3 IN (' . $permutationNumbers . ')
                            or r1.consolation4 IN (' . $permutationNumbers . ') or r1.consolation5 IN (' . $permutationNumbers . ') or r1.consolation6 IN (' . $permutationNumbers . ')
                            or r1.consolation7 IN (' . $permutationNumbers . ') or r1.consolation8 IN (' . $permutationNumbers . ') or r1.consolation9 IN (' . $permutationNumbers . ')
                            or r1.consolation10 IN (' . $permutationNumbers . ')) AND r1.confirm = "yes" LIMIT 1),
                            results.fetching_date
                          ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                    }
                } else {
                    if ($whereCompany) {
                        $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                            (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                            (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                            or r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                            r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                            or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '

                            or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                            or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                            or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                            or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" AND (game_play_id in ' . $whereCom . ')  LIMIT 1),
                            results.fetching_date
                          ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                    } else {
                        $datas = $resultWithFilter['results']->select(DB::Raw(' DATEDIFF(
                            (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND
                            (r1.prize1 = ' . $number . ' or r1.prize2 = ' . $number . ' or r1.prize3 = ' . $number . '

                            or r1.special1 = ' . $number . ' or r1.special2 = ' . $number . ' or r1.special3 = ' . $number . ' or
                            r1.special4 = ' . $number . ' or r1.special5 = ' . $number . ' or r1.special6 = ' . $number . '
                            or r1.special7 = ' . $number . ' or r1.special8 = ' . $number . ' or r1.special9 = ' . $number . ' or r1.special10 = ' . $number . '

                            or r1.consolation1 = ' . $number . ' or r1.consolation2 = ' . $number . ' or r1.consolation3 = ' . $number . '
                            or r1.consolation4 = ' . $number . ' or r1.consolation5 = ' . $number . ' or r1.consolation6 = ' . $number . '
                            or r1.consolation7 = ' . $number . ' or r1.consolation8 = ' . $number . ' or r1.consolation9 = ' . $number . '
                            or r1.consolation10 = ' . $number . ') AND r1.confirm = "yes" LIMIT 1),
                            results.fetching_date
                          ) AS days_since_last'), 'results.*')->orderBy('results.fetching_date', 'asc');
                    }
                }
            }

            $results2 = [];
            $myResult = $datas->get();

            foreach ($whereCompany as $company) {
                if ($resultWithFilter['permutation']) {
                    $permutationNum = $resultWithFilter['permutation'];
                    $data = $this->getAverageData($permutationNum, $company, $request);
                    if (!empty($data)) {
                        array_push($results2, $data);
                    }
                } else {
                    $data = $this->getAverageData($number, $company, $request);
                    if (!empty($data)) {
                        array_push($results2, $data);
                    }
                }
            }
            $permutationNumbers= !is_array($permutationNumbers) ? explode(', ', $permutationNumbers) : $permutationNumbers;
            $permutationsNum = strlen($number) == 4 ? $resultWithFilter['permutation'] : $permutationNumbers;

            //$data = $result->select('*')->get();
            $drawData = $myResult->last();
            $lastDrawId = !empty($drawData) ? $drawData->reference_number : '';
            $lastDrawNo = !empty($drawData) ? $drawData->id : "";
            /* if (! empty($lastDrawId)) {
                $lastDraws = explode('/', $lastDrawId);
                $lastDrawNo = $lastDraws[0];
            } */
            $avgDays = round($myResult->avg('days_since_last'));
            if (!empty($drawData)) {
                $fetching_date = Carbon::parse($drawData->fetching_date)->addDay($avgDays)->format('d-m-Y');
            } else {
                $fetching_date = '';
            }
            $card = [
                //"data" => $results,
                'total_hits' => $myResult->count(),
                'number' => $number,
                // "resultsQuery" => $resultsQuery,
                'max_draw_gap' => $myResult->max('days_since_last'),
                'min_draw_gap' => $myResult->min('days_since_last'),
                'avg_draw_gap' => $avgDays,
                'last_hit_draw_id' => $lastDrawId,
                'last_hit_draw_no' => $lastDrawNo,
                'permutation' => ($permutation == '0') ? 'No' : 'Yes',

                //"estimate_next_hit" => $lastDrawNo + $avgDays,
                'estimate_next_draw_date' => $fetching_date,
            ];
            $resultsData = [
                'data' => $myResult,
                'main_card' => $card,
                'for_card' => $results2,
                'permutation' => $permutationsNum,
            ];

            return response()->json($resultsData, 200);
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }

    public function getAverageData($number, $game_play_id, $request)
    {
        //DB::enableQueryLog();
        $permutation = $request->permutation;
        $default_result = Result::where('game_play_id', $game_play_id);
        $result_data = $this->getFilterResults($request->number, $permutation, $default_result, $request);
        $date = $request->date_range;
        $result_data['results']->where(function ($query) use ($date) {
            if (!empty($date)) {
                $dates = explode('-', $date);
                $dateFrom = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
                $dateTo = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d');

                if (!empty($dateFrom) && !empty($dateTo)) {
                    $query->whereBetween(DB::raw('DATE(fetching_date)'), [$dateFrom, $dateTo]);
                } elseif (!empty($dateFrom) && empty($dateTo)) {
                    $query->where(DB::raw('DATE(fetching_date)'), $dateFrom);
                } elseif (empty($dateFrom) && !empty($dateTo)) {
                    $query->where(DB::raw('DATE(fetching_date)'), $dateTo);
                }
            }
        });

        /* $datas = $result_data['results']->select(DB::Raw(' DATEDIFF(
            (SELECT (r1.fetching_date) FROM results as r1 WHERE r1.fetching_date > results.fetching_date AND (r1.prize1 = '.$number.' or
            r1.prize2 = '.$number.' or r1.prize3 = '.$number.' or r1.special1 = '.$number.' or r1.special2 = '.$number.' or
            r1.special3 = '.$number.' or r1.special4 = '.$number.' or r1.special5 = '.$number.' or r1.special6 = '.$number.' or
            r1.special7 = '.$number.' or r1.special8 = '.$number.' or r1.special9 = '.$number.' or r1.special10 = '.$number.' or
            r1.consolation1 = '.$number.' or r1.consolation2 = '.$number.' or r1.consolation3 = '.$number.' or r1.consolation4 = '.$number.' or
            r1.consolation5 = '.$number.' or r1.consolation6 = '.$number.' or r1.consolation7 = '.$number.' or r1.consolation8 = '.$number.' or
            r1.consolation9 = '.$number.' or r1.consolation10 = '.$number.' or r1.confirm = '.$number.') LIMIT 1),
            results.fetching_date
          ) AS days_since_last'),'results.*'); */

        //dd($result_data['results']->get());
        $resultsData = $result_data['results']->select('results.*')->get();
        //dd($resultsData);

        $drawData = $resultsData->last();
        $lastDrawId = !empty($drawData) ? $drawData->reference_number : '';
        $lastDrawNo = !empty($drawData) ? $drawData->id : "";
        /* if (! empty($lastDrawId)) {
            $lastDraws = explode('/', $lastDrawId);
            $lastDrawNo = $lastDraws[0];
        } */
        $avgDays = round($resultsData->avg('days_since_last'));

        if ($resultsData->count() == 0) {
            $results2 = [];
        } else {
            $fetchingDate = !empty($drawData->fetching_date) ? Carbon::parse($drawData->fetching_date)->addDay($avgDays)->format('d-m-Y') : '';
            $results2 = [
                //"data" => $results,
                'total_hits' => $resultsData->count(),
                'game_play_id' => $game_play_id,
                //"number" => $number,
                // "resultsQuery" => $resultsQuery,
                'max_draw_gap' => $resultsData->max('days_since_last'),
                'min_draw_gap' => $resultsData->min('days_since_last'),
                'avg_draw_gap' => $avgDays,
                'last_hit_draw_id' => $lastDrawId,
                'last_hit_draw_no' => $lastDrawNo,

                //"estimate_next_hit" => $lastDrawNo + $avgDays,
                'estimate_next_draw_date' => $fetchingDate,
            ];
        }

        return $results2;
    }

    public function permute($str, $l, $r)
    {
        if ($l == $r) {
            $this->result[] = $str;
        } else {
            for ($i = $l; $i <= $r; $i++) {
                $str = $this->swap($str, $l, $i);
                $this->permute($str, $l + 1, $r);
                $str = $this->swap($str, $l, $i);
            }
        }
    }

    public function swap($a, $i, $j)
    {
        $charArray = str_split($a);
        $temp = $charArray[$i];
        $charArray[$i] = $charArray[$j];
        $charArray[$j] = $temp;

        return implode($charArray);
    }

    public function getFilterResults($number, $permutation, $results, $request)
    {
        $str = $number;
        $n = strlen($str);
        $this->permute($str, 0, $n - 1);
        $temp = [];
        $count = 1;
        $prizes = [];
        if (isset($request->prize)) {
            $prizes = $request->prize;
        }
        if (isset($permutation) && $permutation == 1 && $n == 4) {
            for ($j = 0; $j < count($this->result); $j++) {
                if (!in_array($this->result[$j], $temp)) {
                    array_push($temp, $this->result[$j]);
                }
            }
            if (isset($request->prize)) {
                if (in_array('top3', $prizes)) {
                    if (in_array('special', $prizes)) {
                        if (in_array('consolation', $prizes)) {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp)
                                    ->orWhereIn('special1', $temp)
                                    ->orWhereIn('special2', $temp)
                                    ->orWhereIn('special3', $temp)
                                    ->orWhereIn('special4', $temp)
                                    ->orWhereIn('special5', $temp)
                                    ->orWhereIn('special6', $temp)
                                    ->orWhereIn('special7', $temp)
                                    ->orWhereIn('special8', $temp)
                                    ->orWhereIn('special9', $temp)
                                    ->orWhereIn('special10', $temp)
                                    ->orWhereIn('consolation1', $temp)
                                    ->orWhereIn('consolation2', $temp)
                                    ->orWhereIn('consolation3', $temp)
                                    ->orWhereIn('consolation4', $temp)
                                    ->orWhereIn('consolation5', $temp)
                                    ->orWhereIn('consolation6', $temp)
                                    ->orWhereIn('consolation7', $temp)
                                    ->orWhereIn('consolation8', $temp)
                                    ->orWhereIn('consolation9', $temp)
                                    ->orWhereIn('consolation10', $temp);
                            });
                        } else {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp)
                                    ->orWhereIn('special1', $temp)
                                    ->orWhereIn('special2', $temp)
                                    ->orWhereIn('special3', $temp)
                                    ->orWhereIn('special4', $temp)
                                    ->orWhereIn('special5', $temp)
                                    ->orWhereIn('special6', $temp)
                                    ->orWhereIn('special7', $temp)
                                    ->orWhereIn('special8', $temp)
                                    ->orWhereIn('special9', $temp)
                                    ->orWhereIn('special10', $temp);
                            });
                        }
                    } else {
                        if (in_array('consolation', $prizes)) {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp)
                                    ->orWhereIn('consolation1', $temp)
                                    ->orWhereIn('consolation2', $temp)
                                    ->orWhereIn('consolation3', $temp)
                                    ->orWhereIn('consolation4', $temp)
                                    ->orWhereIn('consolation5', $temp)
                                    ->orWhereIn('consolation6', $temp)
                                    ->orWhereIn('consolation7', $temp)
                                    ->orWhereIn('consolation8', $temp)
                                    ->orWhereIn('consolation9', $temp)
                                    ->orWhereIn('consolation10', $temp);
                            });
                        } else {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp);
                            });
                        }
                    }
                } elseif (in_array('special', $prizes)) {
                    if (in_array('consolation', $prizes)) {
                        $results->where(function ($query) use ($temp) {
                            $query->WhereIn('special1', $temp)
                                ->orWhereIn('special2', $temp)
                                ->orWhereIn('special3', $temp)
                                ->orWhereIn('special4', $temp)
                                ->orWhereIn('special5', $temp)
                                ->orWhereIn('special6', $temp)
                                ->orWhereIn('special7', $temp)
                                ->orWhereIn('special8', $temp)
                                ->orWhereIn('special9', $temp)
                                ->orWhereIn('special10', $temp)
                                ->orWhereIn('consolation1', $temp)
                                ->orWhereIn('consolation2', $temp)
                                ->orWhereIn('consolation3', $temp)
                                ->orWhereIn('consolation4', $temp)
                                ->orWhereIn('consolation5', $temp)
                                ->orWhereIn('consolation6', $temp)
                                ->orWhereIn('consolation7', $temp)
                                ->orWhereIn('consolation8', $temp)
                                ->orWhereIn('consolation9', $temp)
                                ->orWhereIn('consolation10', $temp);
                        });
                    } else {
                        $results->where(function ($query) use ($temp) {
                            $query->WhereIn('special1', $temp)
                                ->orWhereIn('special2', $temp)
                                ->orWhereIn('special3', $temp)
                                ->orWhereIn('special4', $temp)
                                ->orWhereIn('special5', $temp)
                                ->orWhereIn('special6', $temp)
                                ->orWhereIn('special7', $temp)
                                ->orWhereIn('special8', $temp)
                                ->orWhereIn('special9', $temp)
                                ->orWhereIn('special10', $temp);
                        });
                    }
                } elseif (in_array('consolation', $prizes)) {
                    $results->where(function ($query) use ($temp) {
                        $query->WhereIn('consolation1', $temp)
                            ->orWhereIn('consolation2', $temp)
                            ->orWhereIn('consolation3', $temp)
                            ->orWhereIn('consolation4', $temp)
                            ->orWhereIn('consolation5', $temp)
                            ->orWhereIn('consolation6', $temp)
                            ->orWhereIn('consolation7', $temp)
                            ->orWhereIn('consolation8', $temp)
                            ->orWhereIn('consolation9', $temp)
                            ->orWhereIn('consolation10', $temp);
                    });
                }
            } else {
                $results->where(function ($query) use ($temp) {
                    $query->whereIn('prize1', $temp)
                        ->orWhereIn('prize2', $temp)
                        ->orWhereIn('prize3', $temp)
                        ->orWhereIn('special1', $temp)
                        ->orWhereIn('special2', $temp)
                        ->orWhereIn('special3', $temp)
                        ->orWhereIn('special4', $temp)
                        ->orWhereIn('special5', $temp)
                        ->orWhereIn('special6', $temp)
                        ->orWhereIn('special7', $temp)
                        ->orWhereIn('special8', $temp)
                        ->orWhereIn('special9', $temp)
                        ->orWhereIn('special10', $temp)
                        ->orWhereIn('consolation1', $temp)
                        ->orWhereIn('consolation2', $temp)
                        ->orWhereIn('consolation3', $temp)
                        ->orWhereIn('consolation4', $temp)
                        ->orWhereIn('consolation5', $temp)
                        ->orWhereIn('consolation6', $temp)
                        ->orWhereIn('consolation7', $temp)
                        ->orWhereIn('consolation8', $temp)
                        ->orWhereIn('consolation9', $temp)
                        ->orWhereIn('consolation10', $temp);
                });
            }
        } else if ($n == 4) {
            if (isset($request->prize)) {
                if (in_array('top3', $prizes)) {
                    if (in_array('special', $prizes)) {
                        if (in_array('consolation', $prizes)) {
                            $results->where(function ($query) use ($number) {
                                $query->where(['prize1' => $number])
                                    ->orWhere(['prize2' => $number])
                                    ->orWhere(['prize3' => $number])
                                    ->orWhere(['special1' => $number])
                                    ->orWhere(['special2' => $number])
                                    ->orWhere(['special3' => $number])
                                    ->orWhere(['special4' => $number])
                                    ->orWhere(['special5' => $number])
                                    ->orWhere(['special6' => $number])
                                    ->orWhere(['special7' => $number])
                                    ->orWhere(['special8' => $number])
                                    ->orWhere(['special9' => $number])
                                    ->orWhere(['special10' => $number])
                                    ->orWhere(['consolation1' => $number])
                                    ->orWhere(['consolation2' => $number])
                                    ->orWhere(['consolation3' => $number])
                                    ->orWhere(['consolation4' => $number])
                                    ->orWhere(['consolation5' => $number])
                                    ->orWhere(['consolation6' => $number])
                                    ->orWhere(['consolation7' => $number])
                                    ->orWhere(['consolation8' => $number])
                                    ->orWhere(['consolation9' => $number])
                                    ->orWhere(['consolation10' => $number]);
                            });
                        } else {
                            $results->where(function ($query) use ($number) {
                                $query->where(['prize1' => $number])
                                    ->orWhere(['prize2' => $number])
                                    ->orWhere(['prize3' => $number])
                                    ->orWhere(['special1' => $number])
                                    ->orWhere(['special2' => $number])
                                    ->orWhere(['special3' => $number])
                                    ->orWhere(['special4' => $number])
                                    ->orWhere(['special5' => $number])
                                    ->orWhere(['special6' => $number])
                                    ->orWhere(['special7' => $number])
                                    ->orWhere(['special8' => $number])
                                    ->orWhere(['special9' => $number])
                                    ->orWhere(['special10' => $number]);
                            });
                        }
                    } else {
                        if (in_array('consolation', $prizes)) {
                            $results->where(function ($query) use ($number) {
                                $query->Where('prize1', $number)
                                    ->orWhere('prize2', $number)
                                    ->orWhere('prize3', $number)
                                    ->orWhere('consolation1', $number)
                                    ->orWhere('consolation2', $number)
                                    ->orWhere('consolation3', $number)
                                    ->orWhere('consolation4', $number)
                                    ->orWhere('consolation5', $number)
                                    ->orWhere('consolation6', $number)
                                    ->orWhere('consolation7', $number)
                                    ->orWhere('consolation8', $number)
                                    ->orWhere('consolation9', $number)
                                    ->orWhere('consolation10', $number);
                            });
                        } else {
                            $results->where(function ($query) use ($number) {
                                $query->where(['prize1' => $number])
                                    ->orWhere(['prize2' => $number])
                                    ->orWhere(['prize3' => $number]);
                            });
                        }
                    }
                } elseif (in_array('special', $prizes)) {
                    if (in_array('consolation', $prizes)) {
                        $results->where(function ($query) use ($number) {
                            $query->Where(['special1' => $number])
                                ->orWhere(['special2' => $number])
                                ->orWhere(['special3' => $number])
                                ->orWhere(['special4' => $number])
                                ->orWhere(['special5' => $number])
                                ->orWhere(['special6' => $number])
                                ->orWhere(['special7' => $number])
                                ->orWhere(['special8' => $number])
                                ->orWhere(['special9' => $number])
                                ->orWhere(['special10' => $number])
                                ->orWhere(['consolation1' => $number])
                                ->orWhere(['consolation2' => $number])
                                ->orWhere(['consolation3' => $number])
                                ->orWhere(['consolation4' => $number])
                                ->orWhere(['consolation5' => $number])
                                ->orWhere(['consolation6' => $number])
                                ->orWhere(['consolation7' => $number])
                                ->orWhere(['consolation8' => $number])
                                ->orWhere(['consolation9' => $number])
                                ->orWhere(['consolation10' => $number]);
                        });
                    } else {
                        $results->where(function ($query) use ($number) {
                            $query->Where(['special1' => $number])
                                ->orWhere(['special2' => $number])
                                ->orWhere(['special3' => $number])
                                ->orWhere(['special4' => $number])
                                ->orWhere(['special5' => $number])
                                ->orWhere(['special6' => $number])
                                ->orWhere(['special7' => $number])
                                ->orWhere(['special8' => $number])
                                ->orWhere(['special9' => $number])
                                ->orWhere(['special10' => $number]);
                        });
                    }
                } elseif (in_array('consolation', $prizes)) {
                    $results->where(function ($query) use ($number) {
                        $query->Where('consolation1', $number)
                            ->orWhere('consolation2', $number)
                            ->orWhere('consolation3', $number)
                            ->orWhere('consolation4', $number)
                            ->orWhere('consolation5', $number)
                            ->orWhere('consolation6', $number)
                            ->orWhere('consolation7', $number)
                            ->orWhere('consolation8', $number)
                            ->orWhere('consolation9', $number)
                            ->orWhere('consolation10', $number);
                    });
                }
            } else {
                $results->where(function ($query) use ($number) {
                    $query->where(['prize1' => $number])
                        ->orWhere(['prize2' => $number])
                        ->orWhere(['prize3' => $number])
                        ->orWhere(['special1' => $number])
                        ->orWhere(['special2' => $number])
                        ->orWhere(['special3' => $number])
                        ->orWhere(['special4' => $number])
                        ->orWhere(['special5' => $number])
                        ->orWhere(['special6' => $number])
                        ->orWhere(['special7' => $number])
                        ->orWhere(['special8' => $number])
                        ->orWhere(['special9' => $number])
                        ->orWhere(['special10' => $number])
                        ->orWhere(['consolation1' => $number])
                        ->orWhere(['consolation2' => $number])
                        ->orWhere(['consolation3' => $number])
                        ->orWhere(['consolation4' => $number])
                        ->orWhere(['consolation5' => $number])
                        ->orWhere(['consolation6' => $number])
                        ->orWhere(['consolation7' => $number])
                        ->orWhere(['consolation8' => $number])
                        ->orWhere(['consolation9' => $number])
                        ->orWhere(['consolation10' => $number]);
                });
            }
        } else {

            for ($i = 0; $i < 10; $i++) {
                array_push($temp, $i . '' . $number);
            }


            for ($i = 0; $i < 10; $i++) {
                array_push($temp, $number . '' . $i);
            }





            if (isset($request->prize)) {
                if (in_array('top3', $prizes)) {
                    if (in_array('special', $prizes)) {
                        if (in_array('consolation', $prizes)) {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp)
                                    ->orWhereIn('special1', $temp)
                                    ->orWhereIn('special2', $temp)
                                    ->orWhereIn('special3', $temp)
                                    ->orWhereIn('special4', $temp)
                                    ->orWhereIn('special5', $temp)
                                    ->orWhereIn('special6', $temp)
                                    ->orWhereIn('special7', $temp)
                                    ->orWhereIn('special8', $temp)
                                    ->orWhereIn('special9', $temp)
                                    ->orWhereIn('special10', $temp)
                                    ->orWhereIn('consolation1', $temp)
                                    ->orWhereIn('consolation2', $temp)
                                    ->orWhereIn('consolation3', $temp)
                                    ->orWhereIn('consolation4', $temp)
                                    ->orWhereIn('consolation5', $temp)
                                    ->orWhereIn('consolation6', $temp)
                                    ->orWhereIn('consolation7', $temp)
                                    ->orWhereIn('consolation8', $temp)
                                    ->orWhereIn('consolation9', $temp)
                                    ->orWhereIn('consolation10', $temp);
                            });
                        } else {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp)
                                    ->orWhereIn('special1', $temp)
                                    ->orWhereIn('special2', $temp)
                                    ->orWhereIn('special3', $temp)
                                    ->orWhereIn('special4', $temp)
                                    ->orWhereIn('special5', $temp)
                                    ->orWhereIn('special6', $temp)
                                    ->orWhereIn('special7', $temp)
                                    ->orWhereIn('special8', $temp)
                                    ->orWhereIn('special9', $temp)
                                    ->orWhereIn('special10', $temp);
                            });
                        }
                    } else {
                        if (in_array('consolation', $prizes)) {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp)
                                    ->orWhereIn('consolation1', $temp)
                                    ->orWhereIn('consolation2', $temp)
                                    ->orWhereIn('consolation3', $temp)
                                    ->orWhereIn('consolation4', $temp)
                                    ->orWhereIn('consolation5', $temp)
                                    ->orWhereIn('consolation6', $temp)
                                    ->orWhereIn('consolation7', $temp)
                                    ->orWhereIn('consolation8', $temp)
                                    ->orWhereIn('consolation9', $temp)
                                    ->orWhereIn('consolation10', $temp);
                            });
                        } else {
                            $results->where(function ($query) use ($temp) {
                                $query->whereIn('prize1', $temp)
                                    ->orWhereIn('prize2', $temp)
                                    ->orWhereIn('prize3', $temp);
                            });
                        }
                    }
                } elseif (in_array('special', $prizes)) {
                    if (in_array('consolation', $prizes)) {
                        $results->where(function ($query) use ($temp) {
                            $query->WhereIn('special1', $temp)
                                ->orWhereIn('special2', $temp)
                                ->orWhereIn('special3', $temp)
                                ->orWhereIn('special4', $temp)
                                ->orWhereIn('special5', $temp)
                                ->orWhereIn('special6', $temp)
                                ->orWhereIn('special7', $temp)
                                ->orWhereIn('special8', $temp)
                                ->orWhereIn('special9', $temp)
                                ->orWhereIn('special10', $temp)
                                ->orWhereIn('consolation1', $temp)
                                ->orWhereIn('consolation2', $temp)
                                ->orWhereIn('consolation3', $temp)
                                ->orWhereIn('consolation4', $temp)
                                ->orWhereIn('consolation5', $temp)
                                ->orWhereIn('consolation6', $temp)
                                ->orWhereIn('consolation7', $temp)
                                ->orWhereIn('consolation8', $temp)
                                ->orWhereIn('consolation9', $temp)
                                ->orWhereIn('consolation10', $temp);
                        });
                    } else {
                        $results->where(function ($query) use ($temp) {
                            $query->WhereIn('special1', $temp)
                                ->orWhereIn('special2', $temp)
                                ->orWhereIn('special3', $temp)
                                ->orWhereIn('special4', $temp)
                                ->orWhereIn('special5', $temp)
                                ->orWhereIn('special6', $temp)
                                ->orWhereIn('special7', $temp)
                                ->orWhereIn('special8', $temp)
                                ->orWhereIn('special9', $temp)
                                ->orWhereIn('special10', $temp);
                        });
                    }
                } elseif (in_array('consolation', $prizes)) {
                    $results->where(function ($query) use ($temp) {
                        $query->WhereIn('consolation1', $temp)
                            ->orWhereIn('consolation2', $temp)
                            ->orWhereIn('consolation3', $temp)
                            ->orWhereIn('consolation4', $temp)
                            ->orWhereIn('consolation5', $temp)
                            ->orWhereIn('consolation6', $temp)
                            ->orWhereIn('consolation7', $temp)
                            ->orWhereIn('consolation8', $temp)
                            ->orWhereIn('consolation9', $temp)
                            ->orWhereIn('consolation10', $temp);
                    });
                }
            } else {
                $results->where(function ($query) use ($temp) {
                    $query->whereIn('prize1', $temp)
                        ->orWhereIn('prize2', $temp)
                        ->orWhereIn('prize3', $temp)
                        ->orWhereIn('special1', $temp)
                        ->orWhereIn('special2', $temp)
                        ->orWhereIn('special3', $temp)
                        ->orWhereIn('special4', $temp)
                        ->orWhereIn('special5', $temp)
                        ->orWhereIn('special6', $temp)
                        ->orWhereIn('special7', $temp)
                        ->orWhereIn('special8', $temp)
                        ->orWhereIn('special9', $temp)
                        ->orWhereIn('special10', $temp)
                        ->orWhereIn('consolation1', $temp)
                        ->orWhereIn('consolation2', $temp)
                        ->orWhereIn('consolation3', $temp)
                        ->orWhereIn('consolation4', $temp)
                        ->orWhereIn('consolation5', $temp)
                        ->orWhereIn('consolation6', $temp)
                        ->orWhereIn('consolation7', $temp)
                        ->orWhereIn('consolation8', $temp)
                        ->orWhereIn('consolation9', $temp)
                        ->orWhereIn('consolation10', $temp);
                });
            }
        }
        if (isset($permutation)) {
            $response['permutation'] = !is_array($temp)?explode(',',$temp):$temp;
        }
        $response['results'] = $results;

        return $response;
    }
    public function getTwoMonthResult($request): JsonResponse
    {

        try {
            $nDate = explode('-', $request->date);
            $getForMonthStart = $nDate[0] . '-' . $nDate[1] . '-01';
            $lastday = date('t', strtotime('today'));
            $getForMonthEnd = $nDate[0] . '-' . $nDate[1] . '-' . $lastday;
            $results = Result::select('fetching_date')->whereBetween('fetching_date', [$getForMonthStart,  $getForMonthEnd])->get();
            $dates = [];
            if ($results) {
                foreach ($results as $val) {
                    array_push($dates, $val->fetching_date);
                }
            }
            if (!empty($results)) {
                return response()->json([
                    'data' =>  $dates,
                    'success' => true,
                    'messages' => 'Latest result fetched successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'data' => [$request->date],
                    'messages' => 'No Results',
                ], 200);
            }
        } catch (\Exception $e) {
            return generalErrorResponse($e);
        }
    }
}
