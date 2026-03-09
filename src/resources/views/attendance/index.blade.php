@extends('layouts.common')

@section('title')
勤怠登録画面（一般ユーザー）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('main')
<div class="content">
    <div class="form">
        <p class="status">
            @if(!$attendance)
            勤務外
            @elseif($attendance && !$attendance->end_time)
            @if($isBreaking)
            休憩中
            @else
            出勤中
            @endif
            @else
            退勤済
            @endif
        </p>

        <p class="date">{{ \Carbon\Carbon::now()->isoFormat('YYYY年M月D日（ddd）') }}</p>

        <div id="clock" class="clock"></div>


        <!-- 出勤してない -->
        @if(!$attendance)
        <form method="POST" action="/attendance/start" class="button-position">
            @csrf
            <button type="submit" class="button button_black">出勤</button>
        </form>

        <!-- 退勤していない -->
        @elseif($attendance && !$attendance->end_time)

        <!-- 休憩中 -->
        @if($isBreaking)
        <form method="POST" action="/attendance/break/end" class="button-position">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            <button type="submit" class="button button_white">休憩戻</button>
        </form>

        <!-- 勤務中 -->
        @else
        <div class="button-position">
            <form method="POST" action="/attendance/end">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <button type="submit" class="button button_black">退勤</button>
            </form>

            <form method="POST" action="/attendance/break/start">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <button type="submit" class="button button_white">休憩入</button>
            </form>
        </div>
        @endif

        <!-- 退勤済み -->
        @else
        <p class="msg">お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
    function updateClock() {
        const now = new Date();

        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');

        document.getElementById('clock').textContent = hh + ':' + mm;
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>


<script>
    function setTime() {
        const now = new Date();

        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('time').value = hh + ':' + mm;
    }
</script>
@endsection