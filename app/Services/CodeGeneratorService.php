<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CodeGeneratorService
{
    /**
        * Generate a code with prefix + yyyymmdd + padded sequence.
        */
    public function generate(
        string $prefix,
        string $table,
        string $column = 'code',
        ?Carbon $date = null,
        int $pad = 4
    ): string {
        $date = $date?->copy() ?? Carbon::now();
        $datePart = $date->format('Ymd');
        $base = "{$prefix}{$datePart}";

        $latest = DB::table($table)
            ->where($column, 'like', "{$base}-%")
            ->orderByDesc($column)
            ->value($column);

        $nextSequence = 1;

        if ($latest) {
            $lastNumber = (int) substr($latest, strrpos($latest, '-') + 1);
            $nextSequence = $lastNumber + 1;
        }

        $sequence = str_pad((string) $nextSequence, $pad, '0', STR_PAD_LEFT);

        return "{$base}-{$sequence}";
    }
}
