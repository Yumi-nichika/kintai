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
                <a href="/">
                    <img src="{{ asset('img/logo.png') }}">
                </a>
            </div>

            <div class="header__right">
                <!-- 一般ユーザー用 -->
                <a class="button button_black" href="">勤怠</a>
                <a class="button button_black" href="">勤怠一覧</a>
                <a class="button button_black" href="">申請</a>

                <!-- 管理者用 -->
                <!-- <a class="button button_black" href="">勤怠一覧</a>
                <a class="button button_black" href="">スタッフ一覧</a>
                <a class="button button_black" href="">申請一覧</a> -->

                <!-- 共通 -->
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="button button_black">ログアウト</button>
                </form>

            </div>
        </div>
    </header>
    <main>
        @yield('main')
    </main>
    @yield('js')
</body>

</html>