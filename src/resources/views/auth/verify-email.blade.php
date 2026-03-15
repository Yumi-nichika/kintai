@extends('layouts.common')

@section('title')
メール認証誘導画面
@endsection

@section('main')
<div class="content">
    <div class="form">
        <p>登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。</p>

        <div class="form-button">
            <a class="button button_gray" href="http://localhost:8025" target="_blank">認証はこちらから</a>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="button_blue" type="submit">認証メールを再送する</button>
            </form>

            @if (session('resent') == 'verification-link-sent')
            <p style="color: green;">
                新しい認証メールを送信しました。
            </p>
            @endif
        </div>
    </div>
</div>
@endsection