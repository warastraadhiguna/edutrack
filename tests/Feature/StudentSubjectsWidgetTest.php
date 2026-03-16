<?php

namespace Tests\Feature;

use App\Filament\Widgets\StudentSubjectsWidget;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
