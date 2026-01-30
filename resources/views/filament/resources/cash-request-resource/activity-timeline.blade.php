<div class="p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Activity Timeline - {{ $record->activity_name }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Cash Request #{{ $record->id }}
        </p>
    </div>

    @if(isset($record->activities) && $record->activities->count() > 0)
        <div class="relative border-l border-gray-200 dark:border-gray-700">
            @foreach($record->activities as $activity)
                <div class="mb-10 ml-6">
                    <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                        <svg class="w-2.5 h-2.5 text-blue-800 dark:text-blue-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M20 10a10 10 0 1 1-20 0 10 10 0 0 1 20 0z"/>
                        </svg>
                    </span>
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center mb-1">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $activity->description ?? 'Activity' }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-auto">
                                {{ $activity->created_at->format('M d, Y h:i A') }}
                            </span>
                        </div>
                        @if(isset($activity->properties))
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                @if(is_array($activity->properties))
                                    @foreach($activity->properties as $key => $value)
                                        <div class="mt-2">
                                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            @if(is_array($value))
                                                {{ json_encode($value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No activity timeline records found.</p>
            <p class="text-sm text-gray-400 mt-2">Activity records will appear here as changes are made to this cash request.</p>
        </div>
    @endif
</div>
