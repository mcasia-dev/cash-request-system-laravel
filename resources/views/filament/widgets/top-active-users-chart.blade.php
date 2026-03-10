<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Top Active Users</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Users with the most submissions and approval actions.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('reports.top-active-users.print') }}"
                        target="_blank"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Print
                    </a>
                    <a
                        href="{{ route('reports.top-active-users.pdf') }}"
                        target="_blank"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Export PDF
                    </a>
                    <a
                        href="{{ route('reports.top-active-users.excel') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Export Excel
                    </a>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Most Submissions</h4>
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">Submissions</span>
                    </div>

                    @if ($topSubmitters->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No submission activity found.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($topSubmitters as $user)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0">
                                            <span class="block truncate font-medium text-gray-950 dark:text-white">{{ $user['name'] }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $user['position'] }}</span>
                                        </div>
                                        <span class="shrink-0 text-gray-500 dark:text-gray-400">{{ number_format($user['total']) }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div
                                            class="h-2.5 rounded-full bg-blue-600 transition-all"
                                            style="width: {{ $user['width'] }}%;"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Most Approvals</h4>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Approvals</span>
                    </div>

                    @if ($topApprovers->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No approval activity found.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($topApprovers as $user)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0">
                                            <span class="block truncate font-medium text-gray-950 dark:text-white">{{ $user['name'] }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $user['position'] }}</span>
                                        </div>
                                        <span class="shrink-0 text-gray-500 dark:text-gray-400">{{ number_format($user['total']) }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div
                                            class="h-2.5 rounded-full bg-emerald-600 transition-all"
                                            style="width: {{ $user['width'] }}%;"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
