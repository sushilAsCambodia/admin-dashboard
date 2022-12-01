<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WalletService
{
    private $result = [];

    private $data = [];

    public function getAmount($request): JsonResponse
    {
        $customerId = $request->customer_id;
        $merchantId = $request->merchant_id;
        $member = Member::whereCustomerId($customerId)->whereMerchantId($merchantId)->first();
        if (empty($member)) {
            return response()->json([
                'messages' => ['Invalid Customer'],
            ], 200);
        }
        $memberId = $member->id;
        $wallet = Wallet::whereMemberId($memberId)->whereMerchantId($merchantId)->whereStatus('Active')->first();

        if (! empty($wallet)) {
            $data['balance'] = number_format($wallet->amount, 2);

            return response()->json([
                'data' => $data,
                'success' => true,
                'messages' => ['Retrieved Wallet Balance Successfully'],
            ], 200);
        } else {
            return response()->json([
                'messages' => ['Wallet not found'],
            ], 200);
        }
    }

    public function fetchBalance($request): JsonResponse
    {
        $customerIdList = $request->customer_id_list;
        $merchantId = $request->merchant_id;
        // $token = $request->bearerToken();

        /* Merchant Validation  with token */
        $merchant = Merchant::whereId($merchantId)->first(); //->whereToken($token)
        if (empty($merchant)) {
            return response()->json([
                'messages' => ['UnAuthorized User Login '],
            ], 401);
        }

        /* Member  Validation  */
        $customerDataList = [];
        if (! empty($customerIdList)) {
            $customerDataList = $this->getCustomerBalance($customerIdList, $merchantId);
            $data = [
                'merchant_id' => $merchantId,
                'customer_id_list' => $customerDataList,
            ];
        } else {
            $customerIdList = [];
            $customerDataList = $this->getCustomerBalance($customerIdList, $merchantId);
            $totalBalance = Wallet::whereMerchantId($merchantId)->sum('amount');
            $totalCustomers = Wallet::whereMerchantId($merchantId)->count();
            $currency = $merchant->currency->code;
            $data = [
                'merchant_id' => $merchantId,
                'name' => $merchant->name,
                'currency' => $currency,
                'total_balance' => $totalBalance,
                'total_customers' => $totalCustomers,
                'customer_id_list' => $customerDataList,
            ];
        }

        if (! empty($data)) {
            return response()->json([
                'data' => $data,
                'success' => true,
                'messages' => ['Data Fetched Successfully'],
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'messages' => ['No Data'],
            ], 200);
        }
    }

    public function getCustomerBalance($customerIdList, $merchantId)
    {
        if (! empty($customerIdList)) {
            $customerDataList = [];
            foreach ($customerIdList as $key => $value) {
                $user = Member::whereCustomerId($value['customer_id'])
                        ->whereMerchantId($merchantId)
                        ->first();
                if (! $user) {
                    $customerData = [];
                    $customerData['customer_id'] = $value['customer_id'];
                    $customerData['message'] = 'Invalid Customer Id';
                    array_push($customerDataList, $customerData);
                } else {
                    $customerWallet = Wallet::whereMemberId($user->id)->whereMerchantId($merchantId)->first();
                    if ($customerWallet) {
                        $customerData = [];
                        $customerData['customer_id'] = $value['customer_id'];
                        $customerData['account_balance'] = $customerWallet->amount;
                        $customerData['currency'] = Merchant::find($merchantId)->currency->code;
                        $customerData['customer_name'] = $user->customer_name;
                        array_push($customerDataList, $customerData);
                    }
                }
                $customerDatas[] = $customerData;
            }
        } else {
            $customerDataList = [];
            $customerData = [];
            $user = Member::whereMerchantId($merchantId)->get();
            foreach ($user as $key => $value) {
                $customerWallet = Wallet::whereMemberId($value->id)->whereMerchantId($merchantId)->first();
                if ($customerWallet) {
                    $customerData = [];
                    $customerData['customer_id'] = $value->customer_id;
                    $customerData['account_balance'] = $customerWallet->amount;
                    // $customerData['currency'] = Merchant::find($merchantId)->currency->code;
                    $customerData['customer_name'] = $value->customer_name;
                    array_push($customerDataList, $customerData);
                }
                $customerDatas[] = $customerData;
            }
        }

        return $customerDatas;
    }

public function updateWallet($request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'customer_name' => 'required',
        'customer_id' => 'required|alpha_dash',
        'merchant_id' => 'required|exists:merchants,id',
    ]);

    if ($validator->fails()) {
        //return response()->json(['Validation Error.' => $validator->errors()]);
        return response()->json(['message' => $validator->messages(), 'success' => false, 'messageId' => 406], 200);
    }

    $amount = $request->amount;
    $customerId = $request->customer_id;
    $merchantId = $request->merchant_id;
    $transactionType = $request->mode;
    // $currency = $request->currency ?? 'USD';
    $customerMail = $request->customer_mail;
    $customerName = $request->customer_name;
    $transactionStatus = 'In-process';
    // $token = $request->bearerToken();

    /*  User Validate  */
    $user = Member::whereCustomerId($customerId)->whereMerchantId($merchantId)->first();
    if (! $user) {
        $user = Member::create([
            'customer_id' => $customerId,
            'merchant_id' => $merchantId,
            'customer_name' => $customerName,
            'name' => $customerName,
            'email' => $customerMail,
            'language_id' => config('constants.DEFAULT_LANG_ID'),
        ]);
    }

    $memberId = $user->id;

    /* Merchant Validation  with token */
    $merchant = Merchant::whereId($merchantId)->first(); //->whereToken($token)
    if (empty($merchant)) {
        return response()->json([
            'messages' => ['UnAuthorized User Login '],
        ], 401);
    }

    $currency = $merchant->currency->code;

    $LastNumber = Transaction::max('id') + 1;
    $append = zeroappend($LastNumber);
    $transactionID = 'TR'.$append.$LastNumber;
    /*  Create Transacation  */
    $transactionData = [
        'member_id' => $memberId,
        'transaction_id' => $transactionID,
        'external_transaction_id'=> $request->external_transaction_id,
        'merchant_id' => $merchantId,
        'transaction_type' => $transactionType,
        'amount' => $amount,
        'currency' => $currency,
        'status' => $transactionStatus,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),

    ];
    $lastTransactionId = Transaction::create($transactionData);

    $customerWallet = Wallet::whereMemberId($memberId)->whereMerchantId($merchantId)->first();
    if (! empty($customerWallet)) {
        $responseStatus = true;
        $responseMsg = '';
        //subtract amount from wallet
        if ($transactionType == 'Debit') {
            $transaction_from = 'transfer-out';
            if ($customerWallet->amount < $amount) {
                $transactionStatus = 'Fail';
                $responseStatus = false;
                $responseMsg = 'Insufficient Balance';
            } else {
                $transactionStatus = 'Complete';
                $customerWallet->amount = $customerWallet->amount - $amount;
                $responseMsg = 'Debit transaction completed';
            }
        } else {
            $transaction_from = 'transfer-in';
            //sum amount in wallet
            $customerWallet->amount = $customerWallet->amount + $amount;
            $transactionStatus = 'Complete';
            $responseMsg = 'Credit transaction completed';
        }
        $customerWallet->update();
    } else {
        //insert user wallet
        if ($transactionType == 'Debit') {
            $transaction_from = 'transfer-out';
            $customerWallet = Wallet::create([
                'member__id' => $memberId,
                'merchant_id' => $merchantId,
                'amount' => 0,
            ]);
            $responseMsg = 'Insufficient Balance';
            $transactionStatus = 'Fail';
            $responseStatus = false;
        } else {
            $transaction_from = 'transfer-in';
            $customerWallet = Wallet::create([
                'member_id' => $memberId,
                'merchant_id' => $merchantId,
                'amount' => $amount,
            ]);
            $transactionStatus = 'Complete';
            $responseMsg = 'Credit transaction completed';
            $responseStatus = true;
        }
    }

    /*  Update Transaction  */
    Transaction::whereId($lastTransactionId->id)->update([
        'status' => $transactionStatus,
        'transaction_from' => $transaction_from,
        'message' => $responseMsg,
        'updated_at' => Carbon::now(),
    ]);

    if (! $responseStatus) {
        return response()->json([
            'messages' => $responseMsg,
        ], 200);
    }

    if ($transactionType == 'Debit') {
        $body = [
            'customer_name' => $customerName,
            'wallet_balance' => $customerWallet->amount,
            'transaction_id' => $transactionID,
            'currency' => $currency,
        ];

        return response()->json([
            'data' => $body,
            'success' => true,
            'messages' => ['Amount Debited'],
        ], 200);
    } else {
        $body = [
            'customer_name' => $customerName,
            'wallet_balance' => $customerWallet->amount,
            'transaction_id' => $transactionID,
            'currency' => $currency,
        ];

        return response()->json([
            'data' => $body,
            'success' => true,
            'messages' => ['Amount Credited'],
        ]);
    }
}

    public function getTransactionDetailsByID($request): JsonResponse
    {
        try {
            $customerIdList = $request->customer_id_list;
            $transactionIdList = $request->transaction_id_list;
            $externalTransactionIdList = $request->external_transaction_id_list;
            $merchantId = $request->merchant_id;
            $transcationDataList = [];

            /* Getting Customer IDs  */
            $customerIds = [];
            if (! empty($customerIdList)) {
                foreach ($customerIdList as $key => $value) {
                    $customerIds[] = $value['customer_id'];
                }
            }
            $transactionIDs = [];
            if (! empty($transactionIdList)) {
                foreach ($transactionIdList as $key => $value) {
                    $transactionIDs[] = $value['transaction_id'];
                }
            }
            $externalTransactionIDs = [];
            if (! empty($externalTransactionIdList)) {
                foreach ($externalTransactionIdList as $key => $value) {
                    $externalTransactionIDs[] = $value['external_transaction_id'];
                }
            }
            $select = [
                'members.customer_id',
                'transactions.transaction_id as transaction_id',
                'transactions.external_transaction_id',
                'transactions.transaction_type as transaction_type',
                'transactions.amount as transaction_amount',
                'transactions.currency as transaction_currency',
                'transactions.status as transaction_status',
                'transactions.created_at as transaction_date',
            ];
            $transactions = Transaction::select($select)
                                ->where('transactions.merchant_id', $merchantId)
                                ->leftJoin('members', 'members.id', '=', 'transactions.member_id');

            if (! empty($transactionIDs)) {
                $transactions = $transactions->whereIn('transactions.transaction_id', $transactionIDs);
            }
            if (! empty($externalTransactionIDs))
                $transactions = $transactions->whereIn('transactions.external_transaction_id', $externalTransactionIDs);

            if (! empty($request->from_date) && ! empty($request->to_date)) {
                $transactions = $transactions->whereBetween('transactions.created_at', [$request->from_date, $request->to_date]);
            }

            if (! empty($customerIds)) {
                $transactions = $transactions->whereIn('members.customer_id', $customerIds);
            }
            $transcationDataList = $transactions->get();
            if (! empty($transcationDataList->toArray())) {
                return response()->json([
                    'responseDto' => [
                        'responseCode' => 200,
                        'responseDescription' => 'Successfully fetched data',
                        'body' => [
                            'merchant_id' => $merchantId,
                            'transaction_id_detail_list' => $transcationDataList,
                        ],
                    ],
                ], 200);
            } else {
                return response()->json([
                    'responseDto' => [
                        'responseCode' => 200,
                        'responseDescription' => 'No Data',
                        'body' => [],
                    ],
                ], 200);
            }
        } catch (\Exception$e) {
            return response()->json([
                'responseDto' => [
                    'responseCode' => 500,
                    'responseDescription' => 'error',
                    'body' => ['msg' => 'Internal Server Error'],
                ],
            ], 200);
        }
    }
}
