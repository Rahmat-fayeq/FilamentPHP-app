<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProductChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        $data = $this->getProductsPerMonth();
        return [
            'datasets' => [
                [
                    'label' => 'Products created',
                    'data' => $data['productsPerMonth'],
                ]
            ],
            'labels' => $data['months'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getProductsPerMonth(): array
    {
        $now = Carbon::now();

        $months = collect(range(1, 12))->map(function ($month) use ($now) {
            return $now->month($month)->format('M');
        })->toArray();
        $productsPerMonth = Product::get()
            ->groupBy(function (Product $user) {
                return $user->created_at->month;
            })
            ->map(function ($users) {
                return $users->count();
            })
            ->union(array_fill(0, 12, 0))
            ->sortKeys()
            ->toArray();

        return [
            'productsPerMonth' => $productsPerMonth,
            'months' => $months
        ];
    }
}
