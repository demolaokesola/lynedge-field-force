<?php

namespace App\Filament\Shared\Resources\Distributions\Actions;

use App\Exceptions\InsufficientStock;
use App\Models\Distribution;
use App\Services\DistributionPostingService;
use App\Services\StockSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * The "Post" action shared by the distribution edit page and the distributions table.
 * Posting is the submit stage: it freezes the record (DistributionPolicy::post/update/
 * delete all require status===Draft) and, once stock consumption is live, draws the
 * lines down from the position's balance via {@see DistributionPostingService}.
 *
 * Any line that would push a balance below zero is listed in the confirmation modal
 * up front, so the rep sees the shortfall before confirming. Whether that is a
 * warning or a refusal is decided by the Office "block negative stock" setting.
 */
class PostDistributionAction
{
    public static function make(): Action
    {
        return Action::make('post')
            ->label('Post')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->authorize('post')
            ->modalHeading('Post distribution')
            ->modalDescription(fn (Distribution $record): HtmlString => self::modalDescription($record))
            ->action(function (Distribution $record): void {
                try {
                    app(DistributionPostingService::class)->post($record, auth()->user());
                } catch (InsufficientStock $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Insufficient stock')
                        ->body(self::shortfallHtml($exception->shortfalls))
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Distribution posted')
                    ->send();
            });
    }

    private static function modalDescription(Distribution $record): HtmlString
    {
        $shortfalls = app(DistributionPostingService::class)->shortfalls($record);

        if ($shortfalls === []) {
            return new HtmlString('Posting freezes this distribution. It can no longer be edited or deleted.');
        }

        $lead = app(StockSettings::class)->blockNegativeStock()
            ? 'This distribution <strong>will be refused</strong> — the following lines exceed the stock on hand:'
            : 'Posting will take the following balances <strong>below zero</strong>:';

        return new HtmlString($lead.'<br><br>'.self::shortfallHtml($shortfalls));
    }

    /**
     * One escaped line per short product.
     *
     * @param  list<array{product: string, available: string, requested: string}>  $shortfalls
     */
    private static function shortfallHtml(array $shortfalls): HtmlString
    {
        return new HtmlString(
            collect($shortfalls)
                ->map(fn (array $line): string => e("{$line['product']}: have {$line['available']}, selling {$line['requested']}"))
                ->implode('<br>'),
        );
    }
}
