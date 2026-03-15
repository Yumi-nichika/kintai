<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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

        //連続ログインブロック
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
