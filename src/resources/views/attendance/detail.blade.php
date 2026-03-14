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
        @csrf
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
                        <input type="text" name="request_start_time" value="{{ old('request_start_time', substr($attendance->start_time,0,5)) }}">
                        <span>～</span>
                        <input type="text" name="request_end_time" value="{{ old('request_end_time', substr($attendance->end_time,0,5)) }}">
                    </div>
                    @if ($errors->get('request_start_time') || $errors->get('request_end_time'))
                    <ul class="error">
                        @foreach ($errors->get('request_start_time') as $message)
                        <li>{{ $message }}</li>
                        @endforeach

                        @foreach ($errors->get('request_end_time') as $message)
                        <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                    @endif
                </td>
            </tr>

            @foreach($breaks as $break)
            <tr>
                <th>休憩{{ $loop->index == 0 ? '' : $loop->index + 1 }}</th>
                <td>
                    <input type="hidden" name="break_ids[]" value="{{ $break->id }}">
                    <div class="input_texts">
                        <input type="text" name="request_break_start_time[]" value="{{ old('request_break_start_time.' . $loop->index, substr($break->break_start_time,0,5)) }}">
                        <span>～</span>
                        <input type="text" name="request_break_end_time[]" value="{{ old('request_break_end_time.' . $loop->index, substr($break->break_end_time,0,5)) }}">
                    </div>
                    @error('request_break_start_time.' . $loop->index)
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                    @error('request_break_end_time.' . $loop->index)
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                </td>
            </tr>
            @endforeach
            <tr>
                <th>備考</th>
                <td>
                    <textarea name="request_note">{{ old('request_note') }}</textarea>
                    @error('request_note')
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                </td>
            </tr>
        </table>

        <div class="button-area">
            <button type="submit" class="button button_black">修正</button>
        </div>
    </form>
</div>
@endsection