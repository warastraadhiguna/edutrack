<?php

namespace Tests\Feature;

use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\TaskSubmissionService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitted_column_opens_lists_for_registered_students_and_the_selected_task(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);
        $period = Period::query()->create(['name' => 'Active', 'default' => true]);
        $schedule = Schedule::query()->create(['period_id' => $period->id]);
        $task = Task::query()->create([
            'schedule_id' => $schedule->id,
            'name' => 'Selected Task',
            'percentage' => 50,
            'index' => 1,
        ]);
        $otherTask = Task::query()->create([
            'schedule_id' => $schedule->id,
            'name' => 'Other Task',
            'percentage' => 50,
            'index' => 2,
        ]);
        $submitted = User::factory()->create(['role_id' => 3, 'name' => 'Submitted Student']);
        $pending = User::factory()->create(['role_id' => 3, 'name' => 'Pending Student']);
        $outsider = User::factory()->create(['role_id' => 3, 'name' => 'Unregistered Student']);

        foreach ([$submitted, $pending] as $student) {
            Registration::query()->create([
                'student_user_id' => $student->id,
                'schedule_id' => $schedule->id,
                'grade' => null,
            ]);
        }

        foreach ([[$submitted, $task], [$pending, $otherTask], [$outsider, $task]] as [$student, $submissionTask]) {
            TaskDetail::query()->create([
                'user_id' => $student->id,
                'task_id' => $submissionTask->id,
                'document_link' => 'https://example.com/submission',
                'score' => 0,
            ]);
        }

        $lists = app(TaskSubmissionService::class)->students($task);
        $this->assertSame([$submitted->id], $lists['submitted']->pluck('id')->values()->all());
        $this->assertSame([$pending->id], $lists['notSubmitted']->pluck('id')->values()->all());

        $component = Livewire::test(ListTasks::class)
            ->assertTableColumnStateSet('submission_progress', '1 / 2', TaskResource::getEloquentQuery()->findOrFail($task->id))
            ->assertTableActionExists('viewSubmissions')
            ->mountAction(TestAction::make('viewSubmissions')->table($task))
            ->assertActionMounted(TestAction::make('viewSubmissions')->table($task));

        $action = $component->instance()->getMountedAction();
        $this->assertSame('Submissions: Selected Task', $action->getModalHeading());
        $html = $action->getModalContent()->render();
        $this->assertStringContainsString('Submitted (1)', $html);
        $this->assertStringContainsString('Not Submitted (1)', $html);
        $this->assertStringContainsString($submitted->name, $html);
        $this->assertStringContainsString($pending->name, $html);
        $this->assertStringContainsString($submitted->email, $html);
        $this->assertStringNotContainsString($outsider->name, $html);
    }

    public function test_subject_filter_options_only_include_subjects_from_the_default_period(): void
    {
        $superadmin = User::query()->findOrFail(1);

        $defaultPeriod = Period::query()->create([
            'user_id' => $superadmin->id,
            'name' => 'Current Period',
            'default' => 1,
        ]);

        $archivedPeriod = Period::query()->create([
            'user_id' => $superadmin->id,
            'name' => 'Archived Period',
            'default' => 0,
        ]);

        $currentSubject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'CURR',
            'name' => 'Current Subject',
        ]);

        $archivedSubject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'ARCH',
            'name' => 'Archived Subject',
        ]);

        Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $defaultPeriod->id,
            'subject_id' => $currentSubject->id,
        ]);

        Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $archivedPeriod->id,
            'subject_id' => $archivedSubject->id,
        ]);

        $this->assertSame([
            $currentSubject->id => 'Current Subject',
        ], TasksTable::getSubjectOptions());
    }

    public function test_percentage_total_is_only_visible_when_subject_filter_is_active(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);

        $defaultPeriod = Period::query()->create([
            'user_id' => $superadmin->id,
            'name' => 'Current Period',
            'default' => 1,
        ]);

        $subject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'ACTIVE',
            'name' => 'Active Subject',
        ]);

        $schedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $defaultPeriod->id,
            'subject_id' => $subject->id,
        ]);

        foreach ([40, 30] as $index => $percentage) {
            Task::query()->create([
                'user_id' => $superadmin->id,
                'schedule_id' => $schedule->id,
                'name' => "Task {$index}",
                'percentage' => $percentage,
                'index' => $index,
            ]);
        }

        $component = Livewire::test(ListTasks::class);
        $summarizer = $component->instance()
            ->getTable()
            ->getColumn('percentage')
            ->getSummarizer('percentage_total');

        $summarizer->query($component->instance()->getAllTableSummaryQuery());
        $this->assertFalse($summarizer->isVisible());

        $component
            ->filterTable('subject_id', $subject->id)
            ->assertTableColumnSummarySet('percentage', 'percentage_total', 70);

        $summarizer = $component->instance()
            ->getTable()
            ->getColumn('percentage')
            ->getSummarizer('percentage_total')
            ->query($component->instance()->getAllTableSummaryQuery());

        $this->assertTrue($summarizer->isVisible());
    }
}
