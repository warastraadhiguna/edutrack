<?php

namespace Tests\Feature;

use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksTableTest extends TestCase
{
    use RefreshDatabase;

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
