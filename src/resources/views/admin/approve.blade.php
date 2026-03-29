@extends('layouts.common')

@section('title')
修正申請承認画面（管理者）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('main')
<div class="content">
    <form class="form" action="/stamp_correction_request/approve/{{ $attendance->id }}" method="post">
        @csrf
        <h2 class="heading">勤怠詳細</h2>

        <table class="attendance-detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ \Carbon\Carbon::parse($attendance->attendance->work_date)->isoFormat('YYYY年') }}　{{ \Carbon\Carbon::parse($attendance->attendance->work_date)->isoFormat('M月D日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div class="input_texts">
                        <p>{{ substr($attendance->start_time,0,5) }}</p>
                        <span>～</span>
                        <p>{{ substr($attendance->end_time,0,5) }}</p>
                    </div>
                </td>
            </tr>

            @foreach($breaks as $break)
            <tr>
                <th>休憩{{ $loop->index == 0 ? '' : $loop->index + 1 }}</th>
                <td>
                    <div class="input_texts">
                        <p>{{ substr($break->break_start_time,0,5) }}</p>
                        <span>～</span>
                        <p>{{ substr($break->break_end_time,0,5) }}</p>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr>
                <th>備考</th>
                <td>
                    <p class="note">{{ $attendance->apply_note }}</p>
                </td>
            </tr>
        </table>

        <div class="button-area">
            @if(!$attendance->status)
            <button type="submit" class="button button_black">承認</button>
            @else
            <button type="button" class="button button_dark">承認済み</button>
            @endif
        </div>
    </form>
</div>
@endsection