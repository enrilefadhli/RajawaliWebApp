<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use App\Models\SystemSetting;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryKpiOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $settings = SystemSetting::first();
        $thresholdEnabled = (bool) ($settings?->expiry_threshold_enabled ?? config('inventory.expiry_threshold_enabled', true));
        $nearExpiryDays = (int) ($settings?->expiry_threshold_days ?? config('inventory.expiry_threshold_days', 30));
        $nearExpiryDays = max(1, min(365, $nearExpiryDays));
        $nearExpiryDaysForKpi = $thresholdEnabled ? $nearExpiryDays : 0;
        $nearExpiryDate = now()->addDays($nearExpiryDaysForKpi)->toDateString();

        $kpis = Cache::remember(
            'dashboard:kpi:' . $today . ':' . now()->format('H:i'),
            now()->addSeconds(30),
            function () use ($today, $nearExpiryDate) {
                $salesToday = DB::table('sales')
                    ->whereDate('sale_date', $today)
                    ->selectRaw('COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count')
                    ->first();

                $itemsSoldToday = (int) DB::table('sale_details')
                    ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                    ->whereDate('sales.sale_date', $today)
                    ->sum('sale_details.quantity');

                $lowStockProductIds = Product::query()
                    ->where('products.status', 'ACTIVE')
                    ->leftJoin('batch_of_stocks as b', function ($join) use ($today) {
                        $join->on('b.product_id', '=', 'products.id')
                            ->where('b.quantity', '>', 0)
                            ->where(function ($q) use ($today) {
                                $q->whereNull('b.expiry_date')->orWhere('b.expiry_date', '>=', $today);
                            });
                    })
                    ->groupBy('products.id', 'products.minimum_stock')
                    ->havingRaw('COALESCE(SUM(b.quantity), 0) < products.minimum_stock')
                    ->select('products.id');

                $lowStockCount = DB::query()->fromSub($lowStockProductIds, 't')->count();

                $expiredBatches = DB::table('batch_of_stocks')
                    ->where('quantity', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<', $today)
                    ->count();

                $nearExpiryBatches = DB::table('batch_of_stocks')
                    ->where('quantity', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', $today)
                    ->whereDate('expiry_date', '<=', $nearExpiryDate)
                    ->count();

                return [
                    'sales_total' => (float) ($salesToday->total ?? 0),
                    'sales_count' => (int) ($salesToday->count ?? 0),
                    'items_sold' => $itemsSoldToday,
                    'low_stock_count' => $lowStockCount,
                    'expired_batches' => $expiredBatches,
                    'near_expiry_batches' => $nearExpiryBatches,
                ];
            }
        );

        $expiryStatusLabel = match (true) {
            $kpis['expired_batches'] > 0 => $kpis['expired_batches'] . ' expired',
            $kpis['near_expiry_batches'] > 0 => $kpis['near_expiry_batches'] . ' near expiry',
            default => 'All good',
        };

        return [
            Stat::make('Sales Today', $this->formatIdr($kpis['sales_total']))
                ->description($kpis['sales_count'] . ' transactions')
                ->descriptionIcon('heroicon-m-shopping-bag', IconPosition::Before)
                ->color('primary'),

            Stat::make('Items Sold Today', number_format((int) $kpis['items_sold']))
                ->description('Total quantity sold')
                ->descriptionIcon('heroicon-m-cube', IconPosition::Before)
                ->color('primary'),

            Stat::make('Low Stock Products', number_format((int) $kpis['low_stock_count']))
                ->description('ACTIVE products below min stock')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color($kpis['low_stock_count'] > 0 ? 'danger' : 'success'),

            Stat::make('Expiry Watch', $expiryStatusLabel)
                ->description('Expired: ' . (int) $kpis['expired_batches'] . ' | <=' . $nearExpiryDaysForKpi . 'd: ' . (int) $kpis['near_expiry_batches'])
                ->descriptionIcon('heroicon-m-calendar-days', IconPosition::Before)
                ->color(($kpis['expired_batches'] > 0) ? 'danger' : (($kpis['near_expiry_batches'] > 0) ? 'warning' : 'success')),
        ];
    }

    protected function formatIdr(float $amount): string
    {
        return 'IDR ' . number_format((float) $amount, 0, ',', '.');
    }
}
