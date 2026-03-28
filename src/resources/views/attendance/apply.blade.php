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


        <div class="toggle_contents">
            <div class="list wait">
                <table class="attendance-list-table">
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>

                    @foreach($applies as $apply)
                    @if($apply->status == 0)
                    <tr>
                        <td>{{ config('status.' . $apply->status) }}</td>
                        <td>{{ $apply->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($apply->apply_work_date)->format('Y/m/d') }}</td>
                        <td>{{ $apply->apply_note }}</td>
                        <td>{{ $apply->created_at->format('Y/m/d') }}</td>
                        <td>
                            <a class="button_detail" href="/attendance/detail/{{ $apply->attendance_id }}">詳細</a>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </table>
            </div>


            <div class="list approve">
                <table class="attendance-list-table">
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>

                    @foreach($applies as $apply)
                    @if($apply->status == 1)
                    <tr>
                        <td>{{ config('status.' . $apply->status) }}</td>
                        <td>{{ $apply->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($apply->attendance->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $apply->apply_note }}</td>
                        <td>{{ $apply->created_at->format('Y/m/d') }}</td>
                        <td>
                            <a class="button_detail" href="/attendance/detail/{{ $apply->attendance_id }}">詳細</a>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </table>
            </div>
        </div>



    </div>
</div>
@endsection