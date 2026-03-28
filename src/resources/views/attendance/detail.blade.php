@extends('layouts.common')

@section('title')
勤怠詳細画面（一般ユーザー）
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('main')
<div class="content">
    <form class="form"
        action="{{ $attendance ? '/attendance/detail/'.$attendance->id : '/attendance/create' }}"
        method="post">
        @csrf
        <h2 class="heading">勤怠詳細</h2>

        <table class="attendance-detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name ?? auth()->user()->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>
                    @if(isset($attendance))
                    {{ \Carbon\Carbon::parse($attendance->work_date)->isoFormat('YYYY年M月D日') }}
                    <input type="hidden" name="apply_work_date" value="{{ $attendance->work_date }}">
                    @else
                    {{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年M月D日') }}
                    <input type="hidden" name="apply_work_date" value="{{ $date }}">
                    @endif
                </td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    @if(!$flg)
                    <div class="input_texts">
                        <input type="text" name="apply_start_time" value="{{ old('apply_start_time', $attendance && $attendance->start_time ? substr($attendance->start_time,0,5) : '') }}">
                        <span>～</span>
                        <input type="text" name="apply_end_time" value="{{ old('apply_end_time', $attendance && $attendance->end_time ? substr($attendance->end_time,0,5) : '') }}">
                    </div>
                    @if ($errors->get('apply_start_time') || $errors->get('apply_end_time'))
                    <ul class="error">
                        @foreach ($errors->get('apply_start_time') as $message)
                        <li>{{ $message }}</li>
                        @endforeach

                        @foreach ($errors->get('apply_end_time') as $message)
                        <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                    @endif
                    @else
                    <div class="input_texts">
                        <p>{{ substr($attendance->start_time,0,5) }}</p>
                        <span>～</span>
                        <p>{{ substr($attendance->end_time,0,5) }}</p>
                    </div>
                    @endif
                </td>
            </tr>

            @foreach($breaks as $break)
            <tr>
                <th>休憩{{ $loop->index == 0 ? '' : $loop->index + 1 }}</th>
                <td>
                    @if(!$flg)
                    <input type="hidden" name="break_ids[]" value="{{ $break->id }}">
                    <div class="input_texts">
                        <input type="text" name="apply_break_start_times[]" value="{{ old('apply_break_start_times.' . $loop->index, substr($break->break_start_time,0,5)) }}">
                        <span>～</span>
                        <input type="text" name="apply_break_end_times[]" value="{{ old('apply_break_end_times.' . $loop->index, substr($break->break_end_time,0,5)) }}">
                    </div>
                    @error('apply_break_start_times.' . $loop->index)
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                    @error('apply_break_end_times.' . $loop->index)
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                    @else
                    <div class="input_texts">
                        <p>{{ substr($break->break_start_time,0,5) }}</p>
                        <span>～</span>
                        <p>{{ substr($break->break_end_time,0,5) }}</p>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
            @if(!$flg)
            <tr>
                <th>休憩{{ count($breaks) == 0 ? '' : count($breaks) + 1 }}</th>
                <td>
                    <div class="input_texts">
                        <input type="text" name="apply_break_start_times[]" value="{{ old('apply_break_start_times.' . count($breaks)) }}">
                        <span>～</span>
                        <input type="text" name="apply_break_end_times[]" value="{{ old('apply_break_end_times.' . count($breaks)) }}">
                    </div>
                    @error('apply_break_start_times.' . count($breaks))
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                    @error('apply_break_end_times.' . count($breaks))
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                </td>
            </tr>
            @endif
            <tr>
                <th>備考</th>
                <td>
                    @if(!$flg)
                    <textarea name="apply_note">{{ old('apply_note', $attendance && $attendance->note ? $attendance->note : '') }}</textarea>
                    @error('apply_note')
                    <ul class="error">
                        <li>{{ $message }}</li>
                    </ul>
                    @enderror
                    @else
                    <p class="note">{{ $attendance->note }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <div class="button-area">
            @if(!$flg)
            <button type="submit" class="button button_black">修正</button>
            @else
            <p class="msg_detail">*承認待ちのため修正はできません。</p>
            @endif
        </div>
    </form>
</div>
@endsection