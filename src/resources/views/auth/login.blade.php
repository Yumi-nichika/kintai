@extends('layouts.common')

@section('title')
ログイン画面（一般ユーザー）
@endsection

@section('main')
<div class="content">
    <h1>ログイン</h1>
    <div class="content-form">
        <form class="form" action="/login" method="post" novalidate>
            @csrf
            <div class="form__group">
                <div class="form__group-title">
                    メールアドレス
                </div>
                <div class="form__group-content">
                    <div class="form__input--text">
                        <input type="email" name="email" value="{{ old('email') }}" />
                    </div>
                    @error('email')
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <div class="form__group-title">
                    パスワード
                </div>
                <div class="form__group-content">
                    <div class="form__input--text">
                        <input type="password" name="password" />
                    </div>
                    @error('password')
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                </div>
            </div>

            <div class="form-button">
                <button class="button button_black w100" type="submit">ログインする</button>
                <a class="button_blue" href="/login">会員登録はこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection