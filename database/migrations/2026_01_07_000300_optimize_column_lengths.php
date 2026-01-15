<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'username')) {
                DB::statement('ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL');
            }
            if (Schema::hasColumn('users', 'email')) {
                DB::statement('ALTER TABLE users MODIFY email VARCHAR(191) NOT NULL');
            }
            if (Schema::hasColumn('users', 'name')) {
                DB::statement('ALTER TABLE users MODIFY name VARCHAR(120) NOT NULL');
            }
            if (Schema::hasColumn('users', 'phone')) {
                DB::statement('ALTER TABLE users MODIFY phone VARCHAR(30) NOT NULL');
            }
            if (Schema::hasColumn('users', 'role')) {
                DB::statement("ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'STAFF'");
            }
        }

        if (Schema::hasTable('categories')) {
            if (Schema::hasColumn('categories', 'category_name')) {
                DB::statement('ALTER TABLE categories MODIFY category_name VARCHAR(120) NOT NULL');
            }
            if (Schema::hasColumn('categories', 'category_code')) {
                DB::statement('ALTER TABLE categories MODIFY category_code VARCHAR(20) NOT NULL');
            }
        }

        if (Schema::hasTable('suppliers')) {
            if (Schema::hasColumn('suppliers', 'supplier_name')) {
                DB::statement('ALTER TABLE suppliers MODIFY supplier_name VARCHAR(150) NOT NULL');
            }
            if (Schema::hasColumn('suppliers', 'supplier_code')) {
                DB::statement('ALTER TABLE suppliers MODIFY supplier_code VARCHAR(32) NULL');
            }
        }

        if (Schema::hasTable('products')) {
            if (Schema::hasColumn('products', 'product_code')) {
                DB::statement('ALTER TABLE products MODIFY product_code VARCHAR(32) NOT NULL');
            }
            if (Schema::hasColumn('products', 'sku')) {
                DB::statement('ALTER TABLE products MODIFY sku VARCHAR(64) NULL');
            }
            if (Schema::hasColumn('products', 'product_name')) {
                DB::statement('ALTER TABLE products MODIFY product_name VARCHAR(150) NOT NULL');
            }
            if (Schema::hasColumn('products', 'variant')) {
                DB::statement('ALTER TABLE products MODIFY variant VARCHAR(80) NULL');
            }
            if (Schema::hasColumn('products', 'status')) {
                DB::statement("ALTER TABLE products MODIFY status VARCHAR(20) NOT NULL DEFAULT 'DISABLED'");
            }
        }

        if (Schema::hasTable('purchase_requests')) {
            if (Schema::hasColumn('purchase_requests', 'code')) {
                DB::statement('ALTER TABLE purchase_requests MODIFY code VARCHAR(32) NULL');
            }
            if (Schema::hasColumn('purchase_requests', 'status')) {
                DB::statement("ALTER TABLE purchase_requests MODIFY status VARCHAR(20) NOT NULL DEFAULT 'PENDING'");
            }
        }

        if (Schema::hasTable('purchase_orders')) {
            if (Schema::hasColumn('purchase_orders', 'code')) {
                DB::statement('ALTER TABLE purchase_orders MODIFY code VARCHAR(32) NULL');
            }
            if (Schema::hasColumn('purchase_orders', 'status')) {
                DB::statement("ALTER TABLE purchase_orders MODIFY status VARCHAR(20) NOT NULL DEFAULT 'UNPROCESSED'");
            }
        }

        if (Schema::hasTable('purchases')) {
            if (Schema::hasColumn('purchases', 'code')) {
                DB::statement('ALTER TABLE purchases MODIFY code VARCHAR(32) NULL');
            }
            if (Schema::hasColumn('purchases', 'status')) {
                DB::statement("ALTER TABLE purchases MODIFY status VARCHAR(20) NOT NULL DEFAULT 'COMPLETED'");
            }
        }

        if (Schema::hasTable('sales')) {
            if (Schema::hasColumn('sales', 'status')) {
                DB::statement("ALTER TABLE sales MODIFY status VARCHAR(20) NOT NULL DEFAULT 'COMPLETED'");
            }
        }

        if (Schema::hasTable('purchase_request_approvals')) {
            if (Schema::hasColumn('purchase_request_approvals', 'status')) {
                DB::statement('ALTER TABLE purchase_request_approvals MODIFY status VARCHAR(20) NOT NULL');
            }
        }

        if (Schema::hasTable('stock_adjustments')) {
            if (Schema::hasColumn('stock_adjustments', 'reason')) {
                DB::statement('ALTER TABLE stock_adjustments MODIFY reason VARCHAR(20) NOT NULL');
            }
        }

        if (Schema::hasTable('stock_movements')) {
            if (Schema::hasColumn('stock_movements', 'movement_type')) {
                DB::statement('ALTER TABLE stock_movements MODIFY movement_type VARCHAR(20) NOT NULL');
            }
            if (Schema::hasColumn('stock_movements', 'direction')) {
                DB::statement('ALTER TABLE stock_movements MODIFY direction VARCHAR(10) NOT NULL');
            }
            if (Schema::hasColumn('stock_movements', 'source_type')) {
                DB::statement('ALTER TABLE stock_movements MODIFY source_type VARCHAR(30) NULL');
            }
        }

        if (Schema::hasTable('roles')) {
            if (Schema::hasColumn('roles', 'name')) {
                DB::statement('ALTER TABLE roles MODIFY name VARCHAR(30) NOT NULL');
            }
        }

        if (Schema::hasTable('batch_of_stocks')) {
            if (Schema::hasColumn('batch_of_stocks', 'batch_no')) {
                DB::statement('ALTER TABLE batch_of_stocks MODIFY batch_no VARCHAR(64) NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'username')) {
                DB::statement('ALTER TABLE users MODIFY username VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('users', 'email')) {
                DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('users', 'name')) {
                DB::statement('ALTER TABLE users MODIFY name VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('users', 'phone')) {
                DB::statement('ALTER TABLE users MODIFY phone VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('users', 'role')) {
                DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'STAFF'");
            }
        }

        if (Schema::hasTable('categories')) {
            if (Schema::hasColumn('categories', 'category_name')) {
                DB::statement('ALTER TABLE categories MODIFY category_name VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('categories', 'category_code')) {
                DB::statement('ALTER TABLE categories MODIFY category_code VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('suppliers')) {
            if (Schema::hasColumn('suppliers', 'supplier_name')) {
                DB::statement('ALTER TABLE suppliers MODIFY supplier_name VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('suppliers', 'supplier_code')) {
                DB::statement('ALTER TABLE suppliers MODIFY supplier_code VARCHAR(255) NULL');
            }
        }

        if (Schema::hasTable('products')) {
            if (Schema::hasColumn('products', 'product_code')) {
                DB::statement('ALTER TABLE products MODIFY product_code VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('products', 'sku')) {
                DB::statement('ALTER TABLE products MODIFY sku VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('products', 'product_name')) {
                DB::statement('ALTER TABLE products MODIFY product_name VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('products', 'variant')) {
                DB::statement('ALTER TABLE products MODIFY variant VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('products', 'status')) {
                DB::statement("ALTER TABLE products MODIFY status VARCHAR(255) NOT NULL DEFAULT 'DISABLED'");
            }
        }

        if (Schema::hasTable('purchase_requests')) {
            if (Schema::hasColumn('purchase_requests', 'code')) {
                DB::statement('ALTER TABLE purchase_requests MODIFY code VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('purchase_requests', 'status')) {
                DB::statement("ALTER TABLE purchase_requests MODIFY status VARCHAR(255) NOT NULL DEFAULT 'PENDING'");
            }
        }

        if (Schema::hasTable('purchase_orders')) {
            if (Schema::hasColumn('purchase_orders', 'code')) {
                DB::statement('ALTER TABLE purchase_orders MODIFY code VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('purchase_orders', 'status')) {
                DB::statement("ALTER TABLE purchase_orders MODIFY status VARCHAR(255) NOT NULL DEFAULT 'UNPROCESSED'");
            }
        }

        if (Schema::hasTable('purchases')) {
            if (Schema::hasColumn('purchases', 'code')) {
                DB::statement('ALTER TABLE purchases MODIFY code VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('purchases', 'status')) {
                DB::statement("ALTER TABLE purchases MODIFY status VARCHAR(255) NOT NULL DEFAULT 'COMPLETED'");
            }
        }

        if (Schema::hasTable('sales')) {
            if (Schema::hasColumn('sales', 'status')) {
                DB::statement("ALTER TABLE sales MODIFY status VARCHAR(255) NOT NULL DEFAULT 'COMPLETED'");
            }
        }

        if (Schema::hasTable('purchase_request_approvals')) {
            if (Schema::hasColumn('purchase_request_approvals', 'status')) {
                DB::statement('ALTER TABLE purchase_request_approvals MODIFY status VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('stock_adjustments')) {
            if (Schema::hasColumn('stock_adjustments', 'reason')) {
                DB::statement('ALTER TABLE stock_adjustments MODIFY reason VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('stock_movements')) {
            if (Schema::hasColumn('stock_movements', 'movement_type')) {
                DB::statement('ALTER TABLE stock_movements MODIFY movement_type VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('stock_movements', 'direction')) {
                DB::statement('ALTER TABLE stock_movements MODIFY direction VARCHAR(255) NOT NULL');
            }
            if (Schema::hasColumn('stock_movements', 'source_type')) {
                DB::statement('ALTER TABLE stock_movements MODIFY source_type VARCHAR(255) NULL');
            }
        }

        if (Schema::hasTable('roles')) {
            if (Schema::hasColumn('roles', 'name')) {
                DB::statement('ALTER TABLE roles MODIFY name VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('batch_of_stocks')) {
            if (Schema::hasColumn('batch_of_stocks', 'batch_no')) {
                DB::statement('ALTER TABLE batch_of_stocks MODIFY batch_no VARCHAR(255) NULL');
            }
        }
    }
};
