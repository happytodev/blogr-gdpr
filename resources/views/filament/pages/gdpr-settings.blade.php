<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6">
            {{ $this->form }}
        </div>

        <div class="flex justify-end pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
            <x-filament::button type="submit" color="primary">
                Save
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold mb-4">GDPR Compliance Plugin</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Version {{ app(\Happytodev\BlogrGdpr\BlogrGdprPlugin::class)->getVersion() }}
            &mdash; Powered by <strong>{{ app(\Happytodev\BlogrGdpr\BlogrGdprPlugin::class)->getAuthor() }}</strong>
        </p>
    </div>
</x-filament-panels::page>
