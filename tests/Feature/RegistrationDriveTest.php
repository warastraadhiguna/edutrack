<?php

namespace Tests\Feature;

use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\RegistrationDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RegistrationDriveTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_saves_a_drive_folder_for_each_registration(): void
    {
        [$superadmin, $subject, $schedule] = $this->createSubjectSchedule();
        $firstRegistration = $this->createRegistration($superadmin, $schedule, '12345@gmail.com');
        $secondRegistration = $this->createRegistration($superadmin, $schedule, '67890@gmail.com');
        $alreadyLinked = $this->createRegistration($superadmin, $schedule, '11111@gmail.com');
        $alreadyLinked->update(['drive_location' => 'https://drive.google.com/drive/folders/already-linked']);

        $googleDrive = Mockery::mock(GoogleDriveService::class);
        $googleDrive->shouldReceive('findOrCreateFolder')
            ->once()
            ->with($schedule->drive_location, '12345')
            ->andReturn([
                'url' => 'https://drive.google.com/drive/folders/folder-12345',
                'created' => true,
            ]);
        $googleDrive->shouldReceive('findOrCreateFolder')
            ->once()
            ->with($schedule->drive_location, '67890')
            ->andReturn([
                'url' => 'https://drive.google.com/drive/folders/folder-67890',
                'created' => false,
            ]);

        $result = (new RegistrationDriveService($googleDrive))->createFolders($subject->id);

        $this->assertSame(['created' => 1, 'linked' => 1, 'skipped' => 1], $result);
        $this->assertSame('https://drive.google.com/drive/folders/folder-12345', $firstRegistration->fresh()->drive_location);
        $this->assertSame('https://drive.google.com/drive/folders/folder-67890', $secondRegistration->fresh()->drive_location);
        $this->assertSame('https://drive.google.com/drive/folders/already-linked', $alreadyLinked->fresh()->drive_location);
    }

    public function test_create_drive_action_is_only_visible_with_a_subject_filter_and_updates_the_registration(): void
    {
        [$superadmin, $subject, $schedule] = $this->createSubjectSchedule();
        $registration = $this->createRegistration($superadmin, $schedule, '24680@gmail.com');
        $this->actingAs($superadmin);

        $googleDrive = Mockery::mock(GoogleDriveService::class);
        $googleDrive->shouldReceive('findOrCreateFolder')
            ->once()
            ->with($schedule->drive_location, '24680')
            ->andReturn([
                'url' => 'https://drive.google.com/drive/folders/folder-24680',
                'created' => true,
            ]);
        $this->app->instance(GoogleDriveService::class, $googleDrive);

        Livewire::test(ListRegistrations::class)
            ->assertTableActionHidden('createDrive')
            ->filterTable('subject_id', $subject->id)
            ->assertTableActionVisible('createDrive')
            ->callTableAction('createDrive');

        $this->assertSame(
            'https://drive.google.com/drive/folders/folder-24680',
            $registration->fresh()->drive_location,
        );
    }

    public function test_google_drive_folder_id_is_extracted_from_supported_locations(): void
    {
        $service = app(GoogleDriveService::class);

        $this->assertSame(
            'folder-id',
            $service->extractFolderId('https://drive.google.com/drive/folders/folder-id?usp=sharing'),
        );
        $this->assertSame('raw-folder-id', $service->extractFolderId('raw-folder-id'));
    }

    public function test_google_drive_folder_is_created_using_oauth_credentials(): void
    {
        Cache::flush();
        config()->set('services.google_drive', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'refresh_token' => 'refresh-token',
            'service_account_json' => null,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ]),
            'https://www.googleapis.com/drive/v3/files*' => Http::sequence()
                ->push(['files' => []])
                ->push([
                    'id' => 'new-folder-id',
                    'webViewLink' => 'https://drive.google.com/drive/folders/new-folder-id',
                ]),
        ]);

        $result = app(GoogleDriveService::class)->findOrCreateFolder(
            'https://drive.google.com/drive/folders/parent-folder',
            '12345',
        );

        $this->assertSame([
            'url' => 'https://drive.google.com/drive/folders/new-folder-id',
            'created' => true,
        ], $result);

        Http::assertSentCount(3);
    }

    /**
     * @return array{User, Subject, Schedule}
     */
    private function createSubjectSchedule(): array
    {
        $superadmin = User::query()->findOrFail(1);
        $period = Period::query()->create([
            'name' => 'Active Period',
            'default' => true,
        ]);
        $subject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'DRV',
            'name' => 'Drive Subject',
        ]);
        $schedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $period->id,
            'subject_id' => $subject->id,
            'drive_location' => 'https://drive.google.com/drive/folders/parent-folder',
        ]);

        return [$superadmin, $subject, $schedule];
    }

    private function createRegistration(User $creator, Schedule $schedule, string $email): Registration
    {
        $student = User::factory()->create([
            'role_id' => 3,
            'email' => $email,
        ]);

        return Registration::query()->create([
            'user_id' => $creator->id,
            'student_user_id' => $student->id,
            'schedule_id' => $schedule->id,
            'grade' => null,
        ]);
    }
}
