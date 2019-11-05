<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Superadmin',
            'last_name' => 'Superadmin',
            'dni' => '00000000',
            'phone' => '0000000000',
            'email' => 'admin@admin.com',
            'nickname' => 'Superadmin',
            'photo' => 'default.png',
            'password' => bcrypt('change_me'),
            'code' => 'HP0APP',
            'admin' => 1,
        ]);

        DB::table('users')->insert([
            'name' => 'Nivel',
            'last_name' => 'Uno',
            'dni' => '19455541',
            'phone' => '0000000000',
            'email' => 'nivel1@admin.com',
            'nickname' => 'nivel1',
            'photo' => 'default.png',
            'password' => bcrypt('nivel1'),
            'code' => 'NIVEL1',
            'admin' => 0,
        ]);

        DB::table('games')->insert([
            'user_id' => 2
        ]);
    }
}
