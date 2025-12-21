<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductExport implements FromView, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected View $view
    ) {
    }

    public function view(): View
    {
        return $this->view;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Bold the table headings (row 1).
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);

                // Determine last row/column for the data table.
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn(); // should be J

                // Color the table header row.
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E9F5'], // light blue
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'B0C4DE'],
                        ],
                    ],
                ]);

                // Color the data area lightly.
                if ($highestRow >= 2) {
                    $sheet->getStyle("A2:{$highestColumn}{$highestRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FBFF'], // subtle tint
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E0E6ED'],
                            ],
                        ],
                    ]);
                }

                // Freeze pane below headings.
                $sheet->freezePane('A2');

                // Auto-filter on heading row.
                $sheet->setAutoFilter("A1:{$highestColumn}1");
            },
        ];
    }
}
