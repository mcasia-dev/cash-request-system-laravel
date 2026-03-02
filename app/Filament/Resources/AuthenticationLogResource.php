<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource as BaseAuthenticationLogResource;

class AuthenticationLogResource extends BaseAuthenticationLogResource
{

    protected static ?string $navigationGroup = 'Administrator';
    
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isSuperAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('authenticatable')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.authenticatable'))
                    ->formatStateUsing(function (?string $state, Model $record) {
                        if (!$record->authenticatable_id) {
                            return new HtmlString('&mdash;');
                        }

                        $authenticatable = $record->authenticatable;

                        if (!$authenticatable) {
                            return 'Unknown';
                        }

                        if ($authenticatable instanceof \App\Models\User) {
                            return $authenticatable->name ?? trim(($authenticatable->first_name ?? '') . ' ' . ($authenticatable->last_name ?? ''));
                        }

                        return method_exists($authenticatable, 'getAttribute')
                            ? ($authenticatable->getAttribute('name') ?? class_basename($authenticatable))
                            : class_basename($authenticatable);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.ip_address'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.user_agent'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('login_at')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.login_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('login_successful')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.login_successful'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('logout_at')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.logout_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('cleared_by_user')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.cleared_by_user'))
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                //
            ])
            ->filters([
                Filter::make('login_successful')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('login_successful', true)),
                Filter::make('login_at')
                    ->form([
                        DatePicker::make('login_from'),
                        DatePicker::make('login_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['login_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('login_at', '>=', $date),
                            )
                            ->when(
                                $data['login_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('login_at', '<=', $date),
                            );
                    }),
                Filter::make('cleared_by_user')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('cleared_by_user', true)),
            ]);
    }
}
