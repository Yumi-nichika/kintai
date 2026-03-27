<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

/**
 * 認証機能（一般ユーザー）
 * メール認証機能
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションエラーの全パターンをテスト
     * @dataProvider invalidRegisterProvider
     */
    public function test_register_validation_errors($data, $errorField, $expectedMessage)
    {
        $response = $this->post('/register', $data);

        //エラーメッセージ確認
        $response->assertSessionHasErrors([$errorField => $expectedMessage]);

        //データベース未登録確認
        $this->assertCount(0, User::all());
    }

    /**
     * バリデーションエラーテスト
     */
    public function invalidRegisterProvider()
    {
        $valid = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        return [
            '名前が未入力' => [array_merge($valid, ['name' => '']), 'name', 'お名前を入力してください'],
            'メールが未入力' => [array_merge($valid, ['email' => '']), 'email', 'メールアドレスを入力してください'],
            'パスワードが未入力' => [array_merge($valid, ['password' => '', 'password_confirmation' => '']), 'password', 'パスワードを入力してください'],
            'パスワードが7文字以下' => [array_merge($valid, ['password' => '1234567', 'password_confirmation' => '1234567']), 'password', 'パスワードは8文字以上で入力してください'],
            'パスワード不一致' => [array_merge($valid, ['password_confirmation' => 'different']), 'password', 'パスワードと一致しません'],
        ];
    }

    /**
     * 会員登録成功テスト（データベース登録・メール認証誘導画面遷移・認証メール送信）
     */
    public function test_register_success()
    {
        //ユーザー作成
        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->followingRedirects()->post('/register', $data);

        //データベース登録確認
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        //未認証のため最終的にメール認証誘導画面に遷移することを確認
        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');
    }

    /**
     * 会員登録時に認証メールが送信されることのテスト
     */
    public function test_register_sends_verification_email()
    {
        Notification::fake();

        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $this->post('/register', $data);

        //登録したアドレスに認証メールが送信されたことを確認
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * メール認証誘導画面に「認証はこちらから」リンク（mailhog）が表示されるテスト
     */
    public function test_verify_email_page_has_mailhog_link()
    {
        //未認証ユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        //メール認証誘導画面に遷移し、認証リンクの表示を確認
        $verifyResponse = $this->actingAs($user)->get('/email/verify');
        $verifyResponse->assertStatus(200);
        $verifyResponse->assertSee('認証はこちらから');
        $verifyResponse->assertSee('<a class="button button_gray" href="http://localhost:8025" target="_blank">認証はこちらから</a>', false);

        //「認証はこちらから」リンクのhref属性がmailhog（http://localhost:8025）であることを確認
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($verifyResponse->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);
        $link = $xpath->query('//a[contains(text(), "認証はこちらから")]')->item(0);
        $this->assertNotNull($link, '「認証はこちらから」リンクが存在すること');
        $this->assertEquals('http://localhost:8025', $link->getAttribute('href'));
        $this->assertEquals('_blank', $link->getAttribute('target'));
    }

    /**
     * 認証メール内のリンクをクリックすると認証され、「勤怠登録画面」に遷移するテスト
     */
    public function test_email_verification_redirects_to_attendance()
    {
        //未認証ユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        //認証メール内の署名付きURLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        //認証リンクをクリック（署名付きURLにアクセス）
        $response = $this->actingAs($user)->get($verificationUrl);

        //認証後、/attendanceにリダイレクトされることを確認
        $response->assertRedirect('/attendance');

        //ユーザーがメール認証済みになっていることを確認
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
