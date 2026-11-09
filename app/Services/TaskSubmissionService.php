<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskSubmissionService
{
    /**
     * @return array{submitted: Collection, notSubmitted: Collection}
     */
    public function students(Task $task): array
    {
        $students = User::query()
            ->whereIn('id', Registration::query()
                ->select('student_user_id')
                ->where('schedule_id', $task->schedule_id))
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);

        $submittedIds = $task->taskDetails()->pluck('user_id')->flip();

        [$submitted, $notSubmitted] = $students->partition(
            fn (User $student): bool => $submittedIds->has($student->id)
        );

        return compact('submitted', 'notSubmitted');
    }
}
