<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        // 未認証ならメール送信
        if (!$user->hasVerifiedEmail()) {

            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice');
        }

        return redirect()->intended('/attendance');
    }
}
