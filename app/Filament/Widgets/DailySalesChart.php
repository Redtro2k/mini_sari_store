<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use JCCoca\FilamentChartPalette\Traits\HasChartPalette;
use Livewire\Attributes\Locked;

class DailySalesChart extends ChartWidget
{
    use HasChartPalette;

    protected ?string $heading = 'Daily revenue and gross profit';

    protected ?string $description = 'Amounts in PHP · Philippine dates · Gaps indicate unavailable historical costs.';

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '350px';

    /** @var array<string, array{revenue: int, cost: int, transactions: int, units: int, missing: int}> */
    #[Locked]
    public array $daily = [];

    protected ?array $options = [
        'scales' => ['y' => ['title' => ['display' => true, 'text' => 'PHP']]],
        'interaction' => ['mode' => 'index', 'intersect' => false],
    ];

    protected function getData(): array
    {
        $daily = $this->daily;
        ksort($daily);
        $palette = $this->getChartPalette('line', 500);

        return [
            'labels' => array_keys($daily),
            'datasets' => [
                [
                    'label' => 'Sales revenue',
                    'data' => array_values(array_map(fn (array $row): float => $row['revenue'] / 100, $daily)),
                    'borderColor' => $palette[0],
                    'backgroundColor' => $palette[0],
                    'pointRadius' => 2,
                ],
                [
                    'label' => 'Gross profit',
                    'data' => array_values(array_map(fn (array $row): ?float => $row['missing'] ? null : ($row['revenue'] - $row['cost']) / 100, $daily)),
                    'borderColor' => $palette[1],
                    'backgroundColor' => $palette[1],
                    'pointRadius' => 2,
                    'spanGaps' => false,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
