<?php

namespace Tests\Feature;

use App\Filament\Resources\Schedules\Pages\CreateSchedule;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleDriveLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_drive_location_is_saved_from_the_schedule_form(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);

        $period = Period::query()->create([
            'name' => 'Active Period',
            'default' => true,
        ]);

        $subject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'WEB',
            'name' => 'Web Programming',
        ]);

        $driveLocation = 'https://drive.google.com/drive/folders/example-folder-id';

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'note' => 'Monday',
                'drive_location' => $driveLocation,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $schedule = Schedule::query()->firstOrFail();

        $this->assertSame($period->id, $schedule->period_id);
        $this->assertSame($driveLocation, $schedule->drive_location);

        Livewire::test(ListSchedules::class)
            ->assertSee('Open folder')
            ->assertSee($driveLocation, escape: false);
    }

    public function test_drive_location_must_be_a_valid_url(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);

        Period::query()->create([
            'name' => 'Active Period',
            'default' => true,
        ]);

        $subject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'URL',
            'name' => 'URL Validation',
        ]);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'drive_location' => 'not-a-url',
            ])
            ->call('create')
            ->assertHasFormErrors(['drive_location']);

        $this->assertDatabaseCount('schedules', 0);
    }
}
