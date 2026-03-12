@extends('layouts.common')

@section('title')
勤怠詳細画面（一般ユーザー）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('main')
<div class="content">
    <form class="form" action="/attendance/detail/{{ $attendance->id }}" method="post">
        <h2 class="heading">勤怠詳細</h2>

        <table class="attendance-detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ \Carbon\Carbon::parse($attendance->work_date)->isoFormat('YYYY年M月D日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div class="input_texts">
                        <input type="text" name="start_time" value="{{ old('start_time', substr($attendance->start_time,0,5)) }}">
                        <span>～</span>
                        <input type="text" name="end_time" value="{{ old('end_time', substr($attendance->end_time,0,5)) }}">
                    </div>
                </td>
            </tr>

            @foreach($breaks as $break)
            <tr>
                <th>休憩{{ $loop->index == 0 ? '' : $loop->index + 1 }}</th>
                <td>
                    <input type="hidden" name="break_ids[]" value="{{ $break->id }}">
                    <div class="input_texts">
                        <input type="text" name="break_start_time[]" value="{{ old('break_start_time.' . $loop->index, substr($break->break_start_time,0,5)) }}">
                        <span>～</span>
                        <input type="text" name="break_end_time[]" value="{{ old('break_end_time.' . $loop->index, substr($break->break_end_time,0,5)) }}">
                    </div>
                </td>
            </tr>
            @endforeach
            <tr>
                <th>備考</th>
                <td>
                    <textarea name="note">{{ old('note', $attendance->note) }}</textarea>
                </td>
            </tr>
        </table>

        <div class="button-area">
            <button type="submit" class="button button_black">修正</button>
        </div>
    </form>
</div>
@endsection