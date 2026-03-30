<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

/**
 * ユーザー情報取得機能（管理者）
 */
class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => '管理者', 'email' => 'admin@example.com']);
        $this->user1 = User::factory()->create(['name' => 'スタッフA', 'email' => 'staff_a@example.com']);
        $this->user2 = User::factory()->create(['name' => 'スタッフB', 'email' => 'staff_b@example.com']);
        $this->actingAs($this->admin);
    }

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_all_users_name_and_email_displayed()
    {
        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        $response->assertSee('スタッフA');
        $response->assertSee('staff_a@example.com');
        $response->assertSee('スタッフB');
        $response->assertSee('staff_b@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_detail_shows_staff_current_month_attendance()
    {
        $today = Carbon::today();

        // 今月の勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $this->user1->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id);
        $response->assertStatus(200);

        // ユーザー名と今月が表示されていること
        $response->assertSee('スタッフAさんの勤怠');
        $response->assertSee($today->format('Y/m'));

        // 勤怠データが表示されていること
        $response->assertSee(mb_convert_kana('09:00', 'N'));
        $response->assertSee(mb_convert_kana('18:00', 'N'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_shows_previous_month_attendance()
    {
        $lastMonth = Carbon::now()->subMonthNoOverflow();

        // 前月の勤怠データ作成
        Attendance::create([
            'user_id' => $this->user1->id,
            'work_date' => $lastMonth->copy()->startOfMonth()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id . '?month=' . $lastMonth->format('Y-m'));
        $response->assertStatus(200);

        // 前月が表示されていること
        $response->assertSee($lastMonth->format('Y/m'));

        // 前月の勤怠データが表示されていること
        $response->assertSee(mb_convert_kana('10:00', 'N'));
        $response->assertSee(mb_convert_kana('19:00', 'N'));
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_next_month_shows_next_month()
    {
        $nextMonth = Carbon::now()->addMonthNoOverflow();

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id . '?month=' . $nextMonth->format('Y-m'));
        $response->assertStatus(200);

        // 翌月が表示されていること
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_shows_attendance_detail()
    {
        $today = Carbon::today();

        // 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $this->user1->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:30:00',
            'end_time' => '17:30:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get('/admin/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // ユーザー名が表示されていること
        $response->assertSee('スタッフA');

        // 日付が表示されていること
        $response->assertSee($today->isoFormat('YYYY年'));
        $response->assertSee($today->isoFormat('M月D日'));

        // 出勤・退勤時刻が表示されていること
        $response->assertSee('value="09:30"', false);
        $response->assertSee('value="17:30"', false);

        // 休憩時刻が表示されていること
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    /**
     * 機能要件より、「CSV出力」を押下すると，選択した月で行った勤怠一覧情報がCSVでダウンロードできること
     */
    public function test_csv_export_contains_attendance_data()
    {
        $today = Carbon::today();
        $month = $today->format('Y-m');

        // 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $this->user1->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        // CSV出力
        $response = $this->get('/admin/attendance/staff/export/' . $this->user1->id . '?month=' . $month);
        $response->assertStatus(200);

        // Content-Typeがcsvであること
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // ヘッダー行が含まれること
        $this->assertStringContainsString('日付', $content);
        $this->assertStringContainsString('出勤', $content);
        $this->assertStringContainsString('退勤', $content);
        $this->assertStringContainsString('休憩', $content);
        $this->assertStringContainsString('合計', $content);

        // 勤怠データが含まれること
        $this->assertStringContainsString('09:00', $content);
        $this->assertStringContainsString('18:00', $content);
        $this->assertStringContainsString('1:00', $content);
        $this->assertStringContainsString('8:00', $content);
    }
}
