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
 * 勤怠情報修正機能（管理者）
 */
class AdminApplyListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Attendance $attendance1;
    private Attendance $attendance2;
    private Apply $pendingApply;
    private Apply $approvedApply;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['name' => 'テスト太郎']);

        // 勤怠データ1（今日）
        $this->attendance1 = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $this->attendance1->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        // 勤怠データ2（昨日）
        $this->attendance2 = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $this->attendance2->id,
            'break_start_time' => '13:00:00',
            'break_end_time' => '14:00:00',
        ]);

        // 承認待ち申請
        $this->pendingApply = Apply::create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance1->id,
            'apply_work_date' => $this->attendance1->work_date,
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_note' => '承認待ちテスト',
            'status' => 0,
        ]);
        ApplyBreakTime::create([
            'apply_id' => $this->pendingApply->id,
            'break_time_id' => BreakTime::where('attendance_id', $this->attendance1->id)->first()->id,
            'apply_break_start_time' => '11:30',
            'apply_break_end_time' => '12:30',
        ]);

        // 承認済み申請
        $this->approvedApply = Apply::create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance2->id,
            'apply_work_date' => $this->attendance2->work_date,
            'apply_start_time' => '11:00',
            'apply_end_time' => '20:00',
            'apply_note' => '承認済みテスト',
            'status' => 1,
        ]);
        ApplyBreakTime::create([
            'apply_id' => $this->approvedApply->id,
            'break_time_id' => BreakTime::where('attendance_id', $this->attendance2->id)->first()->id,
            'apply_break_start_time' => '14:00',
            'apply_break_end_time' => '15:00',
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_pending_applies_displayed_in_wait_tab()
    {
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);

        $pendingDate = Carbon::parse($this->attendance1->work_date)->format('Y/m/d');
        $this->assertStringContainsString('テスト太郎', $waitMatches[1]);
        $this->assertStringContainsString($pendingDate, $waitMatches[1]);
        $this->assertStringContainsString('承認待ちテスト', $waitMatches[1]);
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function test_approved_applies_displayed_in_approve_tab()
    {
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $content = $response->getContent();
        preg_match('/<div class="list approve">(.*?)<\/div>/s', $content, $approveMatches);

        $approvedDate = Carbon::parse($this->attendance2->work_date)->format('Y/m/d');
        $this->assertStringContainsString('テスト太郎', $approveMatches[1]);
        $this->assertStringContainsString($approvedDate, $approveMatches[1]);
        $this->assertStringContainsString('承認済みテスト', $approveMatches[1]);
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_pending_apply_detail_shows_content_and_approve_button()
    {
        $response = $this->get('/stamp_correction_request/approve/' . $this->pendingApply->id);
        $response->assertStatus(200);

        // ユーザー名
        $response->assertSee('テスト太郎');

        // 日付
        $workDate = Carbon::parse($this->attendance1->work_date);
        $response->assertSee($workDate->isoFormat('YYYY年'));
        $response->assertSee($workDate->isoFormat('M月D日'));

        // 出勤・退勤時刻
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        // 休憩時刻
        $response->assertSee('11:30');
        $response->assertSee('12:30');

        // 備考
        $response->assertSee('承認待ちテスト');

        // 承認ボタンが表示されること
        $response->assertSee('>承認</button>', false);
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_approve_updates_attendance_and_shows_approved()
    {
        // 承認ボタンを押す
        $response = $this->post('/stamp_correction_request/approve/' . $this->pendingApply->id);
        $response->assertRedirect('/stamp_correction_request/approve/' . $this->pendingApply->id);

        // attendancesテーブルが申請内容で更新されていること
        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance1->id,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // break_timesテーブルが申請内容で更新されていること
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance1->id,
            'break_start_time' => '11:30:00',
            'break_end_time' => '12:30:00',
        ]);

        // appliesテーブルのstatusが1になっていること
        $this->assertDatabaseHas('applies', [
            'id' => $this->pendingApply->id,
            'status' => 1,
        ]);

        // 承認後の詳細画面に申請内容が表示されていること
        $response = $this->get('/stamp_correction_request/approve/' . $this->pendingApply->id);
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('11:30');
        $response->assertSee('12:30');

        // 承認済みボタンが表示されること
        $response->assertSee('>承認済み</button>', false);

        // button-area内にsubmitボタンがないこと
        $content = $response->getContent();
        preg_match('/<div class="button-area">(.*?)<\/div>/s', $content, $buttonArea);
        $this->assertStringNotContainsString('type="submit"', $buttonArea[1]);
    }
}
