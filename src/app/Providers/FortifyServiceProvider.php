<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Http\Requests\LoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Laravel\Fortify\Contracts\LoginResponse;
use App\Http\Responses\LoginResponse as CustomLoginResponse;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //「/register」で登録画面を開く
        Fortify::registerView(function () {
            return view('auth.register');
        });

        //登録画面処理（バリデーション・DB登録・認証メール送信）
        Fortify::createUsersUsing(CreateNewUser::class);

        //「/login」でログイン画面を開く
        Fortify::loginView(function () {
            return view('auth.login');
        });

        //ログイン時のバリデーションをカスタム
        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

        //認証ロジック（管理者・一般ユーザー分離）
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                //管理者ログイン：is_admin=1のユーザーのみ許可
                if ($request->boolean('is_admin')) {
                    return $user->is_admin ? $user : null;
                }
                //一般ユーザーログイン：管理者以外のみ許可
                return !$user->is_admin ? $user : null;
            }

            return null;
        });

        //連続ログインブロック
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
