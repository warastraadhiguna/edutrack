<?php

namespace Tests\Feature;

use App\Filament\Widgets\StudentSubjectsWidget;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentSubjectsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_only_visible_for_role_user(): void
    {
        $student = User::factory()->create(['role_id' => 3]);
        $superadmin = User::query()->findOrFail(1);

        $this->actingAs($student);
        $this->assertTrue(StudentSubjectsWidget::canView());

        $this->actingAs($superadmin);
        $this->assertFalse(StudentSubjectsWidget::canView());
    }

    public function test_it_only_returns_subjects_taken_by_the_logged_in_student(): void
    {
        $student = User::factory()->create(['role_id' => 3]);
        $otherStudent = User::factory()->create(['role_id' => 3]);
        $creator = User::query()->findOrFail(1);

        $this->actingAs($student);

        $periodOne = Period::query()->create([
            'name' => '2026 Ganjil',
            'default' => 1,
        ]);

        $periodTwo = Period::query()->create([
            'name' => '2026 Genap',
            'default' => 0,
        ]);

        $ownRegistrationOne = $this->createRegistration($student, $creator, $periodOne, 'Algoritma');
        $ownRegistrationTwo = $this->createRegistration($student, $creator, $periodTwo, 'Basis Data');
        $otherRegistration = $this->createRegistration($otherStudent, $creator, $periodOne, 'Jaringan');

        $visibleIds = StudentSubjectsWidget::getStudentSubjectsQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($ownRegistrationOne->id, $visibleIds);
        $this->assertContains($ownRegistrationTwo->id, $visibleIds);
        $this->assertNotContains($otherRegistration->id, $visibleIds);
    }

    public function test_it_displays_the_logged_in_students_drive_folder_link(): void
    {
        $student = User::factory()->create(['role_id' => 3]);
        $creator = User::query()->findOrFail(1);
        $this->actingAs($student);

        $period = Period::query()->create([
            'name' => '2026 Genap',
            'default' => 1,
        ]);

        $registration = $this->createRegistration($student, $creator, $period, 'Pemrograman Web');
        $driveLocation = 'https://drive.google.com/drive/folders/student-folder';
        $registration->update(['drive_location' => $driveLocation]);

        Livewire::test(StudentSubjectsWidget::class)
            ->assertCanSeeTableRecords([$registration])
            ->assertTableColumnStateSet('drive_location', $driveLocation, $registration)
            ->assertSee('Open Folder')
            ->assertSee($driveLocation, escape: false);
    }

    private function createRegistration(User $student, User $creator, Period $period, string $subjectName): Registration
    {
        $subject = Subject::query()->create([
            'user_id' => $creator->id,
            'code' => fake()->unique()->bothify('SUBJ##'),
            'name' => $subjectName,
        ]);

        $schedule = Schedule::query()->create([
            'user_id' => $creator->id,
            'period_id' => $period->id,
            'subject_id' => $subject->id,
            'note' => $subjectName,
        ]);

        return Registration::query()->create([
            'user_id' => $creator->id,
            'student_user_id' => $student->id,
            'schedule_id' => $schedule->id,
            'grade' => 'A',
        ]);
    }
}
