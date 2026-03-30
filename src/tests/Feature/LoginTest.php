<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

/**
 * ログイン認証機能（一般ユーザー）
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションエラーの全パターンをテスト
     * @dataProvider invalidLoginProvider
     */
    public function test_login_validation_errors($data, $errorField, $expectedMessage)
    {
        $response = $this->post('/login', $data);

        //エラーメッセージ確認
        $response->assertSessionHasErrors([$errorField => $expectedMessage]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function invalidLoginProvider()
    {
        return [
            'メール未入力' => [['email' => '', 'password' => 'password'], 'email', 'メールアドレスを入力してください'],
            'パスワード未入力' => [['email' => 'test@example.com', 'password' => ''], 'password', 'パスワードを入力してください'],
        ];
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails()
    {
        //存在しないユーザーでログイン
        $response = $this->post('/login', [
            'email' => 'notfound@example.com',
            'password' => 'wrong-password',
        ]);

        //エラーが返り、認証されていないこと
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    /**
     * ログイン成功
     */
    public function test_login_success()
    {
        //ユーザー作成
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        //ログイン
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        //認証確認
        $this->assertAuthenticatedAs($user);
    }
}
