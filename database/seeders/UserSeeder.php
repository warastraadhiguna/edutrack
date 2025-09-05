<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csv = <<<'CSV'
name,email,password
Liyani Angellita,562024004@student.uksw.edu,210106
YOGA SETIAWAN,562024002@student.uksw.edu,250506
CSV;

        $lines = array_filter(array_map('trim', explode("\n", trim($csv))));
        $headers = str_getcsv(array_shift($lines)); // ['name','email','password']

        $now = now();
        $data = [];

        foreach ($lines as $line) {
            $row = array_combine($headers, str_getcsv($line));
            if (! $row) {
                continue;
            }

            $name = trim($row['name'] ?? '');
            $email = strtolower(trim($row['email'] ?? ''));
            $passwordPlain = (string) ($row['password'] ?? '');

            if ($name === '' || $email === '' || $passwordPlain === '') {
                continue; // skip baris tidak lengkap
            }

            $data[] = [
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($passwordPlain),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Upsert berdasarkan email (jika email sudah ada -> update name, password)
        DB::table('users')->upsert(
            $data,
            ['email'],
            ['name', 'password', 'updated_at']
        );
    }
}
