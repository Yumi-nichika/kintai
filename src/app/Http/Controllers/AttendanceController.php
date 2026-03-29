<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $date = Carbon::createFromFormat('Y-m-d', $month . '-01');

        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        $dates = CarbonPeriod::create($start, $end);


        /* 休憩時間を先に合計 */
        $breaks = DB::table('break_times')
            ->selectRaw("attendance_id,
            SUM(TIME_TO_SEC(TIMEDIFF(break_end_time, break_start_time))) as break_sec")
            ->groupBy('attendance_id');


        $attendances = DB::table('attendances as a')
            ->selectRaw("a.id,
            a.work_date,
            a.start_time,
            a.end_time,
            TIME_FORMAT(SEC_TO_TIME(IFNULL(b.break_sec,0)),'%k:%i') as break_time,
            TIME_FORMAT(SEC_TO_TIME(FLOOR((TIME_TO_SEC(TIMEDIFF(a.end_time,a.start_time)) - IFNULL(b.break_sec,0)) / 60) * 60),'%k:%i') as work_time")
            ->leftJoinSub($breaks, 'b', function ($join) {
                $join->on('a.id', '=', 'b.attendance_id');
            })
            ->where('a.user_id', auth()->id())
            ->whereBetween('a.work_date', [$start, $end])
            ->get()
            ->keyBy('work_date');

        return view('attendance.list', compact('dates', 'attendances', 'date'));
    }

    /**
     * 勤怠詳細画面表示
     */
    public function showDetail($id)
    {
        $flg = 0;

        $apply = Apply::with('user')->where('attendance_id', $id)
            ->where('status', 0)
            ->orderBy('created_at', 'desc')->first();

        //承認待ちあり
        if ($apply) {
            $flg = 1;

            $attendance = (object)[
                'id' => $apply->attendance_id,
                'user' => $apply->user,
                'work_date' => $apply->apply_work_date,
                'start_time' => $apply->apply_start_time,
                'end_time' => $apply->apply_end_time,
                'note' => $apply->apply_note,
            ];

            $breaks = ApplyBreakTime::where('apply_id', $apply->id)
                ->get()
                ->map(function ($b) {
                    return (object)[
                        'break_start_time' => $b->apply_break_start_time,
                        'break_end_time' => $b->apply_break_end_time,
                    ];
                });
        }

        //承認待ちなし
        else {
            $attendance = Attendance::with('user')->findOrFail($id);

            $breaks = BreakTime::where('attendance_id', $id)->get();
        }

        return view('attendance.detail', compact('attendance', 'breaks', 'flg'));
    }

    /**
     * 勤怠申請
     */
    public function store(AttendanceRequest $request, $id)
    {
        //appliesテーブルに登録するデータ
        $apply_data = $request->only([
            'apply_work_date',
            'apply_start_time',
            'apply_end_time',
            'apply_note'
        ]);

        $apply_data['user_id'] = auth()->id();
        $apply_data['attendance_id'] = $id;

        //appliesテーブルに保存、id取得
        $apply = Apply::create($apply_data);
        $apply_id = $apply->id;

        $break_ids = $request['break_ids'] ?? [];
        $apply_break_start_times = $request['apply_break_start_times'];
        $apply_break_end_times = $request['apply_break_end_times'];

        //apply_break_timesテーブルに保存
        foreach ($apply_break_start_times as $index => $start_time) {
            // 空行はスキップ（休憩削除）
            if (!$start_time || !$apply_break_end_times[$index]) {
                continue;
            }

            $break_data = [
                'apply_id' => $apply_id,
                'break_time_id' => $break_ids[$index] ?? null,
                'apply_break_start_time' => $start_time,
                'apply_break_end_time' => $apply_break_end_times[$index],
            ];

            ApplyBreakTime::create($break_data);
        }

        return redirect('/stamp_correction_request/list');
    }

    /**
     * 勤怠詳細画面表示（新規登録）
     */
    public function showCreate(Request $request)
    {
        return view('attendance.detail', [
            'attendance' => null,
            'breaks' => [],
            'flg' => 0,
            'date' => $request->date
        ]);
    }


    /**
     * 勤怠申請（新規登録）
     */
    public function create(AttendanceRequest $request)
    {
        //attendancesテーブルに保存
        $attendance = Attendance::create([
            'user_id' => auth()->id(),
            'work_date' => $request->apply_work_date,
            'start_time' => $request->apply_start_time,
            'end_time' => $request->apply_end_time,
            'note' => $request->apply_note,
        ]);

        $attendance_id = $attendance->id;

        // appliesテーブルに登録
        $apply = Apply::create([
            'user_id' => auth()->id(),
            'attendance_id' => $attendance_id,
            'apply_work_date' => $request->apply_work_date,
            'apply_start_time' => $request->apply_start_time,
            'apply_end_time' => $request->apply_end_time,
            'apply_note' => $request->apply_note,
        ]);

        $apply_id = $apply->id;

        $start_times = $request['apply_break_start_times'] ?? [];
        $end_times = $request['apply_break_end_times'] ?? [];

        // 休憩保存
        foreach ($start_times as $index => $start) {

            // 空はスキップ
            if (!$start || !$end_times[$index]) {
                continue;
            }

            $break_time = BreakTime::create([
                'attendance_id' => $attendance_id,
                'break_start_time' => $start,
                'break_end_time' => $end_times[$index],
            ]);

            ApplyBreakTime::create([
                'apply_id' => $apply_id,
                'break_time_id' => $break_time->id,
                'apply_break_start_time' => $start,
                'apply_break_end_time' => $end_times[$index],
            ]);
        }

        return redirect('/stamp_correction_request/list');
    }
}
