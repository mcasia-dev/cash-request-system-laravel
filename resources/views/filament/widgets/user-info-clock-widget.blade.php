@php($user = auth()->user())
@php($name = $user?->name ?? 'N/A')
@php($profilePhoto = $user?->getFirstMediaUrl('profile'))
@php($initials = collect(explode(' ', trim($name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode(''))

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900/90">
            <div class="mb-4 flex items-center gap-4">
                <div
                    class="relative shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-700"
                    style="width: 56px; height: 56px;"
                >
                    @if (filled($profilePhoto))
                        <img
                            src="{{ $profilePhoto }}"
                            alt="{{ $name }}"
                            class="h-full w-full object-cover object-center"
                            style="width: 56px; height: 56px;"
                        />
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-sm font-bold text-slate-700 dark:text-slate-100">
                        {{ $initials ?: 'NA' }}
                        </div>
                    @endif
                </div>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $name }}</h2>
            </div>

            <div class="mt-10 grid gap-2 sm:grid-cols-2">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Position:
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $user?->position ?? 'N/A' }}</span>
                </p>

                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Control No:
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $user?->control_no ?? 'N/A' }}</span>
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
