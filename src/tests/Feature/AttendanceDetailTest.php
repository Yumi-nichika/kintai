<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Apply;
use Carbon\Carbon;

/**
 * 勤怠詳細情報取得機能（一般ユーザー）
 * 勤怠詳細情報修正機能（一般ユーザー）
 */
class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'テスト太郎']);
        $this->actingAs($this->user);

        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $this->attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $this->attendance->id,
            'break_start_time' => '15:00:00',
            'break_end_time' => '15:30:00',
        ]);
    }

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     * 勤怠詳細画面の「日付」が選択した日付になっている
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     * 機能要件より、休憩回数分のレコードと追加で１つ分の入力フィールドが表示されること
     */
    public function test_name_date_and_edit_button_displayed()
    {
        $response = $this->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);

        //名前
        $response->assertSee('テスト太郎');

        //日付
        $workDate = Carbon::parse($this->attendance->work_date);
        $response->assertSee($workDate->isoFormat('YYYY年'));
        $response->assertSee($workDate->isoFormat('M月D日'));

        //出勤・退勤
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);

        // 1回目の休憩
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);

        // 2回目の休憩
        $response->assertSee('value="15:00"', false);
        $response->assertSee('value="15:30"', false);

        // 休憩2のthが表示されていること
        $response->assertSee('休憩2', false);

        // 既存休憩2件 + 空行1件 = 休憩3のthが表示されること
        $response->assertSee('休憩3', false);

        // 空の休憩入力欄が存在すること
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/name="apply_break_start_times\[\]"\s+value=""/',
            $content
        );

        $response->assertSee('>修正</button>', false);
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_start_time_after_end_time_shows_error()
    {
        $response = $this->post('/attendance/detail/' . $this->attendance->id, [
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
        $response = $this->post('/attendance/detail/' . $this->attendance->id, [
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
        $response = $this->post('/attendance/detail/' . $this->attendance->id, [
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
        $response = $this->post('/attendance/detail/' . $this->attendance->id, [
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
        $response = $this->post('/attendance/detail/' . $this->attendance->id, [
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
     * 修正申請処理が実行される（申請一覧画面に表示される）
     */
    public function test_apply_date_is_displayed_on_apply_list()
    {
        // 修正申請を送信
        $this->post('/attendance/detail/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '修正テスト',
        ]);

        // 申請一覧画面を表示
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 承認待ちタブ内に申請した日付が表示されていること
        $expectedDate = Carbon::parse($this->attendance->work_date)->format('Y/m/d');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringContainsString($expectedDate, $waitMatches[1]);
    }

    /**
     * 修正申請処理が実行される（管理者の承認画面に表示される）
     */
    public function test_apply_is_displayed_on_admin_apply_list()
    {
        // 一般ユーザーで修正申請を送信
        $this->post('/attendance/detail/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '修正テスト',
        ]);

        // 管理者ユーザーでログイン
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // 管理者の申請一覧画面を表示
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 承認待ちタブ内にユーザー名と日付が表示されていること
        $expectedDate = Carbon::parse($this->attendance->work_date)->format('Y/m/d');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringContainsString('テスト太郎', $waitMatches[1]);
        $this->assertStringContainsString($expectedDate, $waitMatches[1]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_multiple_applies_displayed_on_apply_list()
    {
        // 2つ目の勤怠データ作成（昨日）
        $yesterday = Carbon::yesterday();
        $attendance2 = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => $yesterday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        // 1つ目の申請（今日）
        $this->post('/attendance/detail/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '修正テスト1',
        ]);

        // 2つ目の申請（昨日）
        $this->post('/attendance/detail/' . $attendance2->id, [
            'apply_work_date' => $attendance2->work_date,
            'apply_start_time' => '08:00',
            'apply_end_time' => '17:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '修正テスト2',
        ]);

        // 申請一覧画面を表示
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // ヘッダーが正しいこと
        $response->assertSee('状態');
        $response->assertSee('名前');
        $response->assertSee('対象日時');
        $response->assertSee('申請理由');
        $response->assertSee('申請日時');
        $response->assertSee('詳細');

        // 承認待ちタブ内に両方の日付が表示されていること
        $expectedDate1 = Carbon::parse($this->attendance->work_date)->format('Y/m/d');
        $expectedDate2 = $yesterday->format('Y/m/d');
        $content = $response->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $content, $waitMatches);
        $this->assertStringContainsString($expectedDate1, $waitMatches[1]);
        $this->assertStringContainsString($expectedDate2, $waitMatches[1]);
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_apply_displayed_in_approved_tab()
    {
        // 修正申請を送信
        $this->post('/attendance/detail/' . $this->attendance->id, [
            'apply_work_date' => $this->attendance->work_date,
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['12:00'],
            'apply_break_end_times' => ['13:00'],
            'apply_note' => '承認テスト',
        ]);

        // ステータスを承認済み(1)に更新
        $apply = Apply::where('user_id', $this->user->id)->first();
        $apply->update(['status' => 1]);

        $expectedDate = Carbon::parse($this->attendance->work_date)->format('Y/m/d');

        // 承認待ちタブ：表示されないこと
        $waitResponse = $this->get('/stamp_correction_request/list');
        $waitResponse->assertStatus(200);
        $waitContent = $waitResponse->getContent();
        preg_match('/<div class="list wait">(.*?)<\/div>/s', $waitContent, $waitMatches);
        $this->assertStringNotContainsString($expectedDate, $waitMatches[1]);

        // 承認済みタブを開いて確認
        $approveResponse = $this->get('/stamp_correction_request/list?tab=approve');
        $approveResponse->assertStatus(200);

        // ヘッダーが正しいこと
        $approveResponse->assertSee('状態');
        $approveResponse->assertSee('名前');
        $approveResponse->assertSee('対象日時');
        $approveResponse->assertSee('申請理由');
        $approveResponse->assertSee('申請日時');
        $approveResponse->assertSee('詳細');

        // 承認済みタブ内に表示されること
        $approveContent = $approveResponse->getContent();
        preg_match('/<div class="list approve">(.*?)<\/div>/s', $approveContent, $approveMatches);
        $this->assertStringContainsString($expectedDate, $approveMatches[1]);
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     * 機能要件より、「承認待ち」の申請詳細は，修正を行うことができず「承認待ちのため修正はできません。」とメッセージが表示されていること
     */
    public function test_pending_apply_shows_applied_content_and_no_edit()
    {
        // 出勤・退勤を打刻
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));
        $this->post('/attendance/start');
        $attendanceId = Attendance::where('user_id', $this->user->id)
            ->whereNull('end_time')->first()->id;

        Carbon::setTestNow(Carbon::today()->setTime(18, 0, 0));
        $this->post('/attendance/end', ['attendance_id' => $attendanceId]);

        // 休憩も打刻済みにする
        BreakTime::create([
            'attendance_id' => $attendanceId,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        Carbon::setTestNow();

        // 修正申請を送信
        $this->post('/attendance/detail/' . $attendanceId, [
            'apply_work_date' => Carbon::today()->toDateString(),
            'apply_start_time' => '10:00',
            'apply_end_time' => '19:00',
            'apply_break_start_times' => ['11:30'],
            'apply_break_end_times' => ['12:30'],
            'apply_note' => '時間修正',
        ]);

        // 申請一覧から詳細画面にアクセス
        $response = $this->get('/attendance/detail/' . $attendanceId);
        $response->assertStatus(200);

        // 申請した内容で表示されていること
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('11:30');
        $response->assertSee('12:30');
        $response->assertSee('時間修正');

        // 「*承認待ちのため修正はできません。」が表示されていること
        $response->assertSee('*承認待ちのため修正はできません。');

        // 修正ボタンが表示されないこと
        $response->assertDontSee('>修正</button>', false);
    }
}
