<?php

namespace Tests\Feature;

use App\Filament\Widgets\DashboardStats;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_registered_students_compared_to_all_users_with_role_user(): void
    {
        $studentA = User::factory()->create(['role_id' => 3]);
        $studentB = User::factory()->create(['role_id' => 3]);
        $studentC = User::factory()->create(['role_id' => 3]);
        $studentD = User::factory()->create(['role_id' => 3]);
        User::factory()->create(['role_id' => 2]);

        $defaultPeriod = Period::query()->create([
            'name' => '2026 Active',
            'default' => 1,
        ]);

        $archivedPeriod = Period::query()->create([
            'name' => '2025 Archived',
            'default' => 0,
        ]);

        $defaultSchedule = Schedule::query()->create([
            'period_id' => $defaultPeriod->id,
        ]);

        $archivedSchedule = Schedule::query()->create([
            'period_id' => $archivedPeriod->id,
        ]);

        Registration::query()->create([
            'student_user_id' => $studentA->id,
            'schedule_id' => $defaultSchedule->id,
            'grade' => 'A',
        ]);

        Registration::query()->create([
            'student_user_id' => $studentA->id,
            'schedule_id' => $defaultSchedule->id,
            'grade' => 'A',
        ]);

        Registration::query()->create([
            'student_user_id' => $studentB->id,
            'schedule_id' => $defaultSchedule->id,
            'grade' => 'A',
        ]);

        Registration::query()->create([
            'student_user_id' => $studentC->id,
            'schedule_id' => $defaultSchedule->id,
            'grade' => 'A',
        ]);

        Registration::query()->create([
            'student_user_id' => $studentD->id,
            'schedule_id' => $archivedSchedule->id,
            'grade' => 'A',
        ]);

        Registration::query()->create([
            'student_user_id' => $studentB->id,
            'schedule_id' => $defaultSchedule->id,
            'grade' => 'A',
        ]);

        $this->assertSame(3, DashboardStats::getRegisteredUsersCount());
        $this->assertSame(4, DashboardStats::getTotalStudentUsersCount());
        $this->assertSame('3/4', DashboardStats::getActiveUsersSummary());
    }
}
