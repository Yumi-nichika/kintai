<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__left">
                <img src="{{ asset('img/logo.png') }}">
            </div>

            @if (!request()->routeIs('verification.notice'))
            @auth
            <div class="header__right">
                @if (Auth::user()->is_admin)
                <!-- 管理者 -->
                <a class="button button_black" href="/admin/attendance/list">勤怠一覧</a>
                <a class="button button_black" href="/admin/staff/list">スタッフ一覧</a>
                <a class="button button_black" href="/stamp_correction_request/list">申請一覧</a>
                @else
                <!-- 一般ユーザー -->
                @if($headerAttendance && $headerAttendance->end_time)
                <!-- 退勤済み -->
                <a class="button button_black" href="/attendance/list">今月の出勤一覧</a>
                <a class="button button_black" href="/stamp_correction_request/list">申請一覧</a>
                @else
                <!-- 通常 -->
                <a class="button button_black" href="/attendance">勤怠</a>
                <a class="button button_black" href="/attendance/list">勤怠一覧</a>
                <a class="button button_black" href="/stamp_correction_request/list">申請</a>
                @endif
                @endif

                <!-- 共通 -->
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="button button_black">ログアウト</button>
                </form>
            </div>
            @endauth
            @endif
        </div>
    </header>
    <main>
        @yield('main')
    </main>
    @yield('js')
</body>

</html>