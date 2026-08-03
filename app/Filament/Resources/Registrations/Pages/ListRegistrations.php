<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Models\Schedule;
use App\Services\QuickRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickRegistration')
                ->label('Quick Registration')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('success')
                ->modalHeading('Quick Student Registration')
                ->modalDescription('Select a subject, then enter one student email address per line.')
                ->modalSubmitActionLabel('Save')
                ->modalCancelActionLabel('Cancel')
                ->schema([
                    Select::make('schedule_id')
                        ->label('Subject')
                        ->options(fn (): array => self::getActiveScheduleOptions())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('emails')
                        ->label('Student emails')
                        ->placeholder("23442424@gmail.com\n12342424@gmail.com\n56742424@gmail.com")
                        ->helperText('Enter one email address per line.')
                        ->rows(10)
                        ->required(),
                ])
                ->action(function (array $data, Action $action): void {
                    try {
                        $result = app(QuickRegistrationService::class)->create(
                            (int) $data['schedule_id'],
                            (string) $data['emails'],
                            (int) Auth::id(),
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('Registrations were not saved')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $registrationLabel = $result['created'] === 1 ? 'registration' : 'registrations';
                    $message = "{$result['created']} {$registrationLabel} added successfully.";

                    if ($result['skipped'] > 0) {
                        $skippedLabel = $result['skipped'] === 1 ? 'existing registration was' : 'existing registrations were';
                        $message .= " {$result['skipped']} {$skippedLabel} skipped.";
                    }

                    Notification::make()
                        ->title('Registrations saved')
                        ->body($message)
                        ->success()
                        ->send();
                })
                ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
            CreateAction::make()->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getActiveScheduleOptions(): array
    {
        return Schedule::query()
            ->whereHas('period', fn (Builder $query) => $query->where('default', true))
            ->with('subject:id,name')
            ->orderBy(
                DB::table('subjects')
                    ->select('name')
                    ->whereColumn('subjects.id', 'schedules.subject_id')
            )
            ->get()
            ->mapWithKeys(fn (Schedule $schedule): array => [
                $schedule->id => $schedule->subject?->name ?? 'Subject unavailable',
            ])
            ->all();
    }
}
