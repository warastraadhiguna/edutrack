<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class QuickUserService
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function create(string $userInput, int $creatorId): array
    {
        $roleId = Role::query()->where('name', 'user')->value('id');

        if (! $roleId) {
            throw new InvalidArgumentException('The user role is not available.');
        }

        $lines = collect(preg_split('/\R/u', trim($userInput)) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('Enter at least one user.');
        }

        $users = [];
        $invalidLines = [];

        foreach ($lines as $index => $line) {
            $parts = preg_split('/\s+/u', $line, 2) ?: [];
            $email = strtolower(trim($parts[0] ?? ''));
            $name = trim($parts[1] ?? '');

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $name === '') {
                $invalidLines[] = (string) ($index + 1);

                continue;
            }

            $users[$email] ??= [
                'email' => $email,
                'name' => $name,
            ];
        }

        if ($invalidLines !== []) {
            throw new InvalidArgumentException(
                'Invalid format on line(s): '.implode(', ', $invalidLines).'. Use: email full name.'
            );
        }

        $existingEmails = User::query()
            ->whereIn(DB::raw('LOWER(email)'), array_keys($users))
            ->pluck('email')
            ->map(fn (string $email): string => strtolower($email))
            ->flip();

        return DB::transaction(function () use ($users, $existingEmails, $creatorId, $roleId): array {
            $created = 0;
            $skipped = 0;

            foreach ($users as $user) {
                if ($existingEmails->has($user['email'])) {
                    $skipped++;

                    continue;
                }

                User::query()->create([
                    'user_id' => $creatorId,
                    'role_id' => $roleId,
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make('1'),
                ]);

                $created++;
            }

            return compact('created', 'skipped');
        });
    }
}
