<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

/**
 * ログイン認証機能（管理者）
 */
class AdminLoginTest extends TestCase
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
            'メール未入力' => [['email' => '', 'password' => 'password', 'is_admin' => 1], 'email', 'メールアドレスを入力してください'],
            'パスワード未入力' => [['email' => 'test@example.com', 'password' => '', 'is_admin' => 1], 'password', 'パスワードを入力してください'],
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
            'is_admin' => 1,
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
        //管理者ユーザー作成
        $user = User::factory()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => 1,
        ]);

        //ログイン
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => 1,
        ]);

        //認証確認
        $this->assertAuthenticatedAs($user);
    }
}
