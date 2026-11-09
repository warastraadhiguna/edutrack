<div class="grid gap-6 md:grid-cols-2">
    @foreach (['Submitted' => $submitted, 'Not Submitted' => $notSubmitted] as $heading => $students)
        <section>
            <h3 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">
                {{ $heading }} ({{ $students->count() }})
            </h3>
            <ol class="max-h-96 overflow-y-auto divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                @forelse ($students as $student)
                    <li class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $loop->iteration }}. {{ $student->name }}</p>
                        <p class="break-all text-sm text-gray-500 dark:text-gray-400">{{ $student->email }}</p>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No students in this list.</li>
                @endforelse
            </ol>
        </section>
    @endforeach
</div>
