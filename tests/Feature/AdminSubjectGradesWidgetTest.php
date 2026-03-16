<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminSubjectGradesWidget;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubjectGradesWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_only_visible_for_roles_other_than_user(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $student = User::factory()->create(['role_id' => 3]);

        $this->actingAs($superadmin);
        $this->assertTrue(AdminSubjectGradesWidget::canView());

        $this->actingAs($student);
        $this->assertFalse(AdminSubjectGradesWidget::canView());
    }

    public function test_it_returns_subject_summaries_for_the_default_period_only(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $studentA = User::factory()->create(['role_id' => 3]);
        $studentB = User::factory()->create(['role_id' => 3]);
        $studentC = User::factory()->create(['role_id' => 3]);

        $this->actingAs($superadmin);

        $defaultPeriod = Period::query()->create([
            'name' => '2026 Ganjil',
            'default' => 1,
        ]);

        $archivedPeriod = Period::query()->create([
            'name' => '2025 Genap',
            'default' => 0,
        ]);

        $algoritma = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'ALG01',
            'name' => 'Algoritma',
        ]);

        $basisData = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'BD01',
            'name' => 'Basis Data',
        ]);

        $jadul = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'OLD01',
            'name' => 'Mata Kuliah Lama',
        ]);

        $algoritmaSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $defaultPeriod->id,
            'subject_id' => $algoritma->id,
        ]);

        $basisDataSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $defaultPeriod->id,
            'subject_id' => $basisData->id,
        ]);

        $archivedSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $archivedPeriod->id,
            'subject_id' => $jadul->id,
        ]);

        Registration::query()->create([
            'user_id' => $superadmin->id,
            'student_user_id' => $studentA->id,
            'schedule_id' => $algoritmaSchedule->id,
            'grade' => '',
        ]);

        Registration::query()->create([
            'user_id' => $superadmin->id,
            'student_user_id' => $studentB->id,
            'schedule_id' => $algoritmaSchedule->id,
            'grade' => 'A',
        ]);

        Registration::query()->create([
            'user_id' => $superadmin->id,
            'student_user_id' => $studentC->id,
            'schedule_id' => $basisDataSchedule->id,
            'grade' => '',
        ]);

        Registration::query()->create([
            'user_id' => $superadmin->id,
            'student_user_id' => $studentA->id,
            'schedule_id' => $archivedSchedule->id,
            'grade' => '',
        ]);

        $rows = AdminSubjectGradesWidget::getSubjectGradeSummaryQuery()
            ->get()
            ->keyBy('subject_name');

        $this->assertCount(2, $rows);
        $this->assertSame(1, (int) $rows['Algoritma']->filled_grade_count);
        $this->assertSame(2, (int) $rows['Algoritma']->total_students);
        $this->assertSame(0, (int) $rows['Basis Data']->filled_grade_count);
        $this->assertSame(1, (int) $rows['Basis Data']->total_students);
        $this->assertFalse($rows->has('Mata Kuliah Lama'));
    }
}
