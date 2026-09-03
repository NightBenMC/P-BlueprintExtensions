<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\{identifier};

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;

class {identifier}ExtensionController extends Controller
{
    public BlueprintExtensionLibrary $blueprint;

    public function __construct(BlueprintExtensionLibrary $blueprint)
    {
        $this->blueprint = $blueprint;
    }

    private function ensureAllTables(): void
    {
        $tables = [
            'pterostore_balances' => "CREATE TABLE IF NOT EXISTS `pterostore_balances` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int unsigned NOT NULL,
                `balance` decimal(12,2) NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pterostore_balances_user_id_unique` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_categories' => "CREATE TABLE IF NOT EXISTS `pterostore_categories` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `description` text,
                `sort_order` int NOT NULL DEFAULT 0,
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_packages' => "CREATE TABLE IF NOT EXISTS `pterostore_packages` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `category_id` bigint unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                `description` text,
                `image` varchar(255),
                `cpu` int NOT NULL DEFAULT 100,
                `ram` int NOT NULL DEFAULT 1024,
                `disk` int NOT NULL DEFAULT 5120,
                `ports` int NOT NULL DEFAULT 1,
                `databases` int NOT NULL DEFAULT 0,
                `custom_specs` text,
                `price_monthly` decimal(10,2) NOT NULL DEFAULT 0,
                `price_weekly` decimal(10,2) NOT NULL DEFAULT 0,
                `price_hourly` decimal(10,2) NOT NULL DEFAULT 0,
                `egg_id` int unsigned,
                `nest_id` int unsigned,
                `location_id` int unsigned,
                `node_ids` varchar(500) NULL,
                `sort_order` int NOT NULL DEFAULT 0,
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_server_expiry' => "CREATE TABLE IF NOT EXISTS `pterostore_server_expiry` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `server_id` int unsigned NOT NULL,
                `user_id` int unsigned NOT NULL,
                `package_id` bigint unsigned,
                `billing_cycle` varchar(255) NOT NULL DEFAULT 'monthly',
                `cost` decimal(10,2) NOT NULL DEFAULT 0,
                `expires_at` timestamp NOT NULL,
                `suspended` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pterostore_server_expiry_server_id_unique` (`server_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_resource_splits' => "CREATE TABLE IF NOT EXISTS `pterostore_resource_splits` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int unsigned NOT NULL,
                `cpu` int NOT NULL DEFAULT 0,
                `ram` int NOT NULL DEFAULT 0,
                `disk` int NOT NULL DEFAULT 0,
                `ports` int NOT NULL DEFAULT 0,
                `databases` int NOT NULL DEFAULT 0,
                `server_limit` int NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pterostore_resource_splits_user_id_unique` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_split_servers' => "CREATE TABLE IF NOT EXISTS `pterostore_split_servers` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `owner_id` int unsigned NOT NULL,
                `server_id` int unsigned NOT NULL,
                `cpu_used` int NOT NULL DEFAULT 0,
                `ram_used` int NOT NULL DEFAULT 0,
                `disk_used` int NOT NULL DEFAULT 0,
                `ports_used` int NOT NULL DEFAULT 0,
                `databases_used` int NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_coupons' => "CREATE TABLE IF NOT EXISTS `pterostore_coupons` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `code` varchar(100) NOT NULL,
                `type` varchar(20) NOT NULL DEFAULT 'percent',
                `value` decimal(10,2) NOT NULL DEFAULT 0,
                `usage_type` varchar(20) NOT NULL DEFAULT 'single',
                `max_uses` int NOT NULL DEFAULT 1,
                `times_used` int NOT NULL DEFAULT 0,
                `package_ids` varchar(500) NULL,
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pterostore_coupons_code_unique` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_coupon_usage' => "CREATE TABLE IF NOT EXISTS `pterostore_coupon_usage` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `coupon_id` bigint unsigned NOT NULL,
                `user_id` int unsigned NOT NULL,
                `used_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'pterostore_transactions' => "CREATE TABLE IF NOT EXISTS `pterostore_transactions` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int unsigned NOT NULL,
                `type` varchar(255) NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `description` text,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($tables as $name => $sql) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable($name)) {
                    DB::statement($sql);
                }
            } catch (\Exception $e) {
                // continue
            }
        }

        // Ensure columns exist on packages table (may have been created before these were added)
        $schema = \Illuminate\Support\Facades\Schema::class;
        $addCols = [
            ['pterostore_packages', 'ports', "ALTER TABLE `pterostore_packages` ADD COLUMN `ports` int NOT NULL DEFAULT 1"],
            ['pterostore_packages', 'databases', "ALTER TABLE `pterostore_packages` ADD COLUMN `databases` int NOT NULL DEFAULT 0"],
            ['pterostore_packages', 'egg_id', "ALTER TABLE `pterostore_packages` ADD COLUMN `egg_id` int unsigned NULL"],
            ['pterostore_packages', 'nest_id', "ALTER TABLE `pterostore_packages` ADD COLUMN `nest_id` int unsigned NULL"],
            ['pterostore_packages', 'location_id', "ALTER TABLE `pterostore_packages` ADD COLUMN `location_id` int unsigned NULL"],
            ['pterostore_packages', 'custom_specs', "ALTER TABLE `pterostore_packages` ADD COLUMN `custom_specs` text NULL"],
            ['pterostore_packages', 'image', "ALTER TABLE `pterostore_packages` ADD COLUMN `image` varchar(255) NULL"],
            ['pterostore_packages', 'sort_order', "ALTER TABLE `pterostore_packages` ADD COLUMN `sort_order` int NOT NULL DEFAULT 0"],
            ['pterostore_packages', 'enabled', "ALTER TABLE `pterostore_packages` ADD COLUMN `enabled` tinyint(1) NOT NULL DEFAULT 1"],
            ['pterostore_packages', 'node_ids', "ALTER TABLE `pterostore_packages` ADD COLUMN `node_ids` varchar(500) NULL"],
            ['pterostore_packages', 'price_hourly', "ALTER TABLE `pterostore_packages` ADD COLUMN `price_hourly` decimal(10,2) NOT NULL DEFAULT 0"],
            ['pterostore_packages', 'price_monthly', "ALTER TABLE `pterostore_packages` ADD COLUMN `price_monthly` decimal(10,2) NOT NULL DEFAULT 0"],
            ['pterostore_packages', 'price_weekly', "ALTER TABLE `pterostore_packages` ADD COLUMN `price_weekly` decimal(10,2) NOT NULL DEFAULT 0"],
            ['pterostore_server_expiry', 'auto_renew', "ALTER TABLE `pterostore_server_expiry` ADD COLUMN `auto_renew` tinyint(1) NOT NULL DEFAULT 0"],
            ['pterostore_resource_splits', 'free_cpu', "ALTER TABLE `pterostore_resource_splits` ADD COLUMN `free_cpu` int NOT NULL DEFAULT 0"],
            ['pterostore_resource_splits', 'free_ram', "ALTER TABLE `pterostore_resource_splits` ADD COLUMN `free_ram` int NOT NULL DEFAULT 0"],
            ['pterostore_resource_splits', 'free_disk', "ALTER TABLE `pterostore_resource_splits` ADD COLUMN `free_disk` int NOT NULL DEFAULT 0"],
            ['pterostore_resource_splits', 'free_ports', "ALTER TABLE `pterostore_resource_splits` ADD COLUMN `free_ports` int NOT NULL DEFAULT 0"],
            ['pterostore_resource_splits', 'free_databases', "ALTER TABLE `pterostore_resource_splits` ADD COLUMN `free_databases` int NOT NULL DEFAULT 0"],
            ['pterostore_resource_splits', 'free_claimed', "ALTER TABLE `pterostore_resource_splits` ADD COLUMN `free_claimed` tinyint(1) NOT NULL DEFAULT 0"],
        ];
        foreach ($addCols as $col) {
            try {
                if ($schema::hasTable($col[0]) && !$schema::hasColumn($col[0], $col[1])) {
                    DB::statement($col[2]);
                }
            } catch (\Exception $e) { /* already exists */ }
        }
    }

    public function index(Request $request)
    {
        $this->ensureAllTables();

        $categories = DB::table('pterostore_categories')->orderBy('sort_order')->get();
        $packages = DB::table('pterostore_packages')->orderBy('sort_order')->get();
        $users = DB::table('users')->select('id', 'username', 'email')->get();
        $resourceSplits = DB::table('pterostore_resource_splits')->get()->keyBy('user_id');
        $balances = DB::table('pterostore_balances')->get()->keyBy('user_id');
        $expirations = DB::table('pterostore_server_expiry')
            ->leftJoin('servers', 'pterostore_server_expiry.server_id', '=', 'servers.id')
            ->select('pterostore_server_expiry.*', 'servers.name as server_name')
            ->get();
        $eggs = DB::table('eggs')->select('id', 'name', 'nest_id')->get();

        $allocations = DB::table('allocations')
            ->select('id', 'ip', 'port', 'server_id', 'node_id')
            ->orderBy('node_id')->orderBy('ip')->orderBy('port')
            ->get();
        $nodes = DB::table('nodes')->select('id', 'name')->get();
        $coupons = DB::table('pterostore_coupons')->orderByDesc('created_at')->get();

        return view('admin.extensions.{identifier}.index', [
            'blueprint' => $this->blueprint,
            'root' => $request->user()->root_admin,
            'currency_name' => $this->blueprint->dbGet('{identifier}', 'currency_name') ?? 'Coins',
            'grace_period' => $this->blueprint->dbGet('{identifier}', 'grace_period') ?? '1440',
            'allowed_eggs' => $this->blueprint->dbGet('{identifier}', 'allowed_eggs') ?? '',
            'splitter_nodes' => $this->blueprint->dbGet('{identifier}', 'splitter_nodes') ?? '',
            'store_nodes' => $this->blueprint->dbGet('{identifier}', 'store_nodes') ?? '',
            'store_enabled' => $this->blueprint->dbGet('{identifier}', 'store_enabled') ?? '1',
            'splitter_enabled' => $this->blueprint->dbGet('{identifier}', 'splitter_enabled') ?? '1',
            'billing_change_enabled' => $this->blueprint->dbGet('{identifier}', 'billing_change_enabled') ?? '1',
            'max_expiration_days' => $this->blueprint->dbGet('{identifier}', 'max_expiration_days') ?? '0',
            'splitter_badge_text' => $this->blueprint->dbGet('{identifier}', 'splitter_badge_text') ?? 'SPLITTER',
            'splitter_badge_color' => $this->blueprint->dbGet('{identifier}', 'splitter_badge_color') ?? '#3182ce',
            'free_resources_enabled' => $this->blueprint->dbGet('{identifier}', 'free_resources_enabled') ?? '0',
            'free_cpu' => $this->blueprint->dbGet('{identifier}', 'free_cpu') ?? '0',
            'free_ram' => $this->blueprint->dbGet('{identifier}', 'free_ram') ?? '0',
            'free_disk' => $this->blueprint->dbGet('{identifier}', 'free_disk') ?? '0',
            'free_ports' => $this->blueprint->dbGet('{identifier}', 'free_ports') ?? '0',
            'free_databases' => $this->blueprint->dbGet('{identifier}', 'free_databases') ?? '0',
            'categories' => $categories,
            'packages' => $packages,
            'users' => $users,
            'resourceSplits' => $resourceSplits,
            'balances' => $balances,
            'expirations' => $expirations,
            'eggs' => $eggs,
            'allocations' => $allocations,
            'nodes' => $nodes,
            'coupons' => $coupons,
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (!$request->user() || !$request->user()->root_admin) {
            abort(403);
        }

        $action = $request->input('_action', 'settings');

        switch ($action) {
            case 'settings':
                $this->handleSettings($request);
                break;
            case 'add_category':
                $this->handleAddCategory($request);
                break;
            case 'delete_category':
                $this->handleDeleteCategory($request);
                break;
            case 'add_package':
                $this->handleAddPackage($request);
                break;
            case 'update_package':
                $this->handleUpdatePackage($request);
                break;
            case 'delete_package':
                $this->handleDeletePackage($request);
                break;
            case 'update_balance':
                $this->handleUpdateBalance($request);
                break;
            case 'update_resources':
                $this->handleUpdateResources($request);
                break;
            case 'update_expiry':
                $this->handleUpdateExpiry($request);
                break;
            case 'toggle_auto_renew':
                $this->handleToggleAutoRenew($request);
                break;
            case 'delete_purchased_server':
                $this->handleDeletePurchasedServer($request);
                break;
            case 'add_coupon':
                $this->handleAddCoupon($request);
                break;
            case 'delete_coupon':
                $this->handleDeleteCoupon($request);
                break;
        }

        return redirect()->route('admin.extensions.{identifier}.index')
            ->with('success', 'Updated successfully.');
    }

    private function handleSettings(Request $request): void
    {
        $validated = $request->validate([
            'currency_name' => 'required|string|max:50',
            'grace_period' => 'required|integer|min:0|max:525600',
            'allowed_eggs' => 'nullable|string|max:1000',
            'splitter_nodes' => 'nullable|string|max:5000',
            'store_nodes' => 'nullable|string|max:5000',
            'splitter_badge_text' => 'nullable|string|max:50',
            'splitter_badge_color' => 'nullable|string|max:20',
            'store_enabled' => 'nullable|string',
            'splitter_enabled' => 'nullable|string',
            'billing_change_enabled' => 'nullable|string',
            'max_expiration_days' => 'nullable|integer|min:0',
            'free_resources_enabled' => 'nullable|string',
            'free_cpu' => 'nullable|integer|min:0',
            'free_ram' => 'nullable|integer|min:0',
            'free_disk' => 'nullable|integer|min:0',
            'free_ports' => 'nullable|integer|min:0',
            'free_databases' => 'nullable|integer|min:0',
        ]);

        $validated['store_enabled'] = $request->has('store_enabled') ? '1' : '0';
        $validated['splitter_enabled'] = $request->has('splitter_enabled') ? '1' : '0';
        $validated['billing_change_enabled'] = $request->has('billing_change_enabled') ? '1' : '0';
        $validated['free_resources_enabled'] = $request->has('free_resources_enabled') ? '1' : '0';

        foreach ($validated as $key => $value) {
            $this->blueprint->dbSet('{identifier}', $key, (string)($value ?? ''));
        }
    }

    private function handleAddCategory(Request $request): void
    {
        $request->validate([
            'cat_name' => 'required|string|max:100',
            'cat_description' => 'nullable|string|max:500',
            'cat_sort' => 'nullable|integer',
        ]);

        DB::table('pterostore_categories')->insert([
            'name' => strip_tags($request->cat_name),
            'description' => strip_tags($request->cat_description),
            'sort_order' => $request->cat_sort ?? 0,
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function handleDeleteCategory(Request $request): void
    {
        $request->validate(['cat_id' => 'required|integer']);
        DB::table('pterostore_categories')->where('id', $request->cat_id)->delete();
    }

    private function handleAddPackage(Request $request): void
    {
        $request->validate([
            'pkg_name' => 'required|string|max:100',
            'pkg_category_id' => 'required|integer',
            'pkg_cpu' => 'nullable|integer|min:1',
            'pkg_ram' => 'nullable|integer|min:1',
            'pkg_disk' => 'nullable|integer|min:1',
            'pkg_ports' => 'nullable|integer|min:0',
            'pkg_databases' => 'nullable|integer|min:0',
            'pkg_price_monthly' => 'nullable|numeric|min:0',
            'pkg_price_weekly' => 'nullable|numeric|min:0',
            'pkg_price_hourly' => 'nullable|numeric|min:0',
            'pkg_egg_id' => 'nullable|integer',
            'pkg_node_ids' => 'nullable|array',
        ]);

        $nodeIds = $request->input('pkg_node_ids');
        $nodeIdsStr = is_array($nodeIds) ? implode(',', $nodeIds) : null;

        $data = [
            'category_id' => (int)$request->pkg_category_id,
            'name' => strip_tags($request->pkg_name),
            'description' => strip_tags($request->input('pkg_description', '')),
            'image' => strip_tags($request->input('pkg_image', '')),
            'cpu' => (int)($request->pkg_cpu ?? 100),
            'ram' => (int)($request->pkg_ram ?? 1024),
            'disk' => (int)($request->pkg_disk ?? 5120),
            'ports' => (int)($request->pkg_ports ?? 1),
            'databases' => (int)($request->pkg_databases ?? 0),
            'price_monthly' => $request->pkg_price_monthly ?? 0,
            'price_weekly' => $request->pkg_price_weekly ?? 0,
            'price_hourly' => $request->pkg_price_hourly ?? 0,
            'egg_id' => $request->pkg_egg_id ? (int)$request->pkg_egg_id : null,
            'node_ids' => $nodeIdsStr,
            'sort_order' => (int)$request->input('pkg_sort', 0),
            'stock' => (int)$request->input('pkg_stock', 0),
            'enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($request->pkg_egg_id) {
            $data['nest_id'] = DB::table('eggs')->where('id', (int)$request->pkg_egg_id)->value('nest_id');
        }

        DB::table('pterostore_packages')->insert($data);
    }

    private function handleUpdatePackage(Request $request): void
    {
        $request->validate([
            'pkg_id' => 'required|integer',
            'pkg_name' => 'required|string|max:100',
            'pkg_category_id' => 'required|integer',
            'pkg_egg_id' => 'nullable|integer',
            'pkg_cpu' => 'nullable|integer|min:1',
            'pkg_ram' => 'nullable|integer|min:1',
            'pkg_disk' => 'nullable|integer|min:1',
            'pkg_price_monthly' => 'nullable|numeric|min:0',
            'pkg_price_weekly' => 'nullable|numeric|min:0',
            'pkg_price_hourly' => 'nullable|numeric|min:0',
            'pkg_node_ids' => 'nullable|array',
        ]);

        $nodeIds = $request->input('pkg_node_ids');
        $nodeIdsStr = is_array($nodeIds) ? implode(',', $nodeIds) : null;

        $data = [
            'name' => strip_tags($request->pkg_name),
            'category_id' => (int)$request->pkg_category_id,
            'egg_id' => $request->pkg_egg_id ? (int)$request->pkg_egg_id : null,
            'description' => strip_tags($request->input('pkg_description', '')),
            'image' => strip_tags($request->input('pkg_image', '')),
            'cpu' => (int)($request->pkg_cpu ?? 100),
            'ram' => (int)($request->pkg_ram ?? 1024),
            'disk' => (int)($request->pkg_disk ?? 5120),
            'ports' => (int)$request->input('pkg_ports', 1),
            'databases' => (int)$request->input('pkg_databases', 0),
            'node_ids' => $nodeIdsStr,
            'price_monthly' => $request->pkg_price_monthly ?? 0,
            'price_weekly' => $request->pkg_price_weekly ?? 0,
            'price_hourly' => $request->pkg_price_hourly ?? 0,
            'stock' => (int)$request->input('pkg_stock', 0),
            'updated_at' => now(),
        ];

        if ($request->pkg_egg_id) {
            $data['nest_id'] = DB::table('eggs')->where('id', (int)$request->pkg_egg_id)->value('nest_id');
        }

        DB::table('pterostore_packages')->where('id', $request->pkg_id)->update($data);
    }

    private function handleDeletePackage(Request $request): void
    {
        $request->validate(['pkg_id' => 'required|integer']);
        DB::table('pterostore_packages')->where('id', $request->pkg_id)->delete();
    }

    private function handleUpdateBalance(Request $request): void
    {
        $request->validate([
            'user_id' => 'required|integer',
            'balance_action' => 'required|in:set,add,remove',
            'balance_amount' => 'required|numeric|min:0',
        ]);

        $userId = $request->user_id;
        $amount = (float) $request->balance_amount;

        // Ensure row exists
        $exists = DB::table('pterostore_balances')->where('user_id', $userId)->exists();
        if (!$exists) {
            DB::table('pterostore_balances')->insert([
                'user_id' => $userId,
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        switch ($request->balance_action) {
            case 'set':
                DB::table('pterostore_balances')->where('user_id', $userId)->update(['balance' => $amount]);
                $type = 'admin_add';
                $desc = "Balance set to {$amount}";
                break;
            case 'add':
                DB::table('pterostore_balances')->where('user_id', $userId)->increment('balance', $amount);
                $type = 'admin_add';
                $desc = "Admin added {$amount}";
                break;
            case 'remove':
                DB::table('pterostore_balances')->where('user_id', $userId)->decrement('balance', $amount);
                $type = 'admin_remove';
                $desc = "Admin removed {$amount}";
                break;
        }

        DB::table('pterostore_transactions')->insert([
            'user_id' => $userId,
            'type' => $type ?? 'admin_add',
            'amount' => $request->balance_action === 'remove' ? -$amount : $amount,
            'description' => $desc ?? '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function handleUpdateResources(Request $request): void
    {
        $request->validate([
            'res_user_id' => 'required|integer',
            'res_cpu' => 'required|integer|min:0',
            'res_ram' => 'required|integer|min:0',
            'res_disk' => 'required|integer|min:0',
            'res_ports' => 'required|integer|min:0',
            'res_databases' => 'required|integer|min:0',
            'res_server_limit' => 'required|integer|min:0',
            'res_node_mode' => 'nullable|string|in:whitelist,blacklist',
            'res_node_ids' => 'nullable|string|max:500',
        ]);

        $nodeMode = $request->input('res_node_mode', 'whitelist');
        $nodeIds = $request->input('res_node_ids', '');

        // Normalize node_ids: comma separated integers
        if (!empty($nodeIds)) {
            $ids = array_filter(array_map('trim', explode(',', $nodeIds)), function($val) {
                return is_numeric($val);
            });
            $nodeIds = implode(',', $ids);
        } else {
            $nodeIds = '';
        }

        DB::table('pterostore_resource_splits')->updateOrInsert(
            ['user_id' => $request->res_user_id],
            [
                'cpu' => $request->res_cpu,
                'ram' => $request->res_ram,
                'disk' => $request->res_disk,
                'ports' => $request->res_ports,
                'databases' => $request->res_databases,
                'server_limit' => $request->res_server_limit,
                'node_mode' => $nodeMode,
                'node_ids' => $nodeIds,
                'updated_at' => now(),
            ]
        );
    }

    private function handleUpdateExpiry(Request $request): void
    {
        $request->validate([
            'expiry_id' => 'required|integer',
            'expiry_action' => 'required|in:add_time,remove_time,change_cost',
        ]);

        $expiry = DB::table('pterostore_server_expiry')->find($request->expiry_id);
        if (!$expiry) return;

        switch ($request->expiry_action) {
            case 'add_time':
                $minutes = (int) $request->input('expiry_minutes', 0);
                DB::table('pterostore_server_expiry')
                    ->where('id', $request->expiry_id)
                    ->update([
                        'expires_at' => \Carbon\Carbon::parse($expiry->expires_at)->addMinutes($minutes),
                        'updated_at' => now(),
                    ]);
                break;
            case 'remove_time':
                $minutes = (int) $request->input('expiry_minutes', 0);
                DB::table('pterostore_server_expiry')
                    ->where('id', $request->expiry_id)
                    ->update([
                        'expires_at' => \Carbon\Carbon::parse($expiry->expires_at)->subMinutes($minutes),
                        'updated_at' => now(),
                    ]);
                break;
            case 'change_cost':
                $newCost = (float) $request->input('expiry_cost', 0);
                DB::table('pterostore_server_expiry')
                    ->where('id', $request->expiry_id)
                    ->update(['cost' => $newCost, 'updated_at' => now()]);
                break;
        }
    }

    private function handleToggleAutoRenew(Request $request): void
    {
        $request->validate(['expiry_id' => 'required|integer']);
        $expiry = DB::table('pterostore_server_expiry')->find($request->expiry_id);
        if (!$expiry) return;

        DB::table('pterostore_server_expiry')
            ->where('id', $request->expiry_id)
            ->update([
                'auto_renew' => !($expiry->auto_renew ?? false),
                'updated_at' => now(),
            ]);
    }

    private function handleDeletePurchasedServer(Request $request): void
    {
        $request->validate([
            'expiry_id' => 'required|integer',
            'server_id' => 'required|integer',
        ]);

        // Delete the Pterodactyl server
        $server = \Pterodactyl\Models\Server::find($request->server_id);
        if ($server) {
            try {
                $deletionService = app(\Pterodactyl\Services\Servers\ServerDeletionService::class);
                $deletionService->handle($server);
            } catch (\Throwable $e) {
                try {
                    $deletionService = app(\Pterodactyl\Services\Servers\ServerDeletionService::class);
                    $deletionService->withForce()->handle($server);
                } catch (\Throwable $e2) {
                    $server->delete();
                }
            }
        }

        // Remove expiry record
        DB::table('pterostore_server_expiry')->where('id', $request->expiry_id)->delete();
    }

    private function handleAddCoupon(Request $request): void
    {
        $request->validate([
            'coupon_code' => 'required|string|max:100',
            'coupon_type' => 'required|in:percent,fixed',
            'coupon_value' => 'required|numeric|min:0',
            'coupon_usage_type' => 'required|in:single,multi,unlimited',
            'coupon_max_uses' => 'nullable|integer|min:1',
            'coupon_package_ids' => 'nullable|string|max:500',
        ]);

        $maxUses = $request->coupon_usage_type === 'unlimited' ? 0 : ($request->coupon_max_uses ?? 1);

        DB::table('pterostore_coupons')->insert([
            'code' => strtoupper(trim($request->coupon_code)),
            'type' => $request->coupon_type,
            'value' => $request->coupon_value,
            'usage_type' => $request->coupon_usage_type,
            'max_uses' => $maxUses,
            'times_used' => 0,
            'package_ids' => $request->coupon_package_ids ?: null,
            'enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function handleDeleteCoupon(Request $request): void
    {
        $request->validate(['coupon_id' => 'required|integer']);
        DB::table('pterostore_coupons')->where('id', $request->coupon_id)->delete();
        DB::table('pterostore_coupon_usage')->where('coupon_id', $request->coupon_id)->delete();
    }
}
