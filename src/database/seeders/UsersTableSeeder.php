<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '管理者',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_admin' => 1,
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'テスト太郎',
            'email' => 'tarou@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];
        DB::table('users')->insert($param);
    }
}
