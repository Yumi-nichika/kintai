@extends('layouts.common')

@section('title')
ログイン画面（一般ユーザー）
@endsection

@section('main')
<div class="content">
    <h1>ログイン</h1>
    <form class="form" action="/login" method="post" novalidate>
        @csrf
        <div class="group">
            <div class="group-title">
                メールアドレス
            </div>
            <div class="group-content">
                <input type="email" name="email" value="{{ old('email') }}" />
                @error('email')
                <ul class="error">
                    <li>{{ $message }}</li>
                </ul>
                @enderror
            </div>
        </div>
        <div class="group">
            <div class="group-title">
                パスワード
            </div>
            <div class="group-content">
                <input type="password" name="password" />
                @error('password')
                <ul class="error">
                    <li>{{ $message }}</li>
                </ul>
                @enderror
            </div>
        </div>

        <div class="form-button">
            <button type="submit" class="button button_black w100">ログインする</button>
            <a class="button_blue" href="/register">会員登録はこちら</a>
        </div>
    </form>
</div>
@endsection