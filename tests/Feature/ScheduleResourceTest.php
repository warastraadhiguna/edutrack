<?php

namespace Tests\Feature;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_list_only_contains_records_from_the_active_period(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);

        $activePeriod = Period::query()->create([
            'name' => 'Active Period',
            'default' => true,
        ]);

        $archivedPeriod = Period::query()->create([
            'name' => 'Archived Period',
            'default' => false,
        ]);

        $subject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'TEST',
            'name' => 'Test Subject',
        ]);

        $activeSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $activePeriod->id,
            'subject_id' => $subject->id,
        ]);

        $archivedSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $archivedPeriod->id,
            'subject_id' => $subject->id,
        ]);

        $visibleIds = ScheduleResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($activeSchedule->id, $visibleIds);
        $this->assertNotContains($archivedSchedule->id, $visibleIds);
    }
}
