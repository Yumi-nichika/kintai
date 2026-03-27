<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        //管理者はメール認証不要、管理者画面へリダイレクト
        if ($user->is_admin) {
            return redirect('/admin/attendance/list');
        }

        //一般ユーザーはメール認証チェック
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended('/attendance');
    }
}
