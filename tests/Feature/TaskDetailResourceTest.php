<?php

namespace Tests\Feature;

use App\Filament\Resources\TaskDetails\Tables\TaskDetailsTable;
use App\Filament\Resources\TaskDetails\TaskDetailResource;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDetailResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_only_sees_task_details_from_the_default_period(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);

        [$defaultTaskDetail, $nonDefaultTaskDetail] = $this->createTaskDetailsForDifferentPeriods($superadmin);

        $visibleIds = TaskDetailResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($defaultTaskDetail->id, $visibleIds);
        $this->assertNotContains($nonDefaultTaskDetail->id, $visibleIds);
    }

    public function test_regular_user_only_sees_their_own_task_details_from_the_default_period(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $otherUser = User::factory()->create(['role_id' => 3]);

        $this->actingAs($user);

        $defaultPeriod = Period::query()->create([
            'user_id' => $user->id,
            'name' => 'Global Default',
            'default' => 1,
        ]);

        $nonDefaultPeriod = Period::query()->create([
            'user_id' => $user->id,
            'name' => 'Archived Period',
            'default' => 0,
        ]);

        $ownDefaultTaskDetail = $this->createTaskDetail($user, $defaultPeriod, 'Own Default Task', 'https://example.com/own-default');
        $ownNonDefaultTaskDetail = $this->createTaskDetail($user, $nonDefaultPeriod, 'Own Non Default Task', 'https://example.com/own-non-default');
        $otherUsersDefaultTaskDetail = $this->createTaskDetail($otherUser, $defaultPeriod, 'Other Default Task', 'https://example.com/other-default');

        $visibleIds = TaskDetailResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($ownDefaultTaskDetail->id, $visibleIds);
        $this->assertNotContains($ownNonDefaultTaskDetail->id, $visibleIds);
        $this->assertNotContains($otherUsersDefaultTaskDetail->id, $visibleIds);
    }

    public function test_task_filter_options_are_limited_by_subject_and_default_period(): void
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

        $subjectA = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'SUBA',
            'name' => 'Subject A',
        ]);

        $subjectB = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'SUBB',
            'name' => 'Subject B',
        ]);

        $subjectACurrentSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $defaultPeriod->id,
            'subject_id' => $subjectA->id,
        ]);

        $subjectBCurrentSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $defaultPeriod->id,
            'subject_id' => $subjectB->id,
        ]);

        $subjectAArchivedSchedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $archivedPeriod->id,
            'subject_id' => $subjectA->id,
        ]);

        $subjectACurrentTask = Task::query()->create([
            'user_id' => $superadmin->id,
            'schedule_id' => $subjectACurrentSchedule->id,
            'name' => 'Subject A Current Task',
            'percentage' => 10,
            'index' => 1,
        ]);

        Task::query()->create([
            'user_id' => $superadmin->id,
            'schedule_id' => $subjectBCurrentSchedule->id,
            'name' => 'Subject B Current Task',
            'percentage' => 10,
            'index' => 1,
        ]);

        Task::query()->create([
            'user_id' => $superadmin->id,
            'schedule_id' => $subjectAArchivedSchedule->id,
            'name' => 'Subject A Archived Task',
            'percentage' => 10,
            'index' => 1,
        ]);

        $options = TaskDetailsTable::getTaskOptions($subjectA->id, $defaultPeriod->id);

        $this->assertSame([
            $subjectACurrentTask->id => 'Subject A Current Task',
        ], $options);
    }

    /**
     * @return array{0: \App\Models\TaskDetail, 1: \App\Models\TaskDetail}
     */
    private function createTaskDetailsForDifferentPeriods(User $user): array
    {
        $defaultPeriod = Period::query()->create([
            'user_id' => $user->id,
            'name' => "Default {$user->id}",
            'default' => 1,
        ]);

        $nonDefaultPeriod = Period::query()->create([
            'user_id' => $user->id,
            'name' => "Non Default {$user->id}",
            'default' => 0,
        ]);

        $defaultSchedule = Schedule::query()->create([
            'user_id' => $user->id,
            'period_id' => $defaultPeriod->id,
        ]);

        $nonDefaultSchedule = Schedule::query()->create([
            'user_id' => $user->id,
            'period_id' => $nonDefaultPeriod->id,
        ]);

        $defaultTask = Task::query()->create([
            'user_id' => $user->id,
            'schedule_id' => $defaultSchedule->id,
            'name' => "Task Default {$user->id}",
            'percentage' => 10,
            'index' => 1,
        ]);

        $nonDefaultTask = Task::query()->create([
            'user_id' => $user->id,
            'schedule_id' => $nonDefaultSchedule->id,
            'name' => "Task Non Default {$user->id}",
            'percentage' => 10,
            'index' => 2,
        ]);

        $defaultTaskDetail = TaskDetail::query()->create([
            'user_id' => $user->id,
            'task_id' => $defaultTask->id,
            'document_link' => "https://example.com/default-{$user->id}",
            'score' => 90,
        ]);

        $nonDefaultTaskDetail = TaskDetail::query()->create([
            'user_id' => $user->id,
            'task_id' => $nonDefaultTask->id,
            'document_link' => "https://example.com/non-default-{$user->id}",
            'score' => 80,
        ]);

        return [$defaultTaskDetail, $nonDefaultTaskDetail];
    }

    private function createTaskDetail(User $user, Period $period, string $taskName, string $documentLink): TaskDetail
    {
        $schedule = Schedule::query()->create([
            'user_id' => $user->id,
            'period_id' => $period->id,
        ]);

        $task = Task::query()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'name' => $taskName,
            'percentage' => 10,
            'index' => 1,
        ]);

        return TaskDetail::query()->create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'document_link' => $documentLink,
            'score' => 90,
        ]);
    }
}
