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

        // ヘッダーが正しいこと
        $response->assertSee('名前');
        $response->assertSee('メールアドレス');
        $response->assertSee('月次勤怠');

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

        // ヘッダーが正しいこと
        $response->assertSee('日付');
        $response->assertSee('出勤');
        $response->assertSee('退勤');
        $response->assertSee('休憩');
        $response->assertSee('合計');
        $response->assertSee('詳細');

        // 勤怠データが表示されていること
        $response->assertSee(mb_convert_kana('09:00', 'N'));
        $response->assertSee(mb_convert_kana('18:00', 'N'));
        $response->assertSee(mb_convert_kana('1:00', 'N'));
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
     * 機能要件より、勤怠情報がないフィールドは空白になっていること
     * （まだ出勤しか打刻していない場合、他が空白であること）
     */
    public function test_end_time_is_blank_when_only_clocked_in()
    {
        $today = Carbon::today();

        // 出勤のみで退勤なしの勤怠データ作成
        Attendance::create([
            'user_id' => $this->user1->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id);
        $response->assertStatus(200);

        // 出勤時刻が表示されていること
        $response->assertSee(mb_convert_kana('09:00', 'N'));

        // 退勤時刻が表示されていないこと
        $response->assertDontSee(mb_convert_kana('18:00', 'N'));

        // 出勤のみの日の行を取得し、休憩・合計が空白であること
        $content = $response->getContent();
        $dayLabel = mb_convert_kana($today->isoFormat('MM/DD（ddd）'), 'N');
        preg_match('/<tr>\s*<td>\s*' . preg_quote($dayLabel, '/') . '\s*<\/td>(.*?)<\/tr>/s', $content, $matches);
        $this->assertNotEmpty($matches);

        preg_match_all('/<td>(.*?)<\/td>/s', $matches[1], $tdMatches);
        $cells = $tdMatches[1];

        // 退勤(index 1)・休憩(index 2)・合計(index 3) が空白であること
        for ($i = 1; $i <= 3; $i++) {
            $this->assertMatchesRegularExpression('/^\s*$/', $cells[$i], ($i + 1) . '番目のセルが空白ではありません');
        }
    }

    /**
     * 機能要件より、勤怠情報がないフィールドは空白になっていること
     * （1日は打刻あり、2日は打刻なし）
     */
    public function test_no_attendance_day_shows_all_blank()
    {
        // 当月1日のみ勤怠データを作成し、2日は勤怠なしとする
        $firstDay = Carbon::now()->startOfMonth();
        $secondDay = $firstDay->copy()->addDay();

        Attendance::create([
            'user_id' => $this->user1->id,
            'work_date' => $firstDay->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id);
        $response->assertStatus(200);

        // 勤怠なしの日（2日）の行を取得し、出勤・退勤・休憩・合計が空白であること
        $content = $response->getContent();
        $secondDayLabel = mb_convert_kana($secondDay->isoFormat('MM/DD（ddd）'), 'N');
        preg_match('/<tr>\s*<td>\s*' . preg_quote($secondDayLabel, '/') . '\s*<\/td>(.*?)<\/tr>/s', $content, $matches);

        $this->assertNotEmpty($matches, '勤怠なしの日の行が見つかりません');

        $rowContent = $matches[1];

        // 各tdの中身を取得
        preg_match_all('/<td>(.*?)<\/td>/s', $rowContent, $tdMatches);
        $cells = $tdMatches[1];

        // 出勤・退勤・休憩・合計の4セルが空白であること
        for ($i = 0; $i < 4; $i++) {
            $this->assertMatchesRegularExpression('/^\s*$/', $cells[$i], ($i + 1) . '番目のセルが空白ではありません');
        }
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
