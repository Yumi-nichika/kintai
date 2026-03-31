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

        // ヘッダーが正しいこと
        $response->assertSee('状態');
        $response->assertSee('名前');
        $response->assertSee('対象日時');
        $response->assertSee('申請理由');
        $response->assertSee('申請日時');
        $response->assertSee('詳細');

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
        // 承認済みタブを開いて確認
        $response = $this->get('/stamp_correction_request/list?tab=approve');
        $response->assertStatus(200);

        // ヘッダーが正しいこと
        $response->assertSee('状態');
        $response->assertSee('名前');
        $response->assertSee('対象日時');
        $response->assertSee('申請理由');
        $response->assertSee('申請日時');
        $response->assertSee('詳細');

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
     * 機能要件より、
     * 管理者ユーザーの「修正申請一覧」で，”承認待ち”から”承認済み”に変更されていること
     * 一般ユーザーの当該勤怠情報が更新され，修正申請の内容と一致すること
     * 一般ユーザーの「修正申請一覧」で，”承認待ち”から”承認済み”に変更されていること
     */
    public function test_approve_updates_attendance_and_shows_approved()
    {
        $pendingDate = Carbon::parse($this->attendance1->work_date)->format('Y/m/d');

        // 承認前：管理者の修正申請一覧で承認待ちにあること
        $adminWaitBefore = $this->get('/stamp_correction_request/list');
        $adminWaitBefore->assertStatus(200);
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $adminWaitBefore->getContent(), $adminWaitBeforeMatches);
        $this->assertStringContainsString($pendingDate, $adminWaitBeforeMatches[1]);

        // 承認前：一般ユーザーの修正申請一覧で承認待ちにあること
        $this->actingAs($this->user);
        $userWaitBefore = $this->get('/stamp_correction_request/list');
        $userWaitBefore->assertStatus(200);
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $userWaitBefore->getContent(), $userWaitBeforeMatches);
        $this->assertStringContainsString($pendingDate, $userWaitBeforeMatches[1]);

        // 承認前：一般ユーザーの勤怠一覧が修正前の内容であること
        $listBefore = $this->get('/attendance/list');
        $listBefore->assertStatus(200);
        $listBefore->assertSee(mb_convert_kana('09:00', 'N'));
        $listBefore->assertSee(mb_convert_kana('18:00', 'N'));

        // 管理者に戻す
        $this->actingAs($this->admin);

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

        // 承認済みが表示されること
        $response->assertSee('>承認済み</button>', false);

        // button-area内にsubmitボタンがないこと
        $content = $response->getContent();
        preg_match('/<div class="button-area">(.*?)<\/div>/s', $content, $buttonArea);
        $this->assertStringNotContainsString('type="submit"', $buttonArea[1]);

        // 管理者の修正申請一覧で承認待ちから承認済みに変更されていること
        $adminWaitResponse = $this->get('/stamp_correction_request/list');
        $adminWaitResponse->assertStatus(200);
        $adminWaitContent = $adminWaitResponse->getContent();
        // 承認待ちタブに表示されないこと
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $adminWaitContent, $adminWaitMatches);
        $this->assertStringNotContainsString($pendingDate, $adminWaitMatches[1]);

        // 承認済みタブに表示されること
        $adminApproveResponse = $this->get('/stamp_correction_request/list?tab=approve');
        $adminApproveContent = $adminApproveResponse->getContent();
        preg_match('/<div class="list approve">(.*?)<\/div>/s', $adminApproveContent, $adminApproveMatches);
        $this->assertStringContainsString($pendingDate, $adminApproveMatches[1]);

        // 一般ユーザーに切り替え
        $this->actingAs($this->user);

        // 一般ユーザーの勤怠一覧で申請内容が反映されていること
        $listResponse = $this->get('/attendance/list');
        $listResponse->assertStatus(200);
        $listResponse->assertSee(mb_convert_kana('10:00', 'N'));
        $listResponse->assertSee(mb_convert_kana('19:00', 'N'));

        // 一般ユーザーの勤怠詳細で申請内容が反映されていること
        $detailResponse = $this->get('/attendance/detail/' . $this->attendance1->id);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('value="10:00"', false);
        $detailResponse->assertSee('value="19:00"', false);
        $detailResponse->assertSee('value="11:30"', false);
        $detailResponse->assertSee('value="12:30"', false);

        // 一般ユーザーの修正申請一覧で承認待ちから承認済みに変更されていること
        $userWaitResponse = $this->get('/stamp_correction_request/list');
        $userWaitResponse->assertStatus(200);
        $userWaitContent = $userWaitResponse->getContent();
        // 承認待ちタブに表示されないこと
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $userWaitContent, $userWaitMatches);
        $this->assertStringNotContainsString($pendingDate, $userWaitMatches[1]);

        // 承認済みタブに表示されること
        $userApproveResponse = $this->get('/stamp_correction_request/list?tab=approve');
        $userApproveContent = $userApproveResponse->getContent();
        preg_match('/<div class="list approve">(.*?)<\/div>/s', $userApproveContent, $userApproveMatches);
        $this->assertStringContainsString($pendingDate, $userApproveMatches[1]);
    }
}
