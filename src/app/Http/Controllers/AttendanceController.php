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
    public function showAttendanceList(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $date = Carbon::createFromFormat('Y-m', $month);

        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        $dates = CarbonPeriod::create($start, $end);


        /* 休憩時間を先に合計 */
        $breaks = DB::table('break_times')
            ->selectRaw("attendance_id,
            SUM(TIME_TO_SEC(TIMEDIFF(break_end_time, break_start_time))) as break_sec")
            ->groupBy('attendance_id');


        $attendances = DB::table('attendances as a')
            ->leftJoinSub($breaks, 'b', function ($join) {
                $join->on('a.id', '=', 'b.attendance_id');
            })
            ->selectRaw("a.id,
            a.work_date,
            a.start_time,
            a.end_time,
            TIME_FORMAT(SEC_TO_TIME(IFNULL(b.break_sec,0)),'%k:%i') as break_time,
            TIME_FORMAT(SEC_TO_TIME(FLOOR((TIME_TO_SEC(TIMEDIFF(a.end_time,a.start_time)) - IFNULL(b.break_sec,0)) / 60) * 60),'%k:%i') as work_time")
            ->where('a.user_id', auth()->id())
            ->whereBetween('a.work_date', [$start, $end])
            ->get()
            ->keyBy('work_date');

        return view('attendance.list', compact('dates', 'attendances', 'date'));
    }

    /**
     * 申請一覧画面表示
     */
    public function showRequestList(Request $request)
    {
        return view('attendance.request');
    }

    /**
     * 勤怠詳細画面表示
     */
    public function showDetail($id)
    {
        $attendance = DB::table('attendances as a')
            ->select(
                'a.id',
                'a.work_date',
                'a.start_time',
                'a.end_time',
                'a.note',
                'u.name'
            )
            ->join('users as u', 'a.user_id', '=', 'u.id')
            ->where('a.id', $id)
            ->first();


        $breaks = DB::table('break_times')
            ->select(
                'id',
                'break_start_time',
                'break_end_time'
            )
            ->where('attendance_id', $id)
            ->get();

        return view('attendance.detail', compact('attendance', 'breaks'));
    }

    /**
     * 勤怠申請
     */
    public function request() {}
}
