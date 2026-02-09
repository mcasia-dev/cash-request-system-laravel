<?php
namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\CashRequestResource;
use Illuminate\Support\HtmlString;
use JaOcero\ActivityTimeline\Pages\ActivityTimelinePage;

class TrackRequestStatus extends ActivityTimelinePage
{

    protected static string $resource = CashRequestResource::class;

    /**
     * Build the activity timeline configuration used by the tracker view.
     *
     * Defines labels, empty states, formatting callbacks, and icon/color
     * mapping for activity events.
     *
     * @return array<string, mixed>
     */
    protected function configuration(): array
    {
        return [
            'activity_section'     => [
                'label'                   => 'Track Status',
                'description'             => 'These are the activities that have been recorded.',
                'show_items_count'        => 5,
                'show_items_label'        => 'Show more',
                'show_items_icon'         => 'heroicon-o-chevron-down',
                'show_items_color'        => 'gray',
                'aside'                   => false,
                'empty_state_heading'     => 'No activities yet',
                'empty_state_description' => 'Check back later for activities that have been recorded.',
                'empty_state_icon'        => 'heroicon-o-bolt-slash',
                'heading_visible'         => false,
                'extra_attributes'        => [
                    'class' => 'my-5',
                ],
            ],
            'activity_title'       => [
                'placeholder'  => 'Activity',
                'allow_html'   => true,
                'modify_state' => function ($record) {
                    if (! $record) {
                        return new HtmlString('No activity data.');
                    }

                    // return new HtmlString(sprintf('The <strong>%s</strong> was <strong>%s</strong> by <strong>%s</strong>.', $className, $record['event'], $causerName));
                    $status_remarks = $record['properties']['status_remarks'] ?? 'No Activity';
                    return new HtmlString(sprintf('<strong> ' . ucfirst($status_remarks) . ' </strong>'));
                },
            ],
            'activity_description' => [
                'placeholder'  => 'No description is set',
                'allow_html'   => true,
                'modify_state' => function ($record) {
                    if (! $record || empty($record['properties'])) {
                        return new HtmlString('');
                    }

                    $properties = $record['properties'];
                    $date       = $record->created_at
                        ? $record->created_at->format('F j, Y h:i A')
                        : '';

                    if (isset($properties['old']) && isset($properties['attributes'])) {
                        $oldValues = $properties['old'];
                        $newValues = $properties['attributes'];
                        $changes   = [];

                        foreach ($newValues as $key => $newValue) {
                            $oldValue     = $oldValues[$key] ?? null;
                            $oldFormatted = $oldValue === null ? '—' : (is_array($oldValue) ? json_encode($oldValue) : $oldValue);
                            $newFormatted = is_array($newValue) ? json_encode($newValue) : $newValue;

                            if ($oldValue != $newValue) {
                                $changes[] = "- {$key} from <strong>" . htmlspecialchars($oldFormatted) . '</strong> to <strong>' . htmlspecialchars($newFormatted) . '</strong>';
                            }
                        }

                        if (! empty($changes)) {
                            $causerName = $record['causer']->name ?? $record['causer']->first_name ?? $record['causer']->last_name ?? $record['causer']->username ?? 'Unknown';
                            return new HtmlString(sprintf('%s %s the following: <br>%s', $causerName, $record['event'], implode('<br>', $changes)));
                        }
                    }

                    return new HtmlString(
                        "<span class='text-sm text-gray-500 block mb-1'>{$date}</span>"
                        . $record['description']
                    );
                },
            ],
            'activity_date' => [
                'name'         => 'created_at',
                'date'         => 'F d, Y h:i A',
                'placeholder'  => 'No date is set',
                'modify_state' => fn($state) => new HtmlString($state ?? ''),
            ],
            'activity_icon' => [
                'icon'  => fn($record): string  => match ($record->event) {
                    'created'                 => 'heroicon-o-plus-circle',
                    Status::APPROVED->value   => 'heroicon-o-check-circle',
                    Status::REJECTED->value   => 'heroicon-o-x-mark',
                    Status::CANCELLED->value  => 'heroicon-o-x-circle',
                    Status::RELEASED->value   => 'heroicon-o-currency-dollar',
                    Status::LIQUIDATED->value => 'heroicon-o-arrow-path',
                    default                   => 'heroicon-o-information-circle',
                },
                'color' => fn($record): string => match ($record->event) {
                    'created'                 => 'warning',
                    Status::APPROVED->value   => 'success',
                    Status::REJECTED->value   => 'danger',
                    Status::CANCELLED->value  => 'danger',
                    Status::RELEASED->value   => 'info',
                    Status::LIQUIDATED->value => 'warning',
                    default                   => 'gray',
                },
            ],
        ];
    }
}
