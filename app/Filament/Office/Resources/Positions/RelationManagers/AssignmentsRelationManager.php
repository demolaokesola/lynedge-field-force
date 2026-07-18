<?php

namespace App\Filament\Office\Resources\Positions\RelationManagers;

use App\Enums\AssignmentStatus;
use App\Models\PositionAssignment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Assignments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Rep')
                    ->relationship('user', 'name', fn (Builder $query, ?PositionAssignment $record) => $query
                        ->role('sales_rep')
                        ->whereDoesntHave(
                            'activePositionAssignment',
                            fn (Builder $q) => $q->when(
                                $record?->exists,
                                fn (Builder $q) => $q->whereKeyNot($record->getKey()),
                            ),
                        ))
                    ->required()
                    ->searchable()
                    ->preload(),
                DatePicker::make('effective_from')
                    ->required()
                    ->default(today()),
                DatePicker::make('effective_to')
                    ->label('Effective to (set to end this assignment)')
                    ->helperText('Leave blank while the rep occupies this position.'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Rep')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->date()
                    ->placeholder('— open')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('effective_from', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => self::deriveStatus($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => self::deriveStatus($data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * An assignment is active while open (no effective_to), ended once it is set.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function deriveStatus(array $data): array
    {
        $data['status'] = blank($data['effective_to'] ?? null)
            ? AssignmentStatus::Active->value
            : AssignmentStatus::Ended->value;

        return $data;
    }
}
