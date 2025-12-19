<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StockFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Stock Flow (30d)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $end = now()->endOfDay();
        $start = now()->subDays(29)->startOfDay();

        $cacheKey = 'dashboard:stock-flow:' . $start->toDateString() . ':' . $end->toDateString();

        return Cache::remember(
            $cacheKey . ':' . now()->format('H:i'),
            now()->addSeconds(45),
            function () use ($start, $end) {
                $purchaseQtyByDate = DB::table('purchase_details')
                    ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                    ->whereBetween('purchases.created_at', [$start, $end])
                    ->selectRaw('DATE(purchases.created_at) as date, SUM(purchase_details.quantity) as qty')
                    ->groupBy('date')
                    ->pluck('qty', 'date');

                $saleQtyByDate = DB::table('sale_details')
                    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.sale_date', [$start, $end])
                    ->selectRaw('DATE(sales.sale_date) as date, SUM(sale_details.quantity) as qty')
                    ->groupBy('date')
                    ->pluck('qty', 'date');

                $labels = [];
                $purchaseSeries = [];
                $saleSeries = [];

                for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                    $date = $cursor->toDateString();
                    $labels[] = $cursor->format('d M');
                    $purchaseSeries[] = (int) ($purchaseQtyByDate[$date] ?? 0);
                    $saleSeries[] = (int) ($saleQtyByDate[$date] ?? 0);
                }

                return [
                    'datasets' => [
                        [
                            'label' => 'Purchases (qty)',
                            'data' => $purchaseSeries,
                            'borderColor' => '#0ea5e9',
                            'backgroundColor' => 'rgba(14,165,233,0.2)',
                            'tension' => 0.3,
                            'fill' => true,
                        ],
                        [
                            'label' => 'Sales (qty)',
                            'data' => $saleSeries,
                            'borderColor' => '#f97316',
                            'backgroundColor' => 'rgba(249,115,22,0.2)',
                            'tension' => 0.3,
                            'fill' => true,
                        ],
                    ],
                    'labels' => $labels,
                ];
            }
        );
    }

    protected function getType(): string
    {
        return 'line';
    }
}
