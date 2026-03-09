<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

        return view('attendance.index', compact('attendance', 'isBreaking'));
    }

    /**
     * 出勤
     */
    public function start()
    {
        Attendance::create([
            'user_id' => auth()->id(),
            'work_date' => now(),
            'start_time' => now()->format('H:i:00'),
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
            'end_time' => now()->format('H:i:00')
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
            'break_start_time' => now()->format('H:i:00')
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
            'break_end_time' => now()->format('H:i:00')
            ]);

        return back();
    }

    /**
     * 勤怠一覧画面表示
     */
    public function show(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $date = Carbon::createFromFormat('Y-m', $month);

        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();
        $dates = CarbonPeriod::create($start, $end);

        $attendances = DB::table('attendances as a')
            ->leftJoin('break_times as b', 'a.id', '=', 'b.attendance_id')
            ->selectRaw("
            a.id,
            a.work_date,
            a.start_time,
            a.end_time,

            TIME_FORMAT(
                SEC_TO_TIME(
                    IFNULL(SUM(
                        TIME_TO_SEC(TIMEDIFF(b.break_end_time,b.break_start_time))
                    ),0)
                ),
            '%H:%i') as break_time,

            TIME_FORMAT(
        SEC_TO_TIME(
            FLOOR(
                (
                    TIME_TO_SEC(TIMEDIFF(a.end_time,a.start_time))
                    - IFNULL(SUM(
                        TIME_TO_SEC(TIMEDIFF(b.break_end_time,b.break_start_time))
                    ),0)
                ) / 60
            ) * 60
        ),
    '%H:%i') as work_time
        ")
            ->where('a.user_id', auth()->id())
            ->whereBetween('a.work_date', [$start, $end])
            ->groupBy('a.id')
            ->get()
            ->keyBy('work_date');

        return view('attendance.list', compact('dates', 'attendances', 'date'));
    }
}
