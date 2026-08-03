<?php

namespace Tests\Feature;

use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuickRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class QuickRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_options_only_contain_schedules_from_the_active_period(): void
    {
        $creator = User::query()->findOrFail(1);
        $activePeriod = Period::query()->create(['name' => 'Active', 'default' => true]);
        $archivedPeriod = Period::query()->create(['name' => 'Archived', 'default' => false]);
        $activeSchedule = $this->createSchedule($creator, $activePeriod, 'WEB', 'Pemrograman Web');
        $archivedSchedule = $this->createSchedule($creator, $archivedPeriod, 'OLD', 'Subject Lama');

        $options = ListRegistrations::getActiveScheduleOptions();

        $this->assertSame('Pemrograman Web', $options[$activeSchedule->id]);
        $this->assertArrayNotHasKey($archivedSchedule->id, $options);
    }

    public function test_it_creates_multiple_registrations_from_email_input_and_skips_duplicates(): void
    {
        $creator = User::query()->findOrFail(1);
        $period = Period::query()->create(['name' => 'Active', 'default' => true]);
        $schedule = $this->createSchedule($creator, $period, 'WEB', 'Pemrograman Web');
        $firstStudent = User::factory()->create(['email' => '23442424@gmail.com', 'role_id' => 3]);
        $secondStudent = User::factory()->create(['email' => '12342424@gmail.com', 'role_id' => 3]);
        $service = app(QuickRegistrationService::class);

        $result = $service->create(
            $schedule->id,
            "23442424@gmail.com\n12342424@gmail.com\n23442424@gmail.com",
            $creator->id,
        );

        $this->assertSame(['created' => 2, 'skipped' => 0], $result);
        $this->assertDatabaseHas('registrations', [
            'student_user_id' => $firstStudent->id,
            'schedule_id' => $schedule->id,
            'grade' => null,
        ]);
        $this->assertDatabaseHas('registrations', [
            'student_user_id' => $secondStudent->id,
            'schedule_id' => $schedule->id,
            'grade' => null,
        ]);

        $secondResult = $service->create(
            $schedule->id,
            "23442424@gmail.com\n12342424@gmail.com",
            $creator->id,
        );

        $this->assertSame(['created' => 0, 'skipped' => 2], $secondResult);
        $this->assertSame(2, Registration::query()->count());
    }

    public function test_unknown_email_cancels_the_whole_quick_registration(): void
    {
        $creator = User::query()->findOrFail(1);
        $period = Period::query()->create(['name' => 'Active', 'default' => true]);
        $schedule = $this->createSchedule($creator, $period, 'WEB', 'Pemrograman Web');
        User::factory()->create(['email' => 'known@gmail.com', 'role_id' => 3]);

        try {
            app(QuickRegistrationService::class)->create(
                $schedule->id,
                "known@gmail.com\nunknown@gmail.com",
                $creator->id,
            );

            $this->fail('An unknown email should prevent registration creation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('unknown@gmail.com', $exception->getMessage());
        }

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_superadmin_can_submit_the_quick_registration_modal(): void
    {
        $creator = User::query()->findOrFail(1);
        $this->actingAs($creator);

        $period = Period::query()->create(['name' => 'Active', 'default' => true]);
        $schedule = $this->createSchedule($creator, $period, 'FAST', 'Registrasi Cepat');
        $student = User::factory()->create(['email' => 'fast@gmail.com', 'role_id' => 3]);

        Livewire::test(ListRegistrations::class)
            ->assertActionExists('quickRegistration')
            ->callAction('quickRegistration', [
                'schedule_id' => $schedule->id,
                'emails' => 'fast@gmail.com',
            ]);

        $this->assertDatabaseHas('registrations', [
            'student_user_id' => $student->id,
            'schedule_id' => $schedule->id,
            'user_id' => $creator->id,
        ]);
    }

    private function createSchedule(User $creator, Period $period, string $code, string $name): Schedule
    {
        $subject = Subject::query()->create([
            'user_id' => $creator->id,
            'code' => $code,
            'name' => $name,
        ]);

        return Schedule::query()->create([
            'user_id' => $creator->id,
            'period_id' => $period->id,
            'subject_id' => $subject->id,
        ]);
    }
}
