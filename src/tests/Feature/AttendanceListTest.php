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
