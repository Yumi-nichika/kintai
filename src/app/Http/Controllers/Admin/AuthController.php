<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $credentials['is_admin'] = 1;

        if (Auth::attempt($credentials)) {

            return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'email' => 'ログインできません',
        ]);
    }
}
