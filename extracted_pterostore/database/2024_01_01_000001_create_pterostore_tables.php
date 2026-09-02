<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pterostore_balances')) {
            Schema::create('pterostore_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->decimal('balance', 12, 2)->default(0);
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('pterostore_categories')) {
            Schema::create('pterostore_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pterostore_packages')) {
            Schema::create('pterostore_packages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->integer('cpu')->default(100);
                $table->integer('ram')->default(1024)->comment('MB');
                $table->integer('disk')->default(5120)->comment('MB');
                $table->integer('ports')->default(1);
                $table->integer('databases')->default(0);
                $table->text('custom_specs')->nullable()->comment('JSON extra specs');
                $table->decimal('price_monthly', 10, 2)->default(0);
                $table->decimal('price_weekly', 10, 2)->default(0);
                $table->decimal('price_hourly', 10, 2)->default(0);
                $table->unsignedInteger('egg_id')->nullable();
                $table->unsignedInteger('nest_id')->nullable();
                $table->unsignedInteger('location_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
                $table->foreign('category_id')->references('id')->on('pterostore_categories')->onDelete('cascade');
            });
        }

        // Add stock column to packages if not exists
        if (Schema::hasTable('pterostore_packages') && !Schema::hasColumn('pterostore_packages', 'stock')) {
            Schema::table('pterostore_packages', function (Blueprint $table) {
                $table->integer('stock')->default(0)->after('enabled')->comment('0 = unlimited');
            });
        }

        // Add auto_renew column to server_expiry if not exists
        if (Schema::hasTable('pterostore_server_expiry') && !Schema::hasColumn('pterostore_server_expiry', 'auto_renew')) {
            Schema::table('pterostore_server_expiry', function (Blueprint $table) {
                $table->boolean('auto_renew')->default(false)->after('suspended');
            });
        }

        if (!Schema::hasTable('pterostore_server_expiry')) {
            Schema::create('pterostore_server_expiry', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('server_id')->unique();
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('cost', 10, 2)->default(0);
                $table->timestamp('expires_at');
                $table->boolean('suspended')->default(false);
                $table->timestamps();
                $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('pterostore_resource_splits')) {
            Schema::create('pterostore_resource_splits', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->integer('cpu')->default(0)->comment('% total');
                $table->integer('ram')->default(0)->comment('MB');
                $table->integer('disk')->default(0)->comment('MB');
                $table->integer('ports')->default(0);
                $table->integer('databases')->default(0);
                $table->integer('server_limit')->default(0);
                $table->string('node_mode', 20)->default('whitelist');
                $table->text('node_ids')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            if (!Schema::hasColumn('pterostore_resource_splits', 'node_mode')) {
                Schema::table('pterostore_resource_splits', function (Blueprint $table) {
                    $table->string('node_mode', 20)->default('whitelist');
                });
            }
            if (!Schema::hasColumn('pterostore_resource_splits', 'node_ids')) {
                Schema::table('pterostore_resource_splits', function (Blueprint $table) {
                    $table->text('node_ids')->nullable();
                });
            }
        }

        if (!Schema::hasTable('pterostore_split_servers')) {
            Schema::create('pterostore_split_servers', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('owner_id');
                $table->unsignedInteger('server_id');
                $table->integer('cpu_used')->default(0);
                $table->integer('ram_used')->default(0);
                $table->integer('disk_used')->default(0);
                $table->integer('ports_used')->default(0);
                $table->integer('databases_used')->default(0);
                $table->timestamps();
                $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('pterostore_transactions')) {
            Schema::create('pterostore_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('type')->comment('purchase, admin_add, admin_remove, renewal');
                $table->decimal('amount', 10, 2);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pterostore_transactions');
        Schema::dropIfExists('pterostore_split_servers');
        Schema::dropIfExists('pterostore_resource_splits');
        Schema::dropIfExists('pterostore_server_expiry');
        Schema::dropIfExists('pterostore_packages');
        Schema::dropIfExists('pterostore_categories');
        Schema::dropIfExists('pterostore_balances');
    }
};
