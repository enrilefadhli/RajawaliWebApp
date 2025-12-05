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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        $generator = new CodeGeneratorService();

        DB::table('purchases')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($generator) {
                foreach ($rows as $row) {
                    $code = $generator->generate(
                        prefix: 'DP-',
                        table: 'purchases',
                        column: 'code',
                        date: $row->created_at ? \Carbon\Carbon::parse($row->created_at) : null
                    );

                    DB::table('purchases')
                        ->where('id', $row->id)
                        ->update(['code' => $code]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
