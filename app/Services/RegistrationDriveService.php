<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class RegistrationDriveService
{
    public function __construct(private readonly GoogleDriveService $googleDrive) {}

    /**
     * @return array{created: int, linked: int, skipped: int}
     */
    public function createFolders(int $subjectId): array
    {
        $registrations = Registration::query()
            ->whereHas('schedule', function (Builder $query) use ($subjectId): void {
                $query
                    ->where('subject_id', $subjectId)
                    ->whereHas('period', fn (Builder $periodQuery) => $periodQuery->where('default', true));
            })
            ->with(['studentUser:id,email', 'schedule:id,drive_location'])
            ->get();

        if ($registrations->isEmpty()) {
            throw new RuntimeException('No registrations were found for the selected subject.');
        }

        $missingParentFolder = $registrations->first(
            fn (Registration $registration): bool => blank($registration->schedule?->drive_location)
        );

        if ($missingParentFolder) {
            throw new RuntimeException('The selected subject does not have a Google Drive folder configured.');
        }

        $missingStudentEmail = $registrations->first(
            fn (Registration $registration): bool => blank($registration->studentUser?->email)
        );

        if ($missingStudentEmail) {
            throw new RuntimeException('One or more registrations do not have a student email address.');
        }

        $created = 0;
        $linked = 0;
        $skipped = 0;

        foreach ($registrations as $registration) {
            if (filled($registration->drive_location)) {
                $skipped++;

                continue;
            }

            $folderName = strstr($registration->studentUser->email, '@', true);

            if (blank($folderName)) {
                throw new RuntimeException("Unable to determine a folder name from {$registration->studentUser->email}.");
            }

            $folder = $this->googleDrive->findOrCreateFolder(
                $registration->schedule->drive_location,
                $folderName,
            );

            $registration->update(['drive_location' => $folder['url']]);
            $folder['created'] ? $created++ : $linked++;
        }

        return compact('created', 'linked', 'skipped');
    }
}
