<?php

namespace App\Services;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MemberService
{
    public function checkLogin($params): JsonResponse
    {
        try {
            $member = Member::select('temp_token as token')->where($params)->first();
            if (! empty($member)) {
                return response()->json(['data' => $member, 'message' => 'Logged in successfully'], 200);
            } else {
                return response()->json(['message' => 'Invalid Credentials'], 200);
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginate($request): JsonResponse
    {
        try {
            $this->updateLoginStatus();
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'personal_access_tokens.last_used_at';
            $sortOrder = $request->descending == 'false' ? 'desc' : 'asc';

            $query = (new Member())->newQuery();

            $query->when($request->customer_id, function ($query) use ($request) {
                $query->where('members.customer_id', $request->customer_id);
            });
            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('members.merchant_id', $request->merchant_id);
            });

            $query->when($request->code, function ($query) use ($request) {
                $query->where('merchants.code', $request->code);
            });
            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currencies.id', $request->currency_id);
            });
            $twoHours = Carbon::now()->subMinute(30);
            if (! empty($request->online_status)) {
                if ($request->online_status == 'Online') {
                    $query->where('personal_access_tokens.last_used_at', '>', $twoHours);
                }
                if ($request->online_status == 'Offline') {
                    $query->where('personal_access_tokens.last_used_at', '<', $twoHours)->orWhere('personal_access_tokens.last_used_at', null);
                }
            }
            if ($sortBy == 'online_status') {
                $sortBy = 'personal_access_tokens.last_used_at';
            }
            $selectDatas = [
                'members.id',
                'members.merchant_id',
                'members.customer_id',
                'members.customer_name',
                'members.name',
                'members.email',
                'members.temp_token',
                'members.last_login',
                'members.deleted_at',
                'members.created_at',
                'members.updated_at',
                'members.language_id',
                'members.login_ip',
                'merchants.name as merchant_name',
                'currencies.code as currency_code',
                'wallets.amount',
                'personal_access_tokens.last_used_at',
                'members.online_status',
            ];
            $results = $query->select($selectDatas)
            ->leftJoin('merchants', 'merchants.id', '=', 'members.merchant_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
            ->leftJoin('wallets', 'wallets.member_id', '=', 'members.id')
            ->leftJoin('personal_access_tokens', 'personal_access_tokens.tokenable_id', '=', 'members.id')
            ->with(['merchant', 'merchant.currency', 'wallet'])->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);
            if (! empty($results)) {
                foreach ($results as $key => $value) {
                    if ($value['last_used_at'] < $twoHours) {
                        $results[$key]['online_status'] = 'offline';
                    } else {
                        $results[$key]['online_status'] = 'online';
                    }
                }
            }

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            Member::create($data);

            return response()->json([
                'messages' => ['Member created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($member, array $data): JsonResponse
    {
        try {
            $member->update($data);

            return response()->json([
                'messages' => ['Member updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($member): JsonResponse
    {
        try {
            $member->delete();

            return response()->json([
                'messages' => ['Member deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function updateLoginStatus(): JsonResponse
    {
        $response = [];
        // More than two hours
        $result = DB::select('select * from `personal_access_tokens` where (`last_used_at` is null or `last_used_at` < DATE_SUB(NOW(), INTERVAL 30 MINUTE))');
        if (! empty($result)) {
            foreach ($result as $row) {
                Member::where('id', $row->tokenable_id)->update(['online_status' => 'offline']);
                DB::table('personal_access_tokens')->where('id', $row->id)->delete();
            }
            $response = ['success' => true];
        } else {
            $response = ['message' => 'No Members'];
        }

        return response()->json($response, 200);
    }
}
