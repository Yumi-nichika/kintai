<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

/**
 * 勤怠一覧情報取得機能（一般ユーザー）
 */
class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_attendance_data_is_displayed_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::today();

        // 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩データ作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // ヘッダーが正しいこと
        $response->assertSee('日付');
        $response->assertSee('出勤');
        $response->assertSee('退勤');
        $response->assertSee('休憩');
        $response->assertSee('合計');
        $response->assertSee('詳細');

        // 出勤・退勤・休憩・合計が全角で表示されること
        $response->assertSee(mb_convert_kana('09:00', 'N'));
        $response->assertSee(mb_convert_kana('18:00', 'N'));
        $response->assertSee(mb_convert_kana('1:00', 'N'));
        $response->assertSee(mb_convert_kana('8:00', 'N'));
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_default_month_is_current_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 今月のYYYY/mmが表示されていること
        $currentMonth = Carbon::now()->format('Y/m');
        $response->assertSee($currentMonth);
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_shows_previous_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lastMonth = Carbon::now()->subMonthNoOverflow();

        // 前月の勤怠データ作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $lastMonth->copy()->startOfMonth()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // 前月を指定してアクセス
        $response = $this->get('/attendance/list?month=' . $lastMonth->format('Y-m'));
        $response->assertStatus(200);

        // 前月のYYYY/mmが表示されていること
        $response->assertSee($lastMonth->format('Y/m'));

        // 前月の勤怠データが表示されていること
        $response->assertSee(mb_convert_kana('10:00', 'N'));
        $response->assertSee(mb_convert_kana('19:00', 'N'));
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_next_month_button_shows_next_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $nextMonth = Carbon::now()->addMonthNoOverflow();

        // 翌月を指定してアクセス
        $response = $this->get('/attendance/list?month=' . $nextMonth->format('Y-m'));
        $response->assertStatus(200);

        // 翌月のYYYY/mmが表示されていること
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 機能要件より、勤怠情報がないフィールドは空白になっていること
     * （まだ出勤しか打刻していない場合、他が空白であること）
     */
    public function test_end_time_is_blank_when_only_clocked_in()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::today();

        // 出勤のみで退勤なしの勤怠データ作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->get('/attendance/list');
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
        $user = User::factory()->create();
        $this->actingAs($user);

        // 当月1日のみ勤怠データを作成し、2日は勤怠なしとする
        $firstDay = Carbon::now()->startOfMonth();
        $secondDay = $firstDay->copy()->addDay();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $firstDay->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->get('/attendance/list');
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
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_shows_selected_date_attendance()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::today();

        // 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:30:00',
            'end_time' => '17:30:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        // 詳細画面にアクセス
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        // 日付が表示されていること
        $response->assertSee($today->isoFormat('YYYY年'));
        $response->assertSee($today->isoFormat('M月D日'));

        // 出勤・退勤時刻が表示されていること
        $response->assertSee('09:30');
        $response->assertSee('17:30');

        // 休憩時刻が表示されていること
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
