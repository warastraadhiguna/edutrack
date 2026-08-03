<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuickRegistrationService
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function create(int $scheduleId, string $emailInput, int $creatorId): array
    {
        $schedule = Schedule::query()
            ->whereKey($scheduleId)
            ->whereHas('period', fn ($query) => $query->where('default', true))
            ->first();

        if (! $schedule) {
            throw new InvalidArgumentException('The subject was not found or its period is no longer active.');
        }

        $emails = collect(preg_split('/[\s,;]+/', strtolower(trim($emailInput))) ?: [])
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            throw new InvalidArgumentException('Enter at least one student email address.');
        }

        $invalidEmails = $emails
            ->reject(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values();

        if ($invalidEmails->isNotEmpty()) {
            throw new InvalidArgumentException(
                'Invalid email format: '.$invalidEmails->join(', ')
            );
        }

        $users = User::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails->all())
            ->get()
            ->keyBy(fn (User $user): string => strtolower($user->email));

        $missingEmails = $emails->reject(fn (string $email): bool => $users->has($email))->values();

        if ($missingEmails->isNotEmpty()) {
            throw new InvalidArgumentException(
                'These emails are not registered users: '.$missingEmails->join(', ')
            );
        }

        return DB::transaction(function () use ($emails, $users, $schedule, $creatorId): array {
            $created = 0;
            $skipped = 0;

            foreach ($emails as $email) {
                $registration = Registration::query()->firstOrCreate(
                    [
                        'student_user_id' => $users->get($email)->id,
                        'schedule_id' => $schedule->id,
                    ],
                    [
                        'user_id' => $creatorId,
                        'grade' => null,
                    ],
                );

                $registration->wasRecentlyCreated ? $created++ : $skipped++;
            }

            return compact('created', 'skipped');
        });
    }
}
