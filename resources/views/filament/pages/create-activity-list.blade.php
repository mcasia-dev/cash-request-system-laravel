<x-filament-panels::page>
    <form wire:submit.prevent="create" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="create">
            <span wire:loading.remove wire:target="create">
                Create Activity
            </span>

            <span wire:loading wire:target="create">Saving...</span>
        </x-filament::button>
    </form>



    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
