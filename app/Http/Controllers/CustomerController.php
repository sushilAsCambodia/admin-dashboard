<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\MemberService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private MemberService $memberService)
    {
    }

    public function store(Request $request)
    {
        return $this->memberService->store($request->all());
    }

    public function update(Request $request, Member $member)
    {
        return $this->memberService->update($member, $request->all());
    }

    public function delete(Member $member)
    {
        return $this->memberService->delete($member);
    }

    public function get(Member $member)
    {
        return response()->json($member, 200);
    }

    public function all()
    {
        return response()->json(Member::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->memberService->paginate($request);
    }
}
