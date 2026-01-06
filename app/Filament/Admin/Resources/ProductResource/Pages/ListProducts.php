<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('download_template')
                ->label('Download Import Template')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $headings = [
                        'category_code',
                        'product_code',
                        'product_name',
                        'variant',
                        'sku',
                        'purchase_price',
                        'selling_price',
                        'discount_percent',
                        'discount_amount',
                        'minimum_stock',
                        'status',
                    ];

                    $rows = [[
                        'BEV',
                        '',
                        'Sample Product',
                        '500ml',
                        'SKU-001',
                        '10000',
                        '12000',
                        '',
                        '',
                        '10',
                        'ACTIVE',
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

                    return Excel::download($export, 'products-import-template.xlsx');
                }),
            Actions\Action::make('import_products')
                ->label('Import Products')
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
                        ->disk('local')
                        ->directory('imports')
                        ->preserveFilenames(),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);

                    $rows = SimpleExcelReader::create($path)->getRows();
                    $errors = [];
                    $imported = 0;

                    DB::transaction(function () use ($rows, &$errors, &$imported) {
                        foreach ($rows as $index => $row) {
                            $rowNumber = $index + 2; // header assumed row 1
                            $categoryCode = trim((string) ($row['category_code'] ?? ''));
                            $productName = trim((string) ($row['product_name'] ?? ''));
                            if ($categoryCode === '' || $productName === '') {
                                $errors[] = "Row {$rowNumber}: category_code and product_name are required.";
                                continue;
                            }

                            $category = Category::firstOrCreate(
                                ['category_code' => $categoryCode],
                                ['category_name' => $categoryCode]
                            );

                            $productCode = trim((string) ($row['product_code'] ?? ''));
                            $status = strtoupper(trim((string) ($row['status'] ?? 'DISABLED')));
                            $purchasePrice = (float) ($row['purchase_price'] ?? 0);
                            $sellingPrice = (float) ($row['selling_price'] ?? 0);
                            $minStock = (int) ($row['minimum_stock'] ?? 0);

                            if ($productCode === '') {
                                $productCode = ProductResource::generateProductCodeByCategory($category->id);
                            }

                            $product = Product::firstOrNew(['product_code' => $productCode]);
                            $product->fill([
                                'category_id' => $category->id,
                                'product_code' => $productCode,
                                'sku' => $row['sku'] ?? null,
                                'product_name' => $productName,
                                'variant' => $row['variant'] ?? null,
                                'purchase_price' => $purchasePrice,
                                'selling_price' => $sellingPrice,
                                'discount_percent' => $row['discount_percent'] ?? null,
                                'discount_amount' => $row['discount_amount'] ?? null,
                                'minimum_stock' => $minStock,
                                'status' => $status,
                            ]);

                            try {
                                $product->save();
                                $imported++;
                            } catch (\Throwable $e) {
                                $errors[] = "Row {$rowNumber} ({$productCode}): " . $e->getMessage();
                            }
                        }
                    });

                    $message = "Imported {$imported} products.";
                    if (! empty($errors)) {
                        $message .= ' Some rows failed: ' . implode(' | ', $errors);
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableRecordClasses(): array
    {
        return [
            'bg-red-50 dark:bg-red-900/30' => fn (Product $record) => ($record->available_stock ?? 0) < ($record->minimum_stock ?? 0),
        ];
    }
}
