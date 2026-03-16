<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Apply;
use App\Models\ApplyBreakTime;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面表示
     */
    public function showAttendanceList(Request $request)
    {
        $date = Carbon::parse($request->date ?? today());

        /* 休憩時間を先に合計 */
        $breaks = DB::table('break_times')
            ->selectRaw("attendance_id,
            SUM(TIME_TO_SEC(TIMEDIFF(break_end_time, break_start_time))) as break_sec")
            ->groupBy('attendance_id');


        $attendances = DB::table('attendances as a')
            ->selectRaw("u.name, a.id,
            a.work_date,
            a.start_time,
            a.end_time,
            TIME_FORMAT(SEC_TO_TIME(IFNULL(b.break_sec,0)),'%k:%i') as break_time,
            TIME_FORMAT(SEC_TO_TIME(FLOOR((TIME_TO_SEC(TIMEDIFF(a.end_time,a.start_time)) - IFNULL(b.break_sec,0)) / 60) * 60),'%k:%i') as work_time")
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoinSub($breaks, 'b', function ($join) {
                $join->on('a.id', '=', 'b.attendance_id');
            })
            ->whereDate('a.work_date', $date)
            ->get();

        return view('admin.attendance-list', compact('attendances', 'date'));
    }

    /**
     * 勤怠詳細画面表示
     */
    public function showDetail($id)
    {
        $flg = 0;

        $attendance = Apply::with('user')
            ->selectRaw("attendance_id as id, user_id, apply_start_time as start_time, apply_end_time as end_time, apply_note")
            ->where('attendance_id', $id)
            ->where('status', 0)
            ->orderBy('created_at', 'desc')->first();

        //承認待ちの申請あり
        if ($attendance) {
            $flg = 1;

            $breaks = ApplyBreakTime::selectRaw("apply_break_start_time as break_start_time, apply_break_end_time as break_end_time")
                ->where('apply_id', $attendance->id)
                ->orderBy('break_time_id')
                ->get();
        }

        //承認待ちの申請なし
        else {
            $attendance = Attendance::with('user')
                ->where('id', $id)
                ->first();

            $breaks = BreakTime::where('attendance_id', $id)
                ->orderBy('id')
                ->get();
        }


        return view('admin.detail', compact('attendance', 'breaks', 'flg'));
    }

    /**
     * スタッフ一覧画面表示
     */
    public function showStaffList()
    {
        $users = User::where('is_admin', 0)->get();
        return view('admin.staff-list', compact('users'));
    }

    /**
     * 申請一覧画面表示
     */
    public function showApplyList()
    {
        $applies = Apply::with('user', 'attendance')->get();
        return view('admin.apply-list', compact('applies'));
    }

    /**
     * 修正申請承認画面
     */
    public function showApprove($attendance_correct_request_id)
    {
        $attendance = Apply::with('user')
            ->selectRaw("id, user_id, apply_start_time as start_time, apply_end_time as end_time, apply_note, status")
            ->where('id', $attendance_correct_request_id)
            ->first();

        $breaks = ApplyBreakTime::selectRaw("apply_break_start_time as break_start_time, apply_break_end_time as break_end_time")
            ->where('apply_id', $attendance_correct_request_id)
            ->orderBy('break_time_id')
            ->get();


        return view('admin.approve', compact('attendance', 'breaks'));
    }

    /**
     * 承認
     */
    public function approve($attendance_correct_request_id)
    {

    }
}
