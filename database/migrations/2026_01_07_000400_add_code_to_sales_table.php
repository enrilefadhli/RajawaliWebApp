<?php

use App\Services\CodeGeneratorService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->unique()->after('id');
        });

        $generator = new CodeGeneratorService();

        DB::table('sales')
            ->whereNull('code')
            ->orderBy('id')
            ->select(['id', 'sale_date', 'created_at'])
            ->get()
            ->each(function ($sale) use ($generator) {
                $date = $sale->sale_date ?? $sale->created_at ?? now();
                $code = $generator->generate(
                    prefix: 'S-',
                    table: 'sales',
                    column: 'code',
                    date: \Carbon\Carbon::parse($date),
                    pad: 5
                );

                DB::table('sales')
                    ->where('id', $sale->id)
                    ->update(['code' => $code]);
            });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
