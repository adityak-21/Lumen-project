<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $users = User::create([
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password' => app('hash')->make('password123'),
        ]);
    }
}
