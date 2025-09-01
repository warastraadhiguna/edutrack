<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\Period;
use App\Models\Subject;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Select::make('subject_id')
                ->label('Subject')
                ->relationship('subject', 'name') // pakai relasi; HAPUS ->options()
                ->getOptionLabelFromRecordUsing(fn (Subject $r) => "{$r->code} - {$r->name}")
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    return Subject::query()
                        ->where(fn ($q) => $q
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => "{$s->code} - {$s->name}"])
                        ->toArray();
                })
                ->preload()   // optional: load opsi di awal kalau datanya kecil
                ->required(),
                Textarea::make("note")->label("Note"),
                Hidden::make('period_id')
                    ->default(Period::where('default', '1')->first()->id),
                Hidden::make('user_id')
                    ->default(Auth::id()),
            ]);
    }
}
