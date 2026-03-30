<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Apply;
use Carbon\Carbon;

/**
 * 申請承認フロー（一般ユーザー→管理者→一般ユーザー）
 */
class ApplyFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一般ユーザーの申請→管理者の承認→両者の一覧反映の一連フロー
     */
    public function test_full_apply_approval_flow()
    {
        $user = User::factory()->create(['name' => 'テスト太郎']);
        $admin = User::factory()->create(['is_admin' => true]);
        $today = Carbon::today();

        // === 一般ユーザーでログイン・打刻 ===
        $this->actingAs($user);

        Carbon::setTestNow($today->copy()->setTime(9, 0, 0));
        $this->post('/attendance/start');
        $attendanceId = Attendance::where('user_id', $user->id)->first()->id;

        Carbon::setTestNow($today->copy()->setTime(12, 0, 0));
        $this->post('/attendance/break/start', ['attendance_id' => $attendanceId]);

        Carbon::setTestNow($today->copy()->setTime(13, 0, 0));
        $this->post('/attendance/break/end', ['attendance_id' => $attendanceId]);

        Carbon::setTestNow($today->copy()->setTime(18, 0, 0));
        $this->post('/attendance/end', ['attendance_id' => $attendanceId]);

        Carbon::setTestNow();

        // === 一般ユーザーで修正申請 ===
        $this->post('/attendance/detail/' . $attendanceId, [
            'apply_work_date' => $today->toDateString(),
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '時間修正テスト',
        ]);

        $applyId = Apply::where('user_id', $user->id)->first()->id;
        $expectedDate = $today->format('Y/m/d');

        // === 一般ユーザーの申請一覧：承認待ちにある ===
        $response = $this->get('/stamp_correction_request/list');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringContainsString($expectedDate, $waitMatches[1]);

        // === 管理者でログイン ===
        $this->actingAs($admin);

        // === 管理者の申請一覧：承認待ちにある ===
        $response = $this->get('/stamp_correction_request/list');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringContainsString('テスト太郎', $waitMatches[1]);
        $this->assertStringContainsString($expectedDate, $waitMatches[1]);

        // === 管理者が承認 ===
        $this->post('/stamp_correction_request/approve/' . $applyId);

        // === 管理者の申請一覧：承認待ちに無い ===
        $response = $this->get('/stamp_correction_request/list');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringNotContainsString($expectedDate, $waitMatches[1]);

        // === 管理者の申請一覧：承認済みにある ===
        preg_match('/<div class="list approve">(.*?)<\/div>/s', $content, $approveMatches);
        $this->assertStringContainsString('テスト太郎', $approveMatches[1]);
        $this->assertStringContainsString($expectedDate, $approveMatches[1]);

        // === 一般ユーザーで再ログイン ===
        $this->actingAs($user);

        // === 一般ユーザーの申請一覧：承認待ちに無い ===
        $response = $this->get('/stamp_correction_request/list');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringNotContainsString($expectedDate, $waitMatches[1]);

        // === 一般ユーザーの申請一覧：承認済みにある ===
        preg_match('/<div class="list approve">(.*?)<\/div>/s', $content, $approveMatches);
        $this->assertStringContainsString($expectedDate, $approveMatches[1]);
    }
}
