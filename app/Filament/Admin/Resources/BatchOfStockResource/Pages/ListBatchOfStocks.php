<?php

namespace App\Filament\Admin\Resources\BatchOfStockResource\Pages;

use App\Filament\Admin\Resources\BatchOfStockResource;
use App\Models\BatchOfStock;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListBatchOfStocks extends ListRecords
{
    protected static string $resource = BatchOfStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('download_template')
                ->label('Download Import Template')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $headings = [
                        'product_code',
                        'batch_no',
                        'expiry_date (YYYY-MM-DD)',
                        'quantity',
                    ];

                    $rows = [[
                        'BEV00001',
                        '',
                        now()->addMonths(6)->toDateString(),
                        '100',
                    ]];

                    $export = new class($rows, $headings) implements FromArray, WithHeadings {
                        public function __construct(private array $rows, private array $headings)
                        {
                        }
                        public function array(): array
                        {
                            return $this->rows;
                        }
                        public function headings(): array
                        {
                            return $this->headings;
                        }
                    };

                    return Excel::download($export, 'batch-of-stocks-import-template.xlsx');
                }),
            Actions\Action::make('import_batches')
                ->label('Import Batch of Stocks')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV / XLSX')
                        ->required()
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->directory('imports')
                        ->preserveFilenames(),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/' . $data['file']);
                    $rows = SimpleExcelReader::create($path)->getRows();
                    $errors = [];
                    $imported = 0;

                    DB::transaction(function () use ($rows, &$errors, &$imported) {
                        foreach ($rows as $index => $row) {
                            $rowNumber = $index + 2; // header row assumed
                            $productCode = trim((string) ($row['product_code'] ?? ''));
                            $expiryDate = trim((string) ($row['expiry_date'] ?? $row['expiry_date (YYYY-MM-DD)'] ?? ''));
                            $quantity = (int) ($row['quantity'] ?? 0);
                            $batchNo = trim((string) ($row['batch_no'] ?? ''));

                            if ($productCode === '') {
                                $errors[] = "Row {$rowNumber}: product_code is required.";
                                continue;
                            }
                            if ($expiryDate === '') {
                                $errors[] = "Row {$rowNumber}: expiry_date is required.";
                                continue;
                            }
                            if ($quantity <= 0) {
                                $errors[] = "Row {$rowNumber}: quantity must be > 0.";
                                continue;
                            }

                            $product = Product::where('product_code', $productCode)->first();
                            if (! $product) {
                                $errors[] = "Row {$rowNumber}: product_code {$productCode} not found.";
                                continue;
                            }

                            try {
                                BatchOfStock::create([
                                    'product_id' => $product->id,
                                    'batch_no' => $batchNo ?: null,
                                    'expiry_date' => $expiryDate,
                                    'quantity' => $quantity,
                                ]);
                                $imported++;
                            } catch (\Throwable $e) {
                                $errors[] = "Row {$rowNumber} ({$productCode}): " . $e->getMessage();
                            }
                        }
                    });

                    $message = "Imported {$imported} batch rows.";
                    if (! empty($errors)) {
                        $message .= ' Some rows failed: ' . implode(' | ', $errors);
                    }

                    $this->notify('success', $message);
                }),
        ];
    }
}
