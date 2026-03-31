<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

/**
 * 勤怠一覧情報取得機能（管理者）
 */
class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_all_users_attendance_displayed_correctly()
    {
        $today = Carbon::today();

        // ユーザー2名作成
        $user1 = User::factory()->create(['name' => 'ユーザーA']);
        $user2 = User::factory()->create(['name' => 'ユーザーB']);

        // ユーザーAの勤怠
        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        // ユーザーBの勤怠
        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $today->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start_time' => '13:00:00',
            'break_end_time' => '14:00:00',
        ]);

        $response = $this->get('/admin/attendance/list?date=' . $today->format('Y-m-d'));
        $response->assertStatus(200);

        // ヘッダーが正しいこと
        $response->assertSee('名前');
        $response->assertSee('出勤');
        $response->assertSee('退勤');
        $response->assertSee('休憩');
        $response->assertSee('合計');
        $response->assertSee('詳細');

        // 両ユーザーの名前が表示されていること
        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');

        // ユーザーAの勤怠データ（全角）
        $response->assertSee(mb_convert_kana('09:00', 'N'));
        $response->assertSee(mb_convert_kana('18:00', 'N'));

        // ユーザーBの勤怠データ（全角）
        $response->assertSee(mb_convert_kana('10:00', 'N'));
        $response->assertSee(mb_convert_kana('19:00', 'N'));
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_default_date_is_today()
    {
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        $today = Carbon::today();
        $response->assertSee($today->isoFormat('YYYY年M月D日'));
        $response->assertSee($today->format('Y/m/d'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_previous_day_button_shows_previous_day()
    {
        $yesterday = Carbon::yesterday();

        // 前日の勤怠データ作成
        $user = User::factory()->create(['name' => '前日ユーザー']);
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $yesterday->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // 前日を指定してアクセス
        $response = $this->get('/admin/attendance/list?date=' . $yesterday->format('Y-m-d'));
        $response->assertStatus(200);

        // 前日の日付が表示されていること
        $response->assertSee($yesterday->isoFormat('YYYY年M月D日'));
        $response->assertSee($yesterday->format('Y/m/d'));

        // 前日の勤怠データが表示されていること
        $response->assertSee('前日ユーザー');
        $response->assertSee(mb_convert_kana('08:00', 'N'));
        $response->assertSee(mb_convert_kana('17:00', 'N'));
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_next_day_button_shows_next_day()
    {
        $tomorrow = Carbon::tomorrow();

        // 翌日を指定してアクセス
        $response = $this->get('/admin/attendance/list?date=' . $tomorrow->format('Y-m-d'));
        $response->assertStatus(200);

        // 翌日の日付が表示されていること
        $response->assertSee($tomorrow->isoFormat('YYYY年M月D日'));
        $response->assertSee($tomorrow->format('Y/m/d'));
    }

    /**
     * 機能要件より、勤怠情報がないフィールドは空白になっていること
     * （まだ出勤しか打刻していない場合、他が空白であること）
     */
    public function test_end_time_is_blank_when_only_clocked_in()
    {
        $today = Carbon::today();

        $user = User::factory()->create(['name' => '出勤のみユーザー']);

        // 出勤のみで退勤なしの勤怠データ作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->get('/admin/attendance/list?date=' . $today->format('Y-m-d'));
        $response->assertStatus(200);

        // 出勤時刻が表示されていること
        $response->assertSee(mb_convert_kana('09:00', 'N'));

        // 該当ユーザーの行を取得し、退勤・休憩・合計が空白であること
        $content = $response->getContent();
        preg_match('/<tr>\s*<td>\s*出勤のみユーザー\s*<\/td>(.*?)<\/tr>/s', $content, $matches);
        $this->assertNotEmpty($matches);

        preg_match_all('/<td>(.*?)<\/td>/s', $matches[1], $tdMatches);
        $cells = $tdMatches[1];

        // 退勤(index 1)・休憩(index 2)・合計(index 3) が空白であること
        for ($i = 1; $i <= 3; $i++) {
            $this->assertMatchesRegularExpression('/^\s*$/', $cells[$i], ($i + 1) . '番目のセルが空白ではありません');
        }
    }
}
