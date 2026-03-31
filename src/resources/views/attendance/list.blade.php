@extends('layouts.common')

@section('title')
勤怠一覧画面（一般ユーザー）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
@endsection

@section('main')
<div class="content">
    <div class="list">
        <h2 class="heading">勤怠一覧</h2>

        <div class="calendar-nav">
            <a class="calendar-link" href="?month={{ $date->copy()->subMonthNoOverflow()->format('Y-m') }}">
                ← 前月
            </a>
            <div class="calendar-center">
                <span class="material-icons">calendar_month</span>
                <span class="calendar-text">
                    {{ $date->format('Y/m') }}
                </span>
            </div>
            <a class="calendar-link" href="?month={{ $date->copy()->addMonthNoOverflow()->format('Y-m') }}">
                翌月 →
            </a>
        </div>


        <table class="attendance-list-table">
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>

            @foreach($dates as $d)

            @php
            $attendance = $attendances[$d->toDateString()] ?? null;
            @endphp

            <tr>
                <td>
                    {{ mb_convert_kana($d->isoFormat('MM/DD（ddd）'),'N') }}
                </td>
                <td>
                    {{ $attendance ? mb_convert_kana(substr($attendance->start_time,0,5),'N') : '' }}
                </td>
                <td>
                    {{ $attendance ? mb_convert_kana(substr($attendance->end_time,0,5),'N') : '' }}
                </td>
                <td>
                    {{ $attendance && $attendance->break_time !== '0:00' ? mb_convert_kana($attendance->break_time,'N') : '' }}
                </td>
                <td>
                    {{ $attendance ? mb_convert_kana($attendance->work_time,'N') : '' }}
                </td>
                <td>
                    @if($attendance)
                    <a class="button_detail" href="/attendance/detail/{{ $attendance->id }}">詳細</a>
                    @else
                    <a class="button_detail" href="/attendance/create?date={{ $d->toDateString() }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection