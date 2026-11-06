<?php

namespace App\Filament\Widgets\Campaign;

use App\Models\Campaign\SeckillActivity;
use App\Models\Campaign\SeckillSession;
use Filament\Widgets\ChartWidget;

class SeckillActivitySessionsChart extends ChartWidget
{
    public ?SeckillActivity $record = null;

    protected ?string $maxHeight = '123px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $sessions = $this->record?->sessions()
            ->orderBy('start_at')
            ->get() ?? collect();

        return [
            'labels' => $sessions->pluck('name')->all(),
            'datasets' => [
                [
                    'label' => '剩余名额',
                    'data' => $sessions->map(fn (SeckillSession $session): int => $session->remaining())->all(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, .2)',
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
