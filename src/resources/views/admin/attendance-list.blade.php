@extends('layouts.common')

@section('title')
勤怠一覧画面（管理者）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
@endsection

@section('main')
<div class="content">
    <div class="list">
        <h2 class="heading">{{ \Carbon\Carbon::now()->isoFormat('YYYY年M月D日') }}の勤怠</h2>

        <div class="calendar-nav">
            <a class="calendar-link" href="?date={{ $date->copy()->subDay()->format('Y-m-d') }}">
                ← 前日
            </a>
            <div class="calendar-center">
                <span class="material-icons">calendar_month</span>
                <span class="calendar-text">
                    {{ $date->format('Y/m/d') }}
                </span>
            </div>
            <a class="calendar-link" href="?date={{ $date->copy()->addDay()->format('Y-m-d') }}">
                翌日 →
            </a>
        </div>


        <table class="attendance-list-table">
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>

            @foreach($attendances as $attendance)
            <tr>
                <td>
                    {{ $attendance->name }}
                </td>
                <td>
                    {{ mb_convert_kana(substr($attendance->start_time,0,5),'N') }}
                </td>
                <td>
                    {{ mb_convert_kana(substr($attendance->end_time,0,5),'N') }}
                </td>
                <td>
                    {{ mb_convert_kana($attendance->break_time,'N') }}
                </td>
                <td>
                    {{ mb_convert_kana($attendance->work_time,'N') }}
                </td>
                <td>
                    <a class="button_detail" href="/admin/attendance/{{ $attendance->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection