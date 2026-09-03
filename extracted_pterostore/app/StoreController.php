<?php

namespace Pterodactyl\BlueprintFramework\Extensions\{identifier};

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;

class StoreController extends Controller
{
    private function getMaxExpirationDays(): int
    {
        try {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
            return (int)($lib->dbGet('{identifier}', 'max_expiration_days') ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function checkMaxExpiration(\Carbon\Carbon $newExpiry): ?string
    {
        $maxDays = $this->getMaxExpirationDays();
        if ($maxDays <= 0) return null;
        $maxDate = now()->addDays($maxDays);
        if ($newExpiry->gt($maxDate)) {
            return "Cannot exceed maximum expiration of {$maxDays} days from now.";
        }
        return null;
    }

    // Get user balance
    public function balance(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $balance = DB::table('pterostore_balances')
            ->where('user_id', $userId)
            ->value('balance') ?? 0;

        return response()->json(['balance' => (float) $balance]);
    }

    // Get shop categories with packages
    public function categories(Request $request): JsonResponse
    {
        $categories = DB::table('pterostore_categories')
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->get();

        $result = [];
        foreach ($categories as $cat) {
            $packages = DB::table('pterostore_packages')
                ->where('category_id', $cat->id)
                ->where('enabled', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($pkg) {
                    $pkg->custom_specs = json_decode($pkg->custom_specs, true) ?? [];
                    $stock = (int)($pkg->stock ?? 0);
                    $sold = DB::table('pterostore_server_expiry')->where('package_id', $pkg->id)->count();
                    $pkg->stock_limit = $stock;
                    $pkg->stock_used = $sold;
                    $pkg->stock_remaining = $stock > 0 ? max(0, $stock - $sold) : null;
                    return $pkg;
                });

            $result[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'packages' => $packages,
            ];
        }

        return response()->json($result);
    }

    // Purchase a package
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|integer',
            'billing_cycle' => 'required|in:monthly,weekly,hourly',
            'coupon_code' => 'nullable|string|max:100',
        ]);

        $userId = $request->user()->id;
        $package = DB::table('pterostore_packages')->find($request->package_id);

        if (!$package || !$package->enabled) {
            return response()->json(['error' => 'Package not found or disabled.'], 404);
        }

        // Check stock limit
        $stock = (int)($package->stock ?? 0);
        if ($stock > 0) {
            $sold = DB::table('pterostore_server_expiry')->where('package_id', $package->id)->count();
            if ($sold >= $stock) {
                return response()->json(['error' => 'This package is out of stock.'], 400);
            }
        }

        $cost = match ($request->billing_cycle) {
            'hourly' => $package->price_hourly,
            'weekly' => $package->price_weekly,
            default => $package->price_monthly,
        };

        // Apply coupon discount
        $coupon = null;
        $discount = 0;
        if ($request->coupon_code) {
            $code = strtoupper(trim($request->coupon_code));
            $coupon = DB::table('pterostore_coupons')
                ->where('code', $code)
                ->where('enabled', true)
                ->first();

            if ($coupon) {
                // Validate usage
                $canUse = true;
                if ($coupon->usage_type === 'single') {
                    if (DB::table('pterostore_coupon_usage')->where('coupon_id', $coupon->id)->where('user_id', $userId)->exists()) {
                        $canUse = false;
                    }
                    if ($coupon->max_uses > 0 && $coupon->times_used >= $coupon->max_uses) {
                        $canUse = false;
                    }
                } elseif ($coupon->usage_type === 'multi') {
                    if ($coupon->max_uses > 0 && $coupon->times_used >= $coupon->max_uses) {
                        $canUse = false;
                    }
                }

                // Package restriction
                if ($canUse && $coupon->package_ids) {
                    $allowed = array_map('intval', array_filter(explode(',', $coupon->package_ids)));
                    if (!empty($allowed) && !in_array($request->package_id, $allowed)) {
                        $canUse = false;
                    }
                }

                if ($canUse) {
                    if ($coupon->type === 'percent') {
                        $discount = $cost * ((float)$coupon->value / 100);
                    } else {
                        $discount = (float)$coupon->value;
                    }
                    $discount = min($discount, $cost);
                    $cost = max(0, $cost - $discount);
                }
            }
        }

        $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;

        if ($balance < $cost) {
            return response()->json(['error' => 'Insufficient balance.', 'required' => $cost, 'balance' => $balance], 402);
        }

        try {
            // Deduct balance first (committed immediately so it's visible)
            DB::table('pterostore_balances')
                ->where('user_id', $userId)
                ->decrement('balance', $cost);

            // Create server OUTSIDE transaction — ServerCreationService needs
            // the server record committed before Wings calls back to fetch config
            $server = $this->createServer($request->user(), $package);

            // Calculate expiry
            $expiresAt = match ($request->billing_cycle) {
                'hourly' => now()->addHour(),
                'weekly' => now()->addWeek(),
                default => now()->addMonth(),
            };

            // Track expiry + transaction (these are non-critical bookkeeping)
            DB::table('pterostore_server_expiry')->insert([
                'server_id' => $server->id,
                'user_id' => $userId,
                'package_id' => $package->id,
                'billing_cycle' => $request->billing_cycle,
                'cost' => $cost,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $desc = "Purchased {$package->name} ({$request->billing_cycle})";
            if ($coupon && $discount > 0) {
                $desc .= " (coupon: {$coupon->code}, -{$discount})";

                // Track coupon usage
                DB::table('pterostore_coupon_usage')->insert([
                    'coupon_id' => $coupon->id,
                    'user_id' => $userId,
                    'used_at' => now(),
                ]);
                DB::table('pterostore_coupons')
                    ->where('id', $coupon->id)
                    ->increment('times_used');
            }

            DB::table('pterostore_transactions')->insert([
                'user_id' => $userId,
                'type' => 'purchase',
                'amount' => -$cost,
                'description' => $desc,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Server created successfully.',
                'server_id' => $server->id,
                'expires_at' => $expiresAt->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            // Refund balance if server creation failed
            DB::table('pterostore_balances')
                ->where('user_id', $userId)
                ->increment('balance', $cost);
            return response()->json(['error' => 'Purchase failed: ' . $e->getMessage()], 500);
        }
    }

    // Renew a server
    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'server_id' => 'required|integer',
        ]);

        $userId = $request->user()->id;
        $expiry = DB::table('pterostore_server_expiry')
            ->where('server_id', $request->server_id)
            ->where('user_id', $userId)
            ->first();

        if (!$expiry) {
            return response()->json(['error' => 'Server not found.'], 404);
        }

        $cost = (float) $expiry->cost;
        $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;

        if ($balance < $cost) {
            return response()->json(['error' => 'Insufficient balance.', 'required' => $cost, 'balance' => $balance], 402);
        }

        DB::beginTransaction();
        try {
            DB::table('pterostore_balances')->where('user_id', $userId)->decrement('balance', $cost);

            $addTime = fn($base) => match ($expiry->billing_cycle) {
                'hourly' => $base->copy()->addHour(),
                'weekly' => $base->copy()->addWeek(),
                default => $base->copy()->addMonth(),
            };

            // If currently expired, extend from now; otherwise extend from current expiry
            $baseTime = \Carbon\Carbon::parse($expiry->expires_at);
            $newExpiry = $baseTime->isPast() ? $addTime(now()) : $addTime($baseTime);

            $maxErr = $this->checkMaxExpiration($newExpiry);
            if ($maxErr) {
                DB::rollBack();
                return response()->json(['error' => $maxErr], 422);
            }

            DB::table('pterostore_server_expiry')
                ->where('id', $expiry->id)
                ->update([
                    'expires_at' => $newExpiry,
                    'suspended' => false,
                    'updated_at' => now(),
                ]);

            // Unsuspend if was suspended
            if ($expiry->suspended) {
                Server::where('id', $request->server_id)->update(['status' => null]);
            }

            DB::table('pterostore_transactions')->insert([
                'user_id' => $userId,
                'type' => 'renewal',
                'amount' => -$cost,
                'description' => "Renewed server #{$request->server_id} ({$expiry->billing_cycle})",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Server renewed successfully.',
                'expires_at' => $newExpiry->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Renewal failed.'], 500);
        }
    }

    // Get server expirations for current user
    public function expirations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $expirations = DB::table('pterostore_server_expiry as se')
            ->leftJoin('pterostore_packages as p', 'p.id', '=', 'se.package_id')
            ->where('se.user_id', $userId)
            ->select('se.*', 'p.name as package_name')
            ->get()
            ->keyBy('server_id');

        return response()->json($expirations);
    }

    // Get package info for a specific server (used by PackageTab)
    public function serverPackage(Request $request, string $uuid): JsonResponse
    {
        $userId = $request->user()->id;
        $server = Server::where('uuid', $uuid)
            ->orWhere('uuidShort', $uuid)
            ->orWhere(DB::raw("LEFT(uuid, " . strlen($uuid) . ")"), $uuid)
            ->first();

        if (!$server) {
            return response()->json(['has_package' => false]);
        }

        $expiry = DB::table('pterostore_server_expiry')
            ->where('server_id', $server->id)
            ->where('user_id', $userId)
            ->first();

        if (!$expiry) {
            return response()->json(['has_package' => false]);
        }

        $package = DB::table('pterostore_packages')->find($expiry->package_id);
        $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;

        $currencyName = 'Coins';
        try {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
            $currencyName = $lib->dbGet('{identifier}', 'currency_name') ?? 'Coins';
        } catch (\Exception $e) {}

        // Get all available packages for switching
        $availablePackages = DB::table('pterostore_packages')
            ->where('enabled', true)
            ->where('id', '!=', $expiry->package_id)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($p) use ($expiry) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price_monthly' => (float) $p->price_monthly,
                    'price_weekly' => (float) $p->price_weekly,
                    'price_hourly' => (float) $p->price_hourly,
                    'cpu' => $p->cpu,
                    'ram' => $p->ram,
                    'disk' => $p->disk,
                ];
            });

        $billingChangeEnabled = true;
        try {
            $billingChangeEnabled = ($lib->dbGet('{identifier}', 'billing_change_enabled') ?? '1') === '1';
        } catch (\Exception $e) {}

        return response()->json([
            'has_package' => true,
            'server_id' => $server->id,
            'package_id' => $expiry->package_id,
            'package_name' => $package->name ?? 'Unknown Package',
            'billing_cycle' => $expiry->billing_cycle,
            'cost' => (float) $expiry->cost,
            'currency' => $currencyName,
            'expires_at' => $expiry->expires_at,
            'suspended' => (bool) $expiry->suspended,
            'auto_renew' => (bool) ($expiry->auto_renew ?? false),
            'balance' => (float) $balance,
            'available_packages' => $availablePackages,
            'billing_change_enabled' => $billingChangeEnabled,
        ]);
    }

    // Toggle auto-renew for a server
    public function toggleAutoRenew(Request $request): JsonResponse
    {
        $request->validate(['server_id' => 'required|integer']);

        $userId = $request->user()->id;
        $expiry = DB::table('pterostore_server_expiry')
            ->where('server_id', $request->server_id)
            ->where('user_id', $userId)
            ->first();

        if (!$expiry) {
            return response()->json(['error' => 'Server not found.'], 404);
        }

        $newValue = !($expiry->auto_renew ?? false);
        DB::table('pterostore_server_expiry')
            ->where('id', $expiry->id)
            ->update(['auto_renew' => $newValue, 'updated_at' => now()]);

        return response()->json(['auto_renew' => $newValue]);
    }

    // Check if user is admin
    public function isAdmin(Request $request): JsonResponse
    {
        return response()->json(['admin' => (bool) $request->user()->root_admin]);
    }

    // Apply coupon code and return discount info
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:100',
            'package_id' => 'required|integer',
        ]);

        $code = strtoupper(trim($request->code));
        $userId = $request->user()->id;
        $coupon = DB::table('pterostore_coupons')
            ->where('code', $code)
            ->where('enabled', true)
            ->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid coupon code.'], 404);
        }

        // Check usage limits
        if ($coupon->usage_type === 'single') {
            $used = DB::table('pterostore_coupon_usage')
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->exists();
            if ($used) {
                return response()->json(['error' => 'You have already used this coupon.'], 422);
            }
            if ($coupon->times_used >= $coupon->max_uses && $coupon->max_uses > 0) {
                return response()->json(['error' => 'This coupon has reached its usage limit.'], 422);
            }
        } elseif ($coupon->usage_type === 'multi') {
            if ($coupon->max_uses > 0 && $coupon->times_used >= $coupon->max_uses) {
                return response()->json(['error' => 'This coupon has reached its usage limit.'], 422);
            }
        }

        // Check package restriction
        if ($coupon->package_ids) {
            $allowedPkgs = array_map('intval', array_filter(explode(',', $coupon->package_ids)));
            if (!empty($allowedPkgs) && !in_array($request->package_id, $allowedPkgs)) {
                return response()->json(['error' => 'This coupon is not valid for the selected package.'], 422);
            }
        }

        $package = DB::table('pterostore_packages')->find($request->package_id);
        if (!$package) {
            return response()->json(['error' => 'Package not found.'], 404);
        }

        return response()->json([
            'valid' => true,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'code' => $coupon->code,
        ]);
    }

    // Claim free splitter resources
    public function claimFreeResources(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
        $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);

        $enabled = $lib->dbGet('{identifier}', 'free_resources_enabled') ?? '0';
        if ($enabled !== '1') {
            return response()->json(['error' => 'Free resources are not available.'], 403);
        }

        $freeCpu = (int)($lib->dbGet('{identifier}', 'free_cpu') ?? 0);
        $freeRam = (int)($lib->dbGet('{identifier}', 'free_ram') ?? 0);
        $freeDisk = (int)($lib->dbGet('{identifier}', 'free_disk') ?? 0);
        $freePorts = (int)($lib->dbGet('{identifier}', 'free_ports') ?? 0);
        $freeDbs = (int)($lib->dbGet('{identifier}', 'free_databases') ?? 0);

        // Check if user already claimed
        $split = DB::table('pterostore_resource_splits')->where('user_id', $userId)->first();
        if ($split && ($split->free_claimed ?? false)) {
            return response()->json(['error' => 'You have already claimed free resources.'], 422);
        }

        // Create or update resource split
        if ($split) {
            DB::table('pterostore_resource_splits')->where('user_id', $userId)->update([
                'cpu' => $split->cpu + $freeCpu,
                'ram' => $split->ram + $freeRam,
                'disk' => $split->disk + $freeDisk,
                'ports' => $split->ports + $freePorts,
                'databases' => $split->databases + $freeDbs,
                'free_cpu' => $freeCpu,
                'free_ram' => $freeRam,
                'free_disk' => $freeDisk,
                'free_ports' => $freePorts,
                'free_databases' => $freeDbs,
                'free_claimed' => true,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('pterostore_resource_splits')->insert([
                'user_id' => $userId,
                'cpu' => $freeCpu,
                'ram' => $freeRam,
                'disk' => $freeDisk,
                'ports' => $freePorts,
                'databases' => $freeDbs,
                'server_limit' => 1,
                'free_cpu' => $freeCpu,
                'free_ram' => $freeRam,
                'free_disk' => $freeDisk,
                'free_ports' => $freePorts,
                'free_databases' => $freeDbs,
                'free_claimed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Free resources claimed!',
            'cpu' => $freeCpu,
            'ram' => $freeRam,
            'disk' => $freeDisk,
            'ports' => $freePorts,
            'databases' => $freeDbs,
        ]);
    }

    // Get free resources info
    public function freeResourcesInfo(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
            $enabled = $lib->dbGet('{identifier}', 'free_resources_enabled') ?? '0';
        } catch (\Exception $e) {
            $enabled = '0';
        }

        if ($enabled !== '1') {
            return response()->json(['enabled' => false]);
        }

        $split = DB::table('pterostore_resource_splits')->where('user_id', $userId)->first();
        $claimed = $split && ($split->free_claimed ?? false);

        try {
            $freeCpu = (int)($lib->dbGet('{identifier}', 'free_cpu') ?? 0);
            $freeRam = (int)($lib->dbGet('{identifier}', 'free_ram') ?? 0);
            $freeDisk = (int)($lib->dbGet('{identifier}', 'free_disk') ?? 0);
            $freePorts = (int)($lib->dbGet('{identifier}', 'free_ports') ?? 0);
            $freeDbs = (int)($lib->dbGet('{identifier}', 'free_databases') ?? 0);
        } catch (\Exception $e) {
            return response()->json(['enabled' => false]);
        }

        return response()->json([
            'enabled' => true,
            'claimed' => $claimed,
            'cpu' => $freeCpu,
            'ram' => $freeRam,
            'disk' => $freeDisk,
            'ports' => $freePorts,
            'databases' => $freeDbs,
        ]);
    }

    // Get store/splitter enable settings for frontend
    public function settings(Request $request): JsonResponse
    {
        try {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
            return response()->json([
                'store_enabled' => ($lib->dbGet('{identifier}', 'store_enabled') ?? '1') === '1',
                'splitter_enabled' => ($lib->dbGet('{identifier}', 'splitter_enabled') ?? '1') === '1',
                'billing_change_enabled' => ($lib->dbGet('{identifier}', 'billing_change_enabled') ?? '1') === '1',
            ]);
        } catch (\Exception $e) {
            return response()->json(['store_enabled' => true, 'splitter_enabled' => true, 'billing_change_enabled' => true]);
        }
    }

    // Process auto-renewals for user's servers (called on page load)
    public function processAutoRenewals(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $renewed = [];

        $servers = DB::table('pterostore_server_expiry')
            ->where('user_id', $userId)
            ->where('auto_renew', true)
            ->where('suspended', false)
            ->where('expires_at', '<=', now()->addMinutes(30))
            ->where('expires_at', '>', now())
            ->get();

        foreach ($servers as $expiry) {
            $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;
            $cost = (float) $expiry->cost;
            if ($balance < $cost) continue;

            DB::table('pterostore_balances')->where('user_id', $userId)->decrement('balance', $cost);

            $newExpiry = match ($expiry->billing_cycle) {
                'hourly' => \Carbon\Carbon::parse($expiry->expires_at)->addHour(),
                'weekly' => \Carbon\Carbon::parse($expiry->expires_at)->addWeek(),
                default => \Carbon\Carbon::parse($expiry->expires_at)->addMonth(),
            };

            DB::table('pterostore_server_expiry')
                ->where('id', $expiry->id)
                ->update(['expires_at' => $newExpiry, 'updated_at' => now()]);

            DB::table('pterostore_transactions')->insert([
                'user_id' => $userId,
                'type' => 'auto_renewal',
                'amount' => -$cost,
                'description' => "Auto-renewed server #{$expiry->server_id} ({$expiry->billing_cycle})",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $renewed[] = $expiry->server_id;
        }

        return response()->json(['renewed' => $renewed]);
    }

    // Change package on a server
    public function changePackage(Request $request): JsonResponse
    {
        $request->validate([
            'server_id' => 'required|integer',
            'new_package_id' => 'required|integer',
        ]);

        $userId = $request->user()->id;
        $expiry = DB::table('pterostore_server_expiry')
            ->where('server_id', $request->server_id)
            ->where('user_id', $userId)
            ->first();

        if (!$expiry) return response()->json(['error' => 'Server not found.'], 404);

        $oldPkg = DB::table('pterostore_packages')->find($expiry->package_id);
        $newPkg = DB::table('pterostore_packages')->find($request->new_package_id);

        if (!$newPkg || !$newPkg->enabled) {
            return response()->json(['error' => 'Package not found or disabled.'], 404);
        }

        $oldCost = match ($expiry->billing_cycle) {
            'hourly' => (float)($oldPkg->price_hourly ?? 0),
            'weekly' => (float)($oldPkg->price_weekly ?? 0),
            default => (float)($oldPkg->price_monthly ?? 0),
        };

        $newCost = match ($expiry->billing_cycle) {
            'hourly' => (float)($newPkg->price_hourly ?? 0),
            'weekly' => (float)($newPkg->price_weekly ?? 0),
            default => (float)($newPkg->price_monthly ?? 0),
        };

        $priceDiff = $newCost - $oldCost;
        $expiresAt = \Carbon\Carbon::parse($expiry->expires_at);
        $now = now();

        if ($priceDiff > 0) {
            $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;
            if ($balance < $priceDiff) {
                return response()->json(['error' => 'Insufficient balance. Need ' . $priceDiff . ' more.'], 402);
            }
            DB::table('pterostore_balances')->where('user_id', $userId)->decrement('balance', $priceDiff);
        }

        // Adjust expiry proportionally
        if (!$expiresAt->isPast() && $oldCost > 0 && $newCost > 0) {
            $remainingSeconds = $now->diffInSeconds($expiresAt);
            $ratio = $oldCost / $newCost;
            $newRemaining = (int)($remainingSeconds * $ratio);
            $expiresAt = $now->copy()->addSeconds($newRemaining);
        }

        // Update server resources
        $server = Server::find($request->server_id);
        if ($server) {
            try {
                $server->update([
                    'memory' => (int)$newPkg->ram,
                    'disk' => (int)$newPkg->disk,
                    'cpu' => (int)$newPkg->cpu,
                    'database_limit' => (int)($newPkg->databases ?? 0),
                    'allocation_limit' => max(0, (int)($newPkg->ports ?? 1) - 1),
                ]);
            } catch (\Exception $e) {}
        }

        DB::table('pterostore_server_expiry')
            ->where('id', $expiry->id)
            ->update([
                'package_id' => $newPkg->id,
                'cost' => $newCost,
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        DB::table('pterostore_transactions')->insert([
            'user_id' => $userId,
            'type' => 'package_change',
            'amount' => $priceDiff > 0 ? -$priceDiff : 0,
            'description' => "Changed package from {$oldPkg->name} to {$newPkg->name}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Package changed successfully.',
            'new_package' => $newPkg->name,
            'new_cost' => $newCost,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    // Change billing cycle
    public function changeBilling(Request $request): JsonResponse
    {
        // Check if billing changes are enabled
        try {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
            if (($lib->dbGet('{identifier}', 'billing_change_enabled') ?? '1') !== '1') {
                return response()->json(['error' => 'Billing cycle changes are disabled.'], 403);
            }
        } catch (\Exception $e) {}

        $request->validate([
            'server_id' => 'required|integer',
            'billing_cycle' => 'required|in:monthly,weekly,hourly',
        ]);

        $userId = $request->user()->id;
        $expiry = DB::table('pterostore_server_expiry')
            ->where('server_id', $request->server_id)
            ->where('user_id', $userId)
            ->first();

        if (!$expiry) return response()->json(['error' => 'Server not found.'], 404);
        if ($expiry->billing_cycle === $request->billing_cycle) {
            return response()->json(['error' => 'Already using this billing cycle.'], 422);
        }

        $package = DB::table('pterostore_packages')->find($expiry->package_id);
        if (!$package) return response()->json(['error' => 'Package not found.'], 404);

        $newCost = match ($request->billing_cycle) {
            'hourly' => (float)($package->price_hourly ?? 0),
            'weekly' => (float)($package->price_weekly ?? 0),
            default => (float)($package->price_monthly ?? 0),
        };

        // Charge one period of the new billing cycle and extend expiry
        $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;
        if ($balance < $newCost) {
            return response()->json(['error' => "Insufficient balance. Need {$newCost} to switch billing cycle.", 'required' => $newCost, 'balance' => $balance], 402);
        }

        $baseTime = \Carbon\Carbon::parse($expiry->expires_at);
        $base = $baseTime->isPast() ? now() : $baseTime;
        $newExpiry = match ($request->billing_cycle) {
            'hourly' => $base->copy()->addHour(),
            'weekly' => $base->copy()->addWeek(),
            default => $base->copy()->addMonth(),
        };

        $maxErr = $this->checkMaxExpiration($newExpiry);
        if ($maxErr) {
            return response()->json(['error' => $maxErr], 422);
        }

        DB::table('pterostore_balances')->where('user_id', $userId)->decrement('balance', $newCost);

        DB::table('pterostore_server_expiry')
            ->where('id', $expiry->id)
            ->update([
                'billing_cycle' => $request->billing_cycle,
                'cost' => $newCost,
                'expires_at' => $newExpiry,
                'suspended' => false,
                'updated_at' => now(),
            ]);

        if ($expiry->suspended) {
            Server::where('id', $request->server_id)->update(['status' => null]);
        }

        DB::table('pterostore_transactions')->insert([
            'user_id' => $userId,
            'type' => 'billing_change',
            'amount' => -$newCost,
            'description' => "Changed billing to {$request->billing_cycle} for server #{$request->server_id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => "Billing changed to {$request->billing_cycle}. Charged {$newCost} and extended by one period.",
            'billing_cycle' => $request->billing_cycle,
            'cost' => $newCost,
            'expires_at' => $newExpiry->toIso8601String(),
        ]);
    }

    // Extend hours for hourly billing
    public function extendHours(Request $request): JsonResponse
    {
        $request->validate([
            'server_id' => 'required|integer',
            'hours' => 'required|integer|min:1|max:10000',
        ]);

        $userId = $request->user()->id;
        $expiry = DB::table('pterostore_server_expiry')
            ->where('server_id', $request->server_id)
            ->where('user_id', $userId)
            ->first();

        if (!$expiry) return response()->json(['error' => 'Server not found.'], 404);
        if ($expiry->billing_cycle !== 'hourly') {
            return response()->json(['error' => 'Only available for hourly billing.'], 422);
        }

        $costPerHour = (float) $expiry->cost;
        $totalCost = $costPerHour * $request->hours;
        $balance = DB::table('pterostore_balances')->where('user_id', $userId)->value('balance') ?? 0;

        if ($balance < $totalCost) {
            return response()->json(['error' => "Insufficient balance. Need {$totalCost}.", 'required' => $totalCost, 'balance' => $balance], 402);
        }

        $baseTime = \Carbon\Carbon::parse($expiry->expires_at);
        $newExpiry = $baseTime->isPast() ? now()->addHours($request->hours) : $baseTime->copy()->addHours($request->hours);

        $maxErr = $this->checkMaxExpiration($newExpiry);
        if ($maxErr) {
            return response()->json(['error' => $maxErr], 422);
        }

        DB::table('pterostore_balances')->where('user_id', $userId)->decrement('balance', $totalCost);

        DB::table('pterostore_server_expiry')
            ->where('id', $expiry->id)
            ->update([
                'expires_at' => $newExpiry,
                'suspended' => false,
                'updated_at' => now(),
            ]);

        if ($expiry->suspended) {
            Server::where('id', $request->server_id)->update(['status' => null]);
        }

        DB::table('pterostore_transactions')->insert([
            'user_id' => $userId,
            'type' => 'extend_hours',
            'amount' => -$totalCost,
            'description' => "Extended server #{$request->server_id} by {$request->hours} hours",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => "Extended by {$request->hours} hours.",
            'expires_at' => $newExpiry->toIso8601String(),
            'charged' => $totalCost,
        ]);
    }

    // Get transaction history
    public function transactions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $transactions = DB::table('pterostore_transactions')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($transactions);
    }

    private function createServer($user, $package): Server
    {
        $service = app(\Pterodactyl\Services\Servers\ServerCreationService::class);

        $egg = \Pterodactyl\Models\Egg::findOrFail($package->egg_id);

        // Find a node — prefer store_nodes setting, then package's node_ids, then location, then any
        $nodeId = null;
        $allocation = null;

        // Check global store_nodes config first (like splitter)
        try {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
            $storeNodesJson = $lib->dbGet('{identifier}', 'store_nodes') ?? '';
            if (!empty($storeNodesJson)) {
                $storeNodes = json_decode($storeNodesJson, true);
                if (is_array($storeNodes) && !empty($storeNodes)) {
                    foreach ($storeNodes as $nc) {
                        $nid = (int)($nc['node_id'] ?? 0);
                        if ($nid <= 0) continue;
                        $portRanges = isset($nc['ports']) ? $nc['ports'] : '';
                        $query = DB::table('allocations')->where('node_id', $nid)->whereNull('server_id');
                        if (!empty($portRanges)) {
                            $allowedPorts = [];
                            foreach (explode(',', $portRanges) as $range) {
                                $range = trim($range);
                                if (str_contains($range, '-')) {
                                    [$start, $end] = array_map('intval', explode('-', $range, 2));
                                    for ($p = $start; $p <= $end; $p++) $allowedPorts[] = $p;
                                } else {
                                    $allowedPorts[] = (int)$range;
                                }
                            }
                            if (!empty($allowedPorts)) {
                                $query->whereIn('port', $allowedPorts);
                            }
                        }
                        $alloc = $query->first();
                        if ($alloc) {
                            $nodeId = $nid;
                            $allocation = $alloc;
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {}

        // Fallback to package node_ids
        if (!$nodeId && !empty($package->node_ids)) {
            $ids = array_filter(array_map('intval', explode(',', $package->node_ids)));
            if (!empty($ids)) {
                $nodeId = DB::table('nodes')->whereIn('id', $ids)->value('id');
            }
        }
        if (!$nodeId && $package->location_id) {
            $nodeId = DB::table('nodes')->where('location_id', $package->location_id)->value('id');
        }
        if (!$nodeId) {
            $nodeId = DB::table('nodes')->value('id');
        }
        if (!$nodeId) {
            throw new \Exception('No nodes available.');
        }

        // Find free allocation on chosen node (if not already found from store_nodes)
        if (!$allocation) {
            $allocation = DB::table('allocations')
                ->where('node_id', $nodeId)
                ->whereNull('server_id')
                ->first();
        }

        if (!$allocation) {
            throw new \Exception('No available allocations found.');
        }

        // Build environment from egg variables
        $environment = [];
        $envVars = DB::table('egg_variables')->where('egg_id', $egg->id)->get();
        foreach ($envVars as $item) {
            $environment[$item->env_variable] = $item->default_value;
        }

        // Get docker image
        $dockerImages = $egg->docker_images;
        $image = 'ghcr.io/pterodactyl/yolks:java_17';
        if (!empty($dockerImages) && is_array($dockerImages)) {
            $image = $dockerImages[array_keys($dockerImages)[0]];
        }

        $server = $service->handle([
            'name' => $package->name . ' - ' . $user->username,
            'description' => 'Created via PteroStore',
            'owner_id' => (int)$user->id,
            'node_id' => (int)$nodeId,
            'allocation_id' => (int)$allocation->id,
            'allocation_additional' => [],
            'memory' => (int)$package->ram,
            'swap' => 0,
            'disk' => (int)$package->disk,
            'io' => 500,
            'cpu' => (int)$package->cpu,
            'threads' => null,
            'nest_id' => (int)$egg->nest_id,
            'egg_id' => (int)$egg->id,
            'startup' => $egg->startup,
            'image' => $image,
            'oom_disabled' => false,
            'environment' => $environment,
            'database_limit' => (int)($package->databases ?? 0),
            'allocation_limit' => max(0, (int)($package->ports ?? 1) - 1),
            'backup_limit' => 0,
            'start_on_completion' => true,
        ]);

        return $server;
    }
}
