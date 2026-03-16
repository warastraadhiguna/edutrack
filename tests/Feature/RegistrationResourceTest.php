<?php

namespace Tests\Feature;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_only_sees_registrations_from_the_default_period(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $student = User::factory()->create(['role_id' => 3]);

        $this->actingAs($superadmin);

        [$defaultRegistration, $nonDefaultRegistration] = $this->createRegistrationsForDifferentPeriods($student, $superadmin);

        $visibleIds = RegistrationResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($defaultRegistration->id, $visibleIds);
        $this->assertNotContains($nonDefaultRegistration->id, $visibleIds);
    }

    public function test_regular_user_only_sees_their_own_registrations_from_the_default_period(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $otherUser = User::factory()->create(['role_id' => 3]);
        $superadmin = User::query()->findOrFail(1);

        $this->actingAs($user);

        $defaultPeriod = Period::query()->create([
            'name' => 'Global Default',
            'default' => 1,
        ]);

        $nonDefaultPeriod = Period::query()->create([
            'name' => 'Archived Period',
            'default' => 0,
        ]);

        $ownDefaultRegistration = $this->createRegistration($user, $defaultPeriod, $superadmin);
        $ownNonDefaultRegistration = $this->createRegistration($user, $nonDefaultPeriod, $superadmin);
        $otherUsersDefaultRegistration = $this->createRegistration($otherUser, $defaultPeriod, $superadmin);

        $visibleIds = RegistrationResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($ownDefaultRegistration->id, $visibleIds);
        $this->assertNotContains($ownNonDefaultRegistration->id, $visibleIds);
        $this->assertNotContains($otherUsersDefaultRegistration->id, $visibleIds);
    }

    /**
     * @return array{0: \App\Models\Registration, 1: \App\Models\Registration}
     */
    private function createRegistrationsForDifferentPeriods(User $student, User $creator): array
    {
        $defaultPeriod = Period::query()->create([
            'name' => "Default {$student->id}",
            'default' => 1,
        ]);

        $nonDefaultPeriod = Period::query()->create([
            'name' => "Non Default {$student->id}",
            'default' => 0,
        ]);

        $defaultRegistration = $this->createRegistration($student, $defaultPeriod, $creator);
        $nonDefaultRegistration = $this->createRegistration($student, $nonDefaultPeriod, $creator);

        return [$defaultRegistration, $nonDefaultRegistration];
    }

    private function createRegistration(User $student, Period $period, User $creator): Registration
    {
        $schedule = Schedule::query()->create([
            'user_id' => $creator->id,
            'period_id' => $period->id,
        ]);

        return Registration::query()->create([
            'user_id' => $creator->id,
            'student_user_id' => $student->id,
            'schedule_id' => $schedule->id,
            'grade' => 'A',
        ]);
    }
}
