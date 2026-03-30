<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Apply;
use App\Models\ApplyBreakTime;
use Carbon\Carbon;

/**
 * 勤怠詳細情報取得・修正機能（管理者）
 */
class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);

        $user = User::factory()->create(['name' => 'テスト太郎']);

        $this->attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $this->attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);
    }

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_selected_date_attendance_displayed_on_detail()
    {
        $workDate = Carbon::parse($this->attendance->work_date);

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get('/admin/attendance/' . $this->attendance->id);
        $response->assertStatus(200);

        // ユーザー名が表示されていること
        $response->assertSee('テスト太郎');

        // 日付が表示されていること
        $response->assertSee($workDate->isoFormat('YYYY年'));
        $response->assertSee($workDate->isoFormat('M月D日'));

        // 出勤・退勤時刻が表示されていること
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);

        // 休憩時刻が表示されていること
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_start_time_after_end_time_shows_error()
    {
        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '19:00',
            'apply_end_time' => '18:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['apply_start_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 機能要件より、退勤時間が出勤時間より前になっている場合、エラーメッセージが表示される
     */
    public function test_end_time_before_start_time_shows_error()
    {
        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '18:00',
            'apply_end_time' => '09:00',
            'apply_break_start_times' => ['08:00'],
            'apply_break_end_times' => ['08:30'],
            'apply_note' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['apply_start_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_after_end_time_shows_error()
    {
        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '09:00',
            'apply_end_time' => '18:00',
            'apply_break_start_times' => ['19:00'],
            'apply_break_end_times' => ['20:00'],
            'apply_note' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['apply_break_start_times.0' => '休憩時間が不適切な値です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_after_end_time_shows_error()
    {
        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '09:00',
            'apply_end_time' => '18:00',
            'apply_break_start_times' => ['17:00'],
            'apply_break_end_times' => ['19:00'],
            'apply_note' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['apply_break_end_times.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_empty_note_shows_error()
    {
        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '09:00',
            'apply_end_time' => '18:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '',
        ]);

        $response->assertSessionHasErrors(['apply_note' => '備考を記入してください']);
    }

    /**
     * 機能要件より、「修正」ボタンを押下すると，管理者として直接修正が実行されること
     */
    public function test_admin_update_directly_modifies_attendance_without_apply()
    {
        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['11:30'],
            'apply_break_end_times' => ['12:30'],
            'apply_note' => '管理者修正',
        ]);

        $response->assertRedirect('/admin/attendance/' . $this->attendance->id);

        // attendancesテーブルが直接更新されていること
        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance->id,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'note' => '管理者修正',
        ]);

        // break_timesテーブルが更新されていること
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'break_start_time' => '11:30:00',
            'break_end_time' => '12:30:00',
        ]);

        // appliesテーブルにレコードが作られていないこと
        $this->assertDatabaseMissing('applies', [
            'attendance_id' => $this->attendance->id,
        ]);

        // apply_break_timesテーブルにレコードが作られていないこと
        $this->assertEquals(0, ApplyBreakTime::count());
    }
}
