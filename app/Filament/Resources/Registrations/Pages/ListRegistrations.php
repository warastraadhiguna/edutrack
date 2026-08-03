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
                ->label('Registrasi Cepat')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('success')
                ->modalHeading('Registrasi Siswa Cepat')
                ->modalDescription('Pilih subject, lalu masukkan email siswa satu per baris.')
                ->modalSubmitActionLabel('Simpan')
                ->modalCancelActionLabel('Batal')
                ->schema([
                    Select::make('schedule_id')
                        ->label('Subject')
                        ->options(fn (): array => self::getActiveScheduleOptions())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('emails')
                        ->label('Data siswa')
                        ->placeholder("23442424@gmail.com\n12342424@gmail.com\n56742424@gmail.com")
                        ->helperText('Masukkan satu email pada setiap baris.')
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
                            ->title('Registrasi belum disimpan')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $message = "{$result['created']} registration berhasil ditambahkan.";

                    if ($result['skipped'] > 0) {
                        $message .= " {$result['skipped']} data yang sudah terdaftar dilewati.";
                    }

                    Notification::make()
                        ->title('Registrasi berhasil disimpan')
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
                $schedule->id => $schedule->subject?->name ?? 'Subject tidak tersedia',
            ])
            ->all();
    }
}
