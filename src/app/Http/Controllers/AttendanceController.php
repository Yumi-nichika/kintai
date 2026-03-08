<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面表示
     */
    public function index()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', now())
            ->first();

        $isBreaking = false;

        if ($attendance) {
            $isBreaking = $attendance->breaks->whereNull('break_end_time')->isNotEmpty();
        }

        return view('attendance', compact('attendance', 'isBreaking'));
    }

    /**
     * 出勤
     */
    public function start()
    {
        Attendance::create([
            'user_id' => auth()->id(),
            'work_date' => now(),
            'start_time' => now(),
        ]);

        return back();
    }

    /**
     * 退勤
     */
    public function end(Request $request)
    {
        $attendance = Attendance::find($request->attendance_id);

        $attendance->update([
            'end_time' => now()
        ]);

        return back();
    }

    /**
     * 休憩入
     */
    public function breakStart(Request $request)
    {
        BreakTime::create([
            'attendance_id' => $request->attendance_id,
            'break_start_time' => now()
        ]);

        return back();
    }

    /**
     * 休憩戻
     */
    public function breakEnd(Request $request)
    {
        $break = BreakTime::where('attendance_id', $request->attendance_id)
            ->whereNull('break_end_time')
            ->latest()
            ->first();

        $break->update([
            'break_end_time' => now()
        ]);

        return back();
    }
}
