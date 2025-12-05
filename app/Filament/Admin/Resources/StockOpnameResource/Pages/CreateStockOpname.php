<?php

namespace App\Filament\Admin\Resources\StockOpnameResource\Pages;

use App\Filament\Admin\Resources\StockOpnameResource;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->applyAdjustmentsFromOpname();
    }

    /**
     * For each batch with a difference, create a stock_adjustment + item and
     * update batch quantity via StockAdjustmentItem model hooks.
     */
    private function applyAdjustmentsFromOpname(): void
    {
        DB::transaction(function () {
            $opname = $this->record->fresh(['items']);
            $items = $opname->items->filter(fn ($item) => (int) $item->difference_qty !== 0);

            if ($items->isEmpty()) {
                return;
            }

            $adjustment = StockAdjustment::create([
                'adjustment_date' => $opname->opname_date ?? now(),
                'reason' => 'OPNAME',
                'created_by' => $opname->created_by,
                'notes' => $opname->notes,
            ]);

            foreach ($items as $item) {
                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'batch_of_stock_id' => $item->batch_of_stock_id,
                    'product_id' => $item->product_id,
                    'qty_change' => (int) $item->difference_qty,
                    'notes' => $item->notes,
                ]);
            }
        });
    }
}
