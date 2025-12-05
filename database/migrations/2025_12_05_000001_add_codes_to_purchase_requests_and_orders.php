<?php

use App\Services\CodeGeneratorService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        $generator = new CodeGeneratorService();

        DB::table('purchase_requests')
            ->orderBy('id')
            ->chunkById(100, function ($requests) use ($generator) {
                foreach ($requests as $request) {
                    $code = $generator->generate(
                        prefix: 'PR-',
                        table: 'purchase_requests',
                        column: 'code',
                        date: $request->requested_at ? Carbon::parse($request->requested_at) : null
                    );

                    DB::table('purchase_requests')
                        ->where('id', $request->id)
                        ->update(['code' => $code]);
                }
            });

        DB::table('purchase_orders')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($generator) {
                foreach ($orders as $order) {
                    $code = $generator->generate(
                        prefix: 'PO-',
                        table: 'purchase_orders',
                        column: 'code',
                        date: $order->created_at ? Carbon::parse($order->created_at) : null
                    );

                    DB::table('purchase_orders')
                        ->where('id', $order->id)
                        ->update(['code' => $code]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
