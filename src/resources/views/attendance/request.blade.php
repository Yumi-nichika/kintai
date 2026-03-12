@extends('layouts.common')

@section('title')
申請一覧画面（一般ユーザー）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('main')
<div class="content">
    <div class="list">
        <h2 class="heading">申請一覧</h2>

        <!-- 切り替え用 -->
        <input type="radio" name="tab" id="tab-wait"
            {{ request('tab') !== 'approve' ? 'checked' : '' }}>

        <input type="radio" name="tab" id="tab-approve"
            {{ request('tab') === 'approve' ? 'checked' : '' }}>

        <div class="toggle_buttons">
            <label for="tab-wait">
                <a href="{{ request()->fullUrlWithQuery(['tab' => null]) }}" class="tab-link">承認待ち</a>
            </label>
            <label for="tab-approve">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'approve']) }}" class="tab-link">承認済み</a>
            </label>
        </div>

        <div class="toggle_line"></div>

        <table class="attendance-list-table">
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>

            <tr>
                <td>
                </td>
                <td>
                </td>
                <td>
                </td>
                <td>
                </td>
                <td>
                </td>
                <td>
                    <a class="button_detail" href="/attendance/detail/">詳細</a>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection