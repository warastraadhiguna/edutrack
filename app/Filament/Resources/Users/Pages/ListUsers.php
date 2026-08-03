<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\QuickUserService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickUserEntry')
                ->label('Quick User Entry')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('success')
                ->modalHeading('Quick User Entry')
                ->modalDescription('Enter one user per line using the format: email followed by full name.')
                ->modalSubmitActionLabel('Save')
                ->modalCancelActionLabel('Cancel')
                ->schema([
                    Textarea::make('users')
                        ->label('User data')
                        ->placeholder("562024004@student.uksw.edu Andy Setya Novanto\n562024003@student.uksw.edu Bambang Tri\n562024010@student.uksw.edu Agus Hari Mukti")
                        ->helperText('Each new user will receive the user role and the default password 1.')
                        ->rows(12)
                        ->required(),
                ])
                ->action(function (array $data, Action $action): void {
                    try {
                        $result = app(QuickUserService::class)->create(
                            (string) $data['users'],
                            (int) Auth::id(),
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('Users were not saved')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $userLabel = $result['created'] === 1 ? 'user' : 'users';
                    $message = "{$result['created']} {$userLabel} added successfully.";

                    if ($result['skipped'] > 0) {
                        $skippedLabel = $result['skipped'] === 1 ? 'existing email was' : 'existing emails were';
                        $message .= " {$result['skipped']} {$skippedLabel} skipped.";
                    }

                    Notification::make()
                        ->title('Users saved')
                        ->body($message)
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
