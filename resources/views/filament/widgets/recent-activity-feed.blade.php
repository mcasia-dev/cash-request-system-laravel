<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-5">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Recent Activity</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Latest submissions, approvals, rejections, and reimbursement processing activity.</p>
            </div>

            @if ($activities->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No recent audit activity found.
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($activities as $activity)
                        @php
                            $accentClasses = match ($activity['tone']) {
                                'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
                                'danger' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300',
                                'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
                                default => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300',
                            };
                        @endphp

                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="mt-1 h-3 w-3 rounded-full bg-gray-900 dark:bg-white"></div>
                                @if (! $loop->last)
                                    <div class="mt-2 h-full min-h-10 w-px bg-gray-200 dark:bg-gray-700"></div>
                                @endif
                            </div>

                            <div class="flex-1 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $accentClasses }}">
                                        {{ $activity['label'] }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $activity['created_at_human'] }}</span>
                                </div>

                                <div class="mt-3 space-y-1">
                                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $activity['request_no'] }} · {{ $activity['activity_name'] }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $activity['causer_name'] }} recorded this activity.
                                    </p>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $activity['created_at_full'] }}</span>
                                    @if (filled($activity['description']))
                                        <span class="truncate">{{ $activity['description'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
