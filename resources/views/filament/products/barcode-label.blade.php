@php
    $bars = (new \Milon\Barcode\DNS1D())->getBarcodeSVG($product->barcode, 'C128', 3, 100, 'black', false, true);
    preg_match('/width="([0-9.]+)"/', $bars, $dimensions);
    $width = (float) $dimensions[1] + 80;
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $width }}" height="180" viewBox="0 0 {{ $width }} 180">
    <rect width="100%" height="100%" fill="white" />
    <g transform="translate(40, 20)">{!! $bars !!}</g>
    <text x="{{ $width / 2 }}" y="145" text-anchor="middle" fill="black" font-family="monospace" font-size="18">{{ $product->barcode }}</text>
    <text x="{{ $width / 2 }}" y="168" text-anchor="middle" fill="black" font-family="sans-serif" font-size="14">{{ \Illuminate\Support\Str::limit($product->name, 35) }}</text>
</svg>
