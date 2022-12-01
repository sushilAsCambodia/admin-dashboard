<?php

namespace App\Http\Controllers;

use App\Http\Requests\WhitelistIPFormRequest;
use App\Models\WhitelistIP;
use App\Services\WhitelistIPService;
use Illuminate\Http\Request;

class WhitelistIPController extends Controller
{
    public function __construct(private WhitelistIPService $ipService)
    {
    }

    public function store(WhitelistIPFormRequest $request)
    {
        return $this->ipService->store($request->all());
    }

    public function update(WhitelistIPFormRequest $request, WhitelistIP $ip)
    {
        return $this->ipService->update($ip, $request->all());
    }

    public function delete(WhitelistIP $ip)
    {
        return $this->ipService->delete($ip);
    }

    public function get(WhitelistIP $ip)
    {
        return response()->json($ip, 200);
    }

    public function all()
    {
        return response()->json(WhitelistIP::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->ipService->paginate($request);
    }
}
