<div class="flex flex-wrap items-start gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
    <div class="shrink-0">
        <x-filament::icon icon="heroicon-o-beaker" class="h-6 w-6 text-primary-500" />
    </div>

    <div class="min-w-0 flex-1 space-y-2">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="font-semibold text-gray-900 dark:text-white">Loopback App</span>
            <x-filament::badge color="success">Always active</x-filament::badge>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            A built-in app used by the Diagnostics page to test your Reverb connection end-to-end. It is not stored in the database — credentials are derived from <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/5">APP_KEY</code> and take effect after you restart Reverb.
        </p>

        <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
            <span>App ID: <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">{{ $appId }}</code></span>
            <span>Key: <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">{{ $key }}</code></span>
        </div>
    </div>
</div>