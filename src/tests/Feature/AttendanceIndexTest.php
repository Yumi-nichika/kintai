<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

/**
 * 日時取得機能
 * ステータス確認機能
 * 出勤機能
 * 休憩機能
 * 退勤機能
 */
class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_date_and_time_is_displayed_in_correct_format()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        $today = Carbon::now();
        $expected = $today->isoFormat('YYYY年M月D日（ddd）');

        // 本日日付が表示されていること
        $response->assertSee($expected);

        // フォーマットが正しいこと（YYYY年M月D日（ddd））
        $this->assertMatchesRegularExpression(
            '/\d{4}年\d{1,2}月\d{1,2}日（[月火水木金土日]）/u',
            $expected
        );

        // 現在時刻表示用の要素とスクリプトが存在すること（HH:mm形式）
        $response->assertSee('<div id="clock" class="clock"></div>', false);
        $response->assertSee("hh + ':' + mm", false);
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     * 出勤中の場合、勤怠ステータスが正しく表示される
     * 休憩中の場合、勤怠ステータスが正しく表示される
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_attendance_status_flow()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        //出勤ボタンが正しく機能する（画面上に「出勤」ボタンが表示される）
        $response = $this->get('/attendance');
        $response->assertSee('勤務外');
        $response->assertSee('出勤');

        //出勤ボタンが正しく機能する（処理後に画面上に表示されるステータスが「出勤中」になる）
        //休憩ボタンが正しく機能する（画面上に「休憩入」ボタンが表示される）
        //退勤ボタンが正しく機能する（画面上に「退勤」ボタンが表示される）
        $this->post('/attendance/start');
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
        $response->assertSee('退勤');
        $response->assertSee('休憩入');

        $attendanceId = \App\Models\Attendance::where('user_id', $user->id)->first()->id;

        //休憩ボタンが正しく機能する（処理後に画面上に表示されるステータスが「休憩中」になる）
        $this->post('/attendance/break/start', ['attendance_id' => $attendanceId]);
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');

        //休憩は一日に何回でもできる
        //休憩戻ボタンが正しく機能する
        $this->post('/attendance/break/end', ['attendance_id' => $attendanceId]);
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
        $response->assertSee('退勤');
        $response->assertSee('休憩入');

        //退勤ボタンが正しく機能する（処理後に画面上に表示されるステータスが「退勤済」になる）
        $this->post('/attendance/end', ['attendance_id' => $attendanceId]);
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_no_start_button_after_end()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤・退勤を済ませる
        $this->post('/attendance/start');
        $attendanceId = \App\Models\Attendance::where('user_id', $user->id)->first()->id;
        $this->post('/attendance/end', ['attendance_id' => $attendanceId]);

        // 画面を表示
        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');
        $response->assertDontSee('>出勤</button>', false);
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_start_time_is_displayed_on_attendance_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $this->post('/attendance/start');

        // DBに保存された出勤時刻を取得
        $attendance = \App\Models\Attendance::where('user_id', $user->id)->first();
        $startTime = substr($attendance->start_time, 0, 5);
        // ビューはmb_convert_kana(...,'N')で全角数字に変換している
        $expectedTime = mb_convert_kana($startTime, 'N');

        // 勤怠一覧画面を表示
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($expectedTime);
    }

    /**
     * 休憩戻は一日に何回でもできる
     */
    public function test_break_return_button_displayed_after_second_break_start()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $this->post('/attendance/start');
        $attendanceId = \App\Models\Attendance::where('user_id', $user->id)->first()->id;

        // 1回目の休憩入→休憩戻
        $this->post('/attendance/break/start', ['attendance_id' => $attendanceId]);
        $this->post('/attendance/break/end', ['attendance_id' => $attendanceId]);

        // 2回目の休憩入
        $this->post('/attendance/break/start', ['attendance_id' => $attendanceId]);

        // 休憩中・休憩戻ボタンが表示されること
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_time_is_displayed_on_attendance_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::today();

        // 出勤（09:00）
        Carbon::setTestNow($today->copy()->setTime(9, 0, 0));
        $this->post('/attendance/start');
        $attendanceId = \App\Models\Attendance::where('user_id', $user->id)->first()->id;

        // 休憩入（12:00）
        Carbon::setTestNow($today->copy()->setTime(12, 0, 0));
        $this->post('/attendance/break/start', ['attendance_id' => $attendanceId]);

        // 休憩戻（13:00）→休憩時間1時間
        Carbon::setTestNow($today->copy()->setTime(13, 0, 0));
        $this->post('/attendance/break/end', ['attendance_id' => $attendanceId]);

        Carbon::setTestNow();

        // 勤怠一覧画面で休憩時間が表示されていること（1:00 → 全角）
        $expectedBreakTime = mb_convert_kana('1:00', 'N');
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($expectedBreakTime);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_end_time_and_work_time_displayed_on_attendance_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::today();

        // 出勤（09:00）
        Carbon::setTestNow($today->copy()->setTime(9, 0, 0));
        $this->post('/attendance/start');
        $attendanceId = \App\Models\Attendance::where('user_id', $user->id)->first()->id;

        // 退勤（18:00）
        Carbon::setTestNow($today->copy()->setTime(18, 0, 0));
        $this->post('/attendance/end', ['attendance_id' => $attendanceId]);

        Carbon::setTestNow();

        // 勤怠一覧画面を表示
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 退勤時刻（18:00 → 全角）
        $expectedEndTime = mb_convert_kana('18:00', 'N');
        $response->assertSee($expectedEndTime);

        // 合計勤務時間（9時間 = 9:00 → 全角）
        $expectedWorkTime = mb_convert_kana('9:00', 'N');
        $response->assertSee($expectedWorkTime);
    }
}
