@php
    $barcode = $getRecord()->barcode;
    $barcodeImage = filled($barcode) ? (new Milon\Barcode\DNS1D())->getBarcodePNG($barcode, 'C128', 2, 80) : null;
@endphp

@if ($barcodeImage)
    <div class="flex w-max flex-col items-center gap-2 rounded-lg bg-white px-8 py-4">
        <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode {{ $barcode }}" class="h-20 w-auto max-w-none">
        <span class="font-mono text-sm text-gray-900">{{ $barcode }}</span>
    </div>
@else
    <span class="text-sm text-gray-500">Not generated</span>
@endif
