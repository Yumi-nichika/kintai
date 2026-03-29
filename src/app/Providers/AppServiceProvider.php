<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.common', function ($view) {
            if (Auth::check()) {
                $headerAttendance = Attendance::where('user_id', Auth::id())
                    ->whereDate('work_date', now())
                    ->first();
            } else {
                $headerAttendance = null;
            }

            $view->with('headerAttendance', $headerAttendance);
        });
    }
}
