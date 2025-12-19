<?php

namespace App\Filament\Admin\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PurchasePipelineStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $data = Cache::remember(
            'dashboard:purchase-pipeline:' . $today . ':' . now()->format('H:i'),
            now()->addSeconds(30),
            function () use ($today) {
                $pendingPr = (int) DB::table('purchase_requests')
                    ->where('status', 'PENDING')
                    ->count();

                $approvedPrToday = (int) DB::table('purchase_requests')
                    ->where('status', 'APPROVED')
                    ->whereDate('handled_at', $today)
                    ->count();

                $poInProgress = (int) DB::table('purchase_orders')
                    ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                    ->count();

                $poCompletedToday = (int) DB::table('purchase_orders')
                    ->where('status', 'COMPLETED')
                    ->whereDate('updated_at', $today)
                    ->count();

                return [
                    'pending_pr' => $pendingPr,
                    'approved_pr_today' => $approvedPrToday,
                    'po_in_progress' => $poInProgress,
                    'po_completed_today' => $poCompletedToday,
                ];
            }
        );

        return [
            Stat::make('Pending PRs', number_format($data['pending_pr']))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color($data['pending_pr'] > 0 ? 'warning' : 'success'),

            Stat::make('PRs Approved Today', number_format($data['approved_pr_today']))
                ->description('New approvals today')
                ->descriptionIcon('heroicon-m-check-badge', IconPosition::Before)
                ->color('success'),

            Stat::make('POs In Progress', number_format($data['po_in_progress']))
                ->description('Not completed/cancelled')
                ->descriptionIcon('heroicon-m-clipboard-document-check', IconPosition::Before)
                ->color($data['po_in_progress'] > 0 ? 'primary' : 'success'),

            Stat::make('POs Completed Today', number_format($data['po_completed_today']))
                ->description('Marked completed today')
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success'),
        ];
    }
}
