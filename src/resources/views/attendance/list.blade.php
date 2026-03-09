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
        <div class="month-nav">

            <a class="month-link" href="?month={{ $date->copy()->subMonth()->format('Y-m') }}">
                ← 前月
            </a>

            <div class="month-center">
                <span class="material-icons">calendar_month</span>
                <span class="month-text">
                    {{ $date->format('Y/m') }}
                </span>
            </div>

            <a class="month-link" href="?month={{ $date->copy()->addMonth()->format('Y-m') }}">
                翌月 →
            </a>

        </div>


        <table class="attendance-table">
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>勤務時間</th>
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
                    {{ $attendance ? mb_convert_kana(\Carbon\Carbon::createFromFormat('H:i',$attendance->break_time)->format('G:i'),'N') : '' }}
                </td>
                <td>
                    {{ $attendance ? mb_convert_kana(\Carbon\Carbon::createFromFormat('H:i',$attendance->work_time)->format('G:i'),'N') : '' }}
                </td>
                <td>
                    @if($attendance)
                    <a class="button_detail" href="/attendance/detail/{{ $attendance->id }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection