<?php
namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Filament\Resources\CashRequestResource;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use JaOcero\ActivityTimeline\Pages\ActivityTimelinePage;

class TrackRequestStatus extends ActivityTimelinePage
{

    protected static string $resource = CashRequestResource::class;

    protected function configuration(): array
    {
        return [
            'activity_section'     => [
                'label'                   => 'Track Status',
                'description'             => 'These are the activities that have been recorded.',
                'show_items_count'        => 0,
                'show_items_label'        => 'Show more',
                'show_items_icon'         => 'heroicon-o-chevron-down',
                'show_items_color'        => 'gray',
                'aside'                   => true,
                'empty_state_heading'     => 'No activities yet',
                'empty_state_description' => 'Check back later for activities that have been recorded.',
                'empty_state_icon'        => 'heroicon-o-bolt-slash',
                'heading_visible'         => true,
                'extra_attributes'        => [],
            ],
            'activity_title'       => [
                'placeholder'  => 'Activity',
                'allow_html'   => true,
                'modify_state' => function ($record) {
                    if (! $record) {
                        return new HtmlString('No activity data.');
                    }

                    if ($record['description'] == $record['event']) {
                        $className  = Str::lower(Str::snake(class_basename($record['subject'] ?? \App\Models\CashRequest::class), ' '));
                        $causerName = $record['causer']->name ?? $record['causer']->first_name ?? $record['causer']->last_name ?? $record['causer']->username ?? 'Unknown';

                        return new HtmlString(sprintf('The <strong>%s</strong> was <strong>%s</strong> by <strong>%s</strong>.', $className, $record['event'], $causerName));
                    }

                    return new HtmlString($record['description']);
                },
            ],
            'activity_description' => [
                'placeholder'  => 'No description is set',
                'allow_html'   => true,
                'modify_state' => function ($record) {
                    if (!$record || empty($record['properties'])) {
                        return new HtmlString('');
                    }

                    $properties = $record['properties'];

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

                    return new HtmlString('Hello there');
                },
            ],
            'activity_date' => [
                'name'         => 'created_at',
                'date'         => 'F j, Y g:i A',
                'placeholder'  => 'No date is set',
                'modify_state' => function ($state) {
                    return new HtmlString($state ?? '');
                },
            ],
            'activity_icon' => [
                'icon'  => function ($record) {
                    return match ($record->event) {
                        'created'    => 'heroicon-o-plus-circle',
                        'updated'    => 'heroicon-o-pencil',
                        'deleted'    => 'heroicon-o-trash',
                        'approved'   => 'heroicon-o-check-circle',
                        'rejected'   => 'heroicon-o-x-circle',
                        'released'   => 'heroicon-o-currency-dollar',
                        'liquidated' => 'heroicon-o-arrow-path',
                        default      => 'heroicon-o-information-circle',
                    };
                },
                'color' => function ($record) {
                    return match ($record->event) {
                        'created'    => 'success',
                        'updated'    => 'info',
                        'deleted'    => 'danger',
                        'approved'   => 'success',
                        'rejected'   => 'danger',
                        'released'   => 'primary',
                        'liquidated' => 'warning',
                        default      => 'gray',
                    };
                },
            ],
        ];
    }
}
