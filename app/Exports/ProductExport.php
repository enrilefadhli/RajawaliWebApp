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

                // Bold header rows (1..6) and table headings (row 7).
                $sheet->getStyle('A1:A6')->getFont()->setBold(true);
                $sheet->getStyle('A7:J7')->getFont()->setBold(true);

                // Determine last row/column for the data table.
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn(); // should be J

                // Color the table header row.
                $sheet->getStyle("A7:{$highestColumn}7")->applyFromArray([
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
                if ($highestRow >= 8) {
                    $sheet->getStyle("A8:{$highestColumn}{$highestRow}")->applyFromArray([
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
                $sheet->freezePane('A8');

                // Auto-filter on heading row.
                $sheet->setAutoFilter("A7:{$highestColumn}7");
            },
        ];
    }
}
