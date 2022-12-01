<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantFormRequest;
use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function __construct(private MerchantService $merchantService)
    {
    }

    /**
     * It takes a request, creates a random token, adds the token to the request data, and then passes
     * the data to the service
     *
     * @param MerchantFormRequest request The request object
     * @return The return value is the result of the store method in the MerchantService class.
     */
    public function store(MerchantFormRequest $request)
    {
        $token = Str::random(32);
        $data = $request->all();
        $data['token'] = $token;

        return $this->merchantService->store($data);
    }

    public function update(MerchantFormRequest $request, Merchant $merchant)
    {
        return $this->merchantService->update($merchant, $request->all());
    }

    public function delete(Merchant $merchant)
    {
        return $this->merchantService->delete($merchant);
    }

    public function get(Merchant $merchant)
    {
        return response()->json(Merchant::with(['market', 'currency', 'betLimit', 'betLimit.limitSettings', 'market.oddSettings'])->find($merchant['id']), 200);
    }

    public function all()
    {
        return $this->merchantService->all();
    }

    public function paginate(Request $request)
    {
        return $this->merchantService->paginate($request);
    }

    public function getToken(Request $request)
    {
        return $this->merchantService->getToken($request->all());
    }

    public function getMerchantById($id)
    {
        return $this->merchantService->getMerchantById($id);
    }

    public function fetchToken(MerchantFormRequest $merchant)
    {
        return $this->merchantService->fetchToken($merchant);
    }
}
