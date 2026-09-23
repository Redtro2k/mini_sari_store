<div
    wire:ignore
    x-data="{ scanner: null, loadError: false, destroy() { this.scanner?.destroy() } }"
    x-init="import(@js(\Illuminate\Support\Facades\Vite::asset('resources/js/sale-camera.js'))).then(module => { scanner = module.createScanner($refs.video, code => $wire.addScannedProduct(code)) }).catch(() => { loadError = true })"
    x-on:livewire:navigating.window="scanner?.stop()"
    x-on:pagehide.window="scanner?.stop()"
    x-on:visibilitychange.document="if (document.hidden) scanner?.stop()"
    class="space-y-3"
>
    <x-filament::button type="button" icon="heroicon-o-camera" x-on:click="scanner.start()" x-bind:disabled="!scanner || scanner.starting || scanner.busy || scanner.running">
        Scan with camera
    </x-filament::button>
    <p x-show="loadError" x-cloak role="alert" class="text-sm text-danger-600">Unable to load the camera scanner. Refresh this page or enter the barcode below.</p>
    <div x-show="scanner?.open" x-cloak class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-300">Point the camera at the product barcode. One scan adds one item.</p>
            <x-filament::button type="button" color="gray" x-on:click="scanner.stop()">Close camera</x-filament::button>
        </div>
        <div class="relative max-w-xl">
            <video x-ref="video" autoplay muted playsinline class="aspect-video w-full rounded-lg bg-black object-contain"></video>
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-[10%] inset-y-[30%] rounded-lg border-2 border-amber-400"></div>
        </div>
        <label x-show="scanner?.cameras.length > 1" class="block text-sm font-medium">
            Camera
            <select x-on:change="scanner.switchCamera($event.target.value)" x-bind:disabled="scanner?.starting || scanner?.busy" class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <template x-for="camera in scanner?.cameras ?? []" x-bind:key="camera.deviceId">
                    <option x-bind:value="camera.deviceId" x-bind:selected="camera.deviceId === scanner.deviceId" x-text="camera.label || 'Camera'"></option>
                </template>
            </select>
        </label>
        <p role="status" aria-live="polite" class="text-sm text-gray-600 dark:text-gray-300" x-text="scanner?.message"></p>
        <p x-show="scanner?.error" role="alert" class="text-sm text-danger-600" x-text="scanner?.error"></p>
        <x-filament::button type="button" x-show="scanner && !scanner.running && !scanner.starting && !scanner.busy" x-on:click="scanner.start()">Scan next item</x-filament::button>
    </div>
</div>
