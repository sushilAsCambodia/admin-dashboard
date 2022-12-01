<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            $result = [
                'responseDto' => [
                    'responseCode'        => 401,
                    'responseDescription' => 'Unauthenticated',
                ],
                'body'        => [
                    'msg' => 'Unauthorized user',
                ],
                'messages'    => ['Session expired'],
            ];

            return response()->json($result, 401);
        }

        return route('login');
    }
}
