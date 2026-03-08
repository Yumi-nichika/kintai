@extends('layouts.common')

@section('title')
会員登録画面（一般ユーザー）
@endsection

@section('main')
<div class="content">
    <h1>会員登録</h1>
    <form class="form" action="/register" method="post" novalidate>
        @csrf
        <div class="group">
            <div class="group-title">
                名前
            </div>
            <div class="group-content">
                <input type="text" name="name" value="{{ old('name') }}" />
                @error('name')
                <ul class="error">
                    <li>{{ $message }}</li>
                </ul>
                @enderror
            </div>
        </div>
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
        <div class="group">
            <div class="group-title">
                パスワード確認
            </div>
            <div class="group-content">
                <input type="password" name="password_confirmation" />
            </div>
        </div>
        <div class="form-button">
            <button type="submit" class="button button_black w100">登録する</button>
            <a class="button_blue" href="/login">ログインはこちら</a>
        </div>
    </form>
</div>
@endsection