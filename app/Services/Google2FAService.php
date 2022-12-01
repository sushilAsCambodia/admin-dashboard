<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class Google2FAService
{
    public function verifyUser($request): JsonResponse
    {
        try {
            $user = User::where('email', $request->name)->first();
            info($user);
            info(Hash::check($request->password, $user?->password));

            if (! $user || ! Hash::check($request->password, $user?->password)) {
                return response()->json(['messages' => ['Incorrect username or password']], 400);
            }

            if (! is_null($user->google2fa_secret)) {
                return response()->json(['messages' => ['This account is already bound to Google 2FA']], 400);
            }

            // Generate QR Code and Secret
            $google2fa = app('pragmarx.google2fa');
            $secretKey = $google2fa->generateSecretKey(32);
            $QRImage = $google2fa->getQRCodeInline(
                'Malaysia Lottery',
                $request['name'],
                $secretKey
            );

            return response()->json([
                'qr_image' => $QRImage,
                'secret_key' => $secretKey,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function verifyCode($request)
    {
        try {
            $google2fa = app('pragmarx.google2fa');
            $user = User::where('email', $request->name)->first();

            if ($request->login && ! $user->google2fa_secret) {
                return response()->json(['messages' => ['Please Click on Google Authenticator and bind your Account']], 400);
            }

            $validate = $google2fa->verifyKey($request->login ? $user->google2fa_secret : $request->secret_key, $request->code);

            if (! $validate) {
                throw new Exception('Invalid Code');
            }

            if (! $request->login) {
                $user->update(['google2fa_secret' => $request->secret_key]);
            }

            return response()->json(['messages' => ['Authetication code verified successfully']], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function enableGa($request)
    {
        $user = User::where('email', $request->name)->where('enable_ga', 1)->first();
        if ($user) {
            return response()->json(['messages' => ['user is enable ga'], 'status' => true], 200);
        }

        return response()->json(['messages' => ['user is not enable ga'], 'status' => false], 200);
    }

    public function enableGoogle2FA($request, $user)
    {
        try {
            $google2fa = app('pragmarx.google2fa');

            if ($user) {
                $user->update(['google2fa_secret' => $google2fa->generateSecretKey()]);

                return response()->json(['messages' => 'Google 2FA enabled successfully.'], 200);
            }

            $user = User::where('email', $request->name)->first();
            $user->update(['google2fa_secret' => $request->secret]);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
