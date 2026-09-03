<?php

namespace Pterodactyl\BlueprintFramework\Extensions\{identifier};

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Servers\ServerCreationService;

class SplitterController extends ClientApiController
{
    private ServerCreationService $creationService;
    private static bool $tablesChecked = false;

    public function __construct(ServerCreationService $creationService)
    {
        parent::__construct();
        $this->creationService = $creationService;
    }

    private function ensureTables(): void
    {
        if (self::$tablesChecked) return;
        self::$tablesChecked = true;

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('pterostore_resource_splits')) {
                DB::statement("CREATE TABLE IF NOT EXISTS `pterostore_resource_splits` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `user_id` int unsigned NOT NULL,
                    `cpu` int NOT NULL DEFAULT 0,
                    `ram` int NOT NULL DEFAULT 0,
                    `disk` int NOT NULL DEFAULT 0,
                    `ports` int NOT NULL DEFAULT 0,
                    `databases` int NOT NULL DEFAULT 0,
                    `server_limit` int NOT NULL DEFAULT 0,
                    `node_mode` varchar(20) NOT NULL DEFAULT 'whitelist',
                    `node_ids` text NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `pterostore_resource_splits_user_id_unique` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } else {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('pterostore_resource_splits', 'node_mode')) {
                    DB::statement("ALTER TABLE `pterostore_resource_splits` ADD COLUMN `node_mode` varchar(20) NOT NULL DEFAULT 'whitelist'");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('pterostore_resource_splits', 'node_ids')) {
                    DB::statement("ALTER TABLE `pterostore_resource_splits` ADD COLUMN `node_ids` text NULL");
                }
            }
            if (!\Illuminate\Support\Facades\Schema::hasTable('pterostore_split_servers')) {
                DB::statement("CREATE TABLE IF NOT EXISTS `pterostore_split_servers` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (\Exception $e) {
        }
    }

    private function getBlueprintLib()
    {
        static $lib = null;
        if ($lib === null) {
            $settings = app('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
            $lib = app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);
        }
        return $lib;
    }

    public function resources(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $empty = [
            'cpu' => 0, 'ram' => 0, 'disk' => 0,
            'ports' => 0, 'databases' => 0, 'server_limit' => 0,
            'used' => ['cpu' => 0, 'ram' => 0, 'disk' => 0, 'ports' => 0, 'databases' => 0, 'servers' => 0],
        ];

        $this->ensureTables();

        try {
            $resources = DB::table('pterostore_resource_splits')
                ->where('user_id', $userId)
                ->first();
        } catch (\Exception $e) {
            return response()->json(array_merge($empty, ['debug' => $e->getMessage()]));
        }

        if (!$resources) {
            return response()->json($empty);
        }

        try {
            $used = DB::table('pterostore_split_servers')
                ->where('owner_id', $userId)
                ->selectRaw('COALESCE(SUM(cpu_used),0) as cpu, COALESCE(SUM(ram_used),0) as ram, COALESCE(SUM(disk_used),0) as disk, COALESCE(SUM(ports_used),0) as ports, COALESCE(SUM(databases_used),0) as db_count, COUNT(*) as srv_count')
                ->first();
        } catch (\Exception $e) {
            $used = (object)['cpu' => 0, 'ram' => 0, 'disk' => 0, 'ports' => 0, 'db_count' => 0, 'srv_count' => 0];
        }

        return response()->json([
            'cpu' => $resources->cpu,
            'ram' => $resources->ram,
            'disk' => $resources->disk,
            'ports' => $resources->ports,
            'databases' => $resources->databases,
            'server_limit' => $resources->server_limit,
            'used' => [
                'cpu' => (int) $used->cpu,
                'ram' => (int) $used->ram,
                'disk' => (int) $used->disk,
                'ports' => (int) $used->ports,
                'databases' => (int) $used->db_count,
                'servers' => (int) $used->srv_count,
            ],
        ]);
    }

    public function eggs(Request $request): JsonResponse
    {
        $blueprint = $this->getBlueprintLib();
        $allowedEggs = $blueprint->dbGet('{identifier}', 'allowed_eggs') ?? '';
        $eggIds = array_filter(array_map('intval', explode(',', $allowedEggs)));

        if (empty($eggIds)) {
            return response()->json([]);
        }

        // Use lightweight DB query instead of Eloquent
        $eggs = DB::table('eggs')
            ->whereIn('id', $eggIds)
            ->select('id', 'name', 'description', 'nest_id')
            ->get();

        return response()->json($eggs);
    }

    public function badge(Request $request): JsonResponse
    {
        $blueprint = $this->getBlueprintLib();
        $badgeText = $blueprint->dbGet('{identifier}', 'splitter_badge_text') ?? 'SPLITTER';
        $badgeColor = $blueprint->dbGet('{identifier}', 'splitter_badge_color') ?? '#3182ce';

        $userId = $request->user()->id;

        // Single join query instead of N+1 Server::find() calls
        $splitServers = DB::table('pterostore_split_servers as ps')
            ->join('servers as s', 's.id', '=', 'ps.server_id')
            ->where('ps.owner_id', $userId)
            ->select(
                'ps.id', 'ps.server_id', 'ps.cpu_used', 'ps.ram_used',
                'ps.disk_used', 'ps.ports_used', 'ps.databases_used',
                's.uuid', 's.name', 's.status'
            )
            ->get();

        $uuids = [];
        $servers = [];
        foreach ($splitServers as $row) {
            $uuids[] = $row->uuid;
            $servers[] = [
                'id' => $row->id,
                'server_id' => $row->server_id,
                'uuid' => $row->uuid,
                'name' => $row->name,
                'status' => $row->status ?? 'running',
                'resources' => [
                    'cpu' => $row->cpu_used,
                    'ram' => $row->ram_used,
                    'disk' => $row->disk_used,
                    'ports' => $row->ports_used,
                    'databases' => $row->databases_used,
                ],
            ];
        }

        return response()->json([
            'text' => $badgeText,
            'color' => $badgeColor,
            'server_uuids' => $uuids,
            'split_servers' => $servers,
        ]);
    }

    public function nodes(Request $request): JsonResponse
    {
        $this->ensureTables();
        $userId = $request->user() ? $request->user()->id : null;
        $userRestrictions = null;
        if ($userId) {
            $userRestrictions = DB::table('pterostore_resource_splits')->where('user_id', $userId)->first();
        }

        $allowedNodeIds = [];
        $isWhitelist = true;
        $hasRestrictions = false;

        if ($userRestrictions && !empty(trim($userRestrictions->node_ids ?? ''))) {
            $hasRestrictions = true;
            $isWhitelist = ($userRestrictions->node_mode ?? 'whitelist') === 'whitelist';
            $allowedNodeIds = array_map('intval', array_filter(array_map('trim', explode(',', $userRestrictions->node_ids))));
        }

        $blueprint = $this->getBlueprintLib();
        $splitterNodesJson = $blueprint->dbGet('{identifier}', 'splitter_nodes') ?? '';

        $allCandidates = [];

        if (!empty(trim($splitterNodesJson))) {
            $nodeConfigs = json_decode($splitterNodesJson, true);
            // Handle single object (not wrapped in array) — auto-wrap it
            if (is_array($nodeConfigs) && isset($nodeConfigs['node_id'])) {
                $nodeConfigs = [$nodeConfigs];
            }
            if (is_array($nodeConfigs) && count($nodeConfigs) > 0) {
                foreach ($nodeConfigs as $nc) {
                    if (!is_array($nc)) continue;
                    $nodeId = (int)($nc['node_id'] ?? 0);
                    $maxServers = (int)($nc['max_servers'] ?? 0);
                    $serversOnNode = $nodeId > 0 ? DB::table('servers')->where('node_id', $nodeId)
                        ->whereIn('id', DB::table('pterostore_split_servers')->pluck('server_id'))
                        ->count() : 0;
                    $entry = [
                        'node_id' => $nodeId,
                        'name' => $nc['name'] ?? ('Node ' . ($nc['node_id'] ?? '?')),
                        'ip' => $nc['ip'] ?? '',
                        'servers_count' => $serversOnNode,
                    ];
                    if ($maxServers > 0) {
                        $entry['max_servers'] = $maxServers;
                        $entry['slots_remaining'] = max(0, $maxServers - $serversOnNode);
                    }
                    $allCandidates[] = $entry;
                }
            }
        }

        if (empty($allCandidates)) {
            // Fallback: return all Pterodactyl nodes so users can choose
            $nodes = DB::table('nodes')->select('id', 'name', 'fqdn')->get();
            foreach ($nodes as $node) {
                $allCandidates[] = [
                    'node_id' => (int)$node->id,
                    'name' => $node->name,
                    'ip' => $node->fqdn ?? '',
                ];
            }
        }

        if ($hasRestrictions) {
            $allCandidates = array_values(array_filter($allCandidates, function($item) use ($allowedNodeIds, $isWhitelist) {
                $nodeId = (int)($item['node_id'] ?? 0);
                $inList = in_array($nodeId, $allowedNodeIds, true);
                return $isWhitelist ? $inList : !$inList;
            }));
        }

        return response()->json($allCandidates);
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:255',
                'egg_id' => 'required|integer',
                'cpu' => 'required|integer|min:1',
                'ram' => 'required|integer|min:64',
                'disk' => 'required|integer|min:256',
                'ports' => 'required|integer|min:1|max:10',
                'databases' => 'required|integer|min:0|max:10',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
        }

        $this->ensureTables();

        try {
            $userId = $request->user()->id;
            $resources = DB::table('pterostore_resource_splits')
                ->where('user_id', $userId)->first();

            if (!$resources) {
                return response()->json(['error' => 'No resources allocated.'], 403);
            }

            $used = DB::table('pterostore_split_servers')
                ->where('owner_id', $userId)
                ->selectRaw('COALESCE(SUM(cpu_used),0) as cpu, COALESCE(SUM(ram_used),0) as ram, COALESCE(SUM(disk_used),0) as disk, COALESCE(SUM(ports_used),0) as ports, COALESCE(SUM(databases_used),0) as db_count, COUNT(*) as srv_count')
                ->first();

            $checks = [
                'cpu' => ['requested' => (int)$request->cpu, 'available' => $resources->cpu - $used->cpu],
                'ram' => ['requested' => (int)$request->ram, 'available' => $resources->ram - $used->ram],
                'disk' => ['requested' => (int)$request->disk, 'available' => $resources->disk - $used->disk],
                'ports' => ['requested' => (int)$request->ports, 'available' => $resources->ports - $used->ports],
                'databases' => ['requested' => (int)$request->databases, 'available' => $resources->databases - $used->db_count],
            ];

            foreach ($checks as $key => $check) {
                if ($check['requested'] > $check['available']) {
                    return response()->json(['error' => "Not enough {$key}. Available: {$check['available']}, Requested: {$check['requested']}"], 422);
                }
            }

            if ($used->srv_count >= $resources->server_limit) {
                return response()->json(['error' => 'Server limit reached.'], 422);
            }

            $egg = Egg::find($request->egg_id);
            if (!$egg) {
                return response()->json(['error' => 'Invalid egg.'], 422);
            }

            $node = DB::table('nodes')->first();
            if (!$node) {
                return response()->json(['error' => 'No nodes available.'], 500);
            }

            // Enforce node whitelist / blacklist for requested node
            $requestedNodeId = $request->input('node_id') ? (int)$request->input('node_id') : null;
            if ($resources && !empty(trim($resources->node_ids ?? ''))) {
                $isWhitelist = ($resources->node_mode ?? 'whitelist') === 'whitelist';
                $allowedNodeIds = array_map('intval', array_filter(array_map('trim', explode(',', $resources->node_ids))));

                if ($requestedNodeId) {
                    $inList = in_array($requestedNodeId, $allowedNodeIds, true);
                    if ($isWhitelist && !$inList) {
                        return response()->json(['error' => "You are not authorized to deploy on Node #{$requestedNodeId} (whitelist mode)."], 403);
                    }
                    if (!$isWhitelist && $inList) {
                        return response()->json(['error' => "You are not authorized to deploy on Node #{$requestedNodeId} (blacklist mode)."], 403);
                    }
                }
            }

            $blueprint = $this->getBlueprintLib();
            $splitterNodesJson = $blueprint->dbGet('{identifier}', 'splitter_nodes') ?? '';

            $allocation = null;
            $targetNodeId = $node->id;

            if (!empty(trim($splitterNodesJson))) {
                $nodeConfigs = json_decode($splitterNodesJson, true);
                // Handle single object (not wrapped in array)
                if (is_array($nodeConfigs) && isset($nodeConfigs['node_id'])) {
                    $nodeConfigs = [$nodeConfigs];
                }
                if (is_array($nodeConfigs) && count($nodeConfigs) > 0) {
                    $selectedConfig = null;
                    if ($requestedNodeId) {
                        foreach ($nodeConfigs as $nc) {
                            if (!is_array($nc)) continue;
                            if ((int)($nc['node_id'] ?? 0) === $requestedNodeId) {
                                $selectedConfig = $nc;
                                break;
                            }
                        }
                    }
                    if (!$selectedConfig && is_array($nodeConfigs[0])) $selectedConfig = $nodeConfigs[0];

                    if ($selectedConfig) {
                        $targetNodeId = (int)($selectedConfig['node_id'] ?? $node->id);

                        $portsStr = $selectedConfig['ports'] ?? '';
                        if (!empty(trim($portsStr))) {
                            $ranges = array_filter(array_map('trim', explode(',', $portsStr)));
                            $portConditions = [];
                            foreach ($ranges as $range) {
                                if (strpos($range, '-') !== false) {
                                    $parts = explode('-', $range, 2);
                                    $portConditions[] = [(int)trim($parts[0]), (int)trim($parts[1])];
                                } else {
                                    $port = (int)trim($range);
                                    $portConditions[] = [$port, $port];
                                }
                            }
                            $query = Allocation::where('node_id', $targetNodeId)->whereNull('server_id');
                            $query->where(function($q) use ($portConditions) {
                                foreach ($portConditions as $cond) {
                                    $q->orWhereBetween('port', [$cond[0], $cond[1]]);
                                }
                            });
                            $allocation = $query->first();
                        }
                    }
                }
            }

            // If user requested a specific node but no config matched, use their node
            if (!$allocation && $requestedNodeId) {
                $targetNodeId = $requestedNodeId;
            }

            if (!$allocation) {
                $allocation = Allocation::where('node_id', $targetNodeId)
                    ->whereNull('server_id')
                    ->first();
            }

            if (!$allocation) {
                return response()->json(['error' => 'No free allocations on this node.'], 500);
            }

            $environment = [];
            $envVars = DB::table('egg_variables')->where('egg_id', $egg->id)->get();
            foreach ($envVars as $item) {
                $environment[$item->env_variable] = $item->default_value;
            }

            $dockerImages = $egg->docker_images;
            $image = 'ghcr.io/pterodactyl/yolks:java_17';
            if (!empty($dockerImages) && is_array($dockerImages)) {
                $image = $dockerImages[array_keys($dockerImages)[0]];
            }

            $server = $this->creationService->handle([
                'name' => strip_tags($request->name),
                'description' => $request->input('description') ?: 'Split server',
                'owner_id' => (int)$userId,
                'node_id' => (int)$targetNodeId,
                'allocation_id' => (int)$allocation->id,
                'allocation_additional' => [],
                'memory' => (int)$request->ram,
                'swap' => 0,
                'disk' => (int)$request->disk,
                'io' => 500,
                'cpu' => (int)$request->cpu,
                'threads' => null,
                'nest_id' => (int)$egg->nest_id,
                'egg_id' => (int)$egg->id,
                'startup' => $egg->startup,
                'image' => $image,
                'oom_disabled' => false,
                'environment' => $environment,
                'database_limit' => (int)$request->databases,
                'allocation_limit' => max(0, (int)$request->ports - 1),
                'backup_limit' => 0,
                'start_on_completion' => true,
            ]);

            DB::table('pterostore_split_servers')->insert([
                'owner_id' => (int)$userId,
                'server_id' => (int)$server->id,
                'cpu_used' => (int)$request->cpu,
                'ram_used' => (int)$request->ram,
                'disk_used' => (int)$request->disk,
                'ports_used' => (int)$request->ports,
                'databases_used' => (int)$request->databases,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Server created successfully!']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[PteroStore] Create server failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Failed to create server: ' . $e->getMessage()], 500);
        }
    }

    public function servers(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $this->ensureTables();

        try {
            // Single join query instead of N+1 Server::find() calls
            $splits = DB::table('pterostore_split_servers as ps')
                ->join('servers as s', 's.id', '=', 'ps.server_id')
                ->where('ps.owner_id', $userId)
                ->select(
                    's.id', 's.uuid', 's.name', 's.status',
                    'ps.cpu_used', 'ps.ram_used', 'ps.disk_used',
                    'ps.ports_used', 'ps.databases_used'
                )
                ->get();
        } catch (\Exception $e) {
            return response()->json([]);
        }

        $result = [];
        foreach ($splits as $row) {
            $result[] = [
                'id' => $row->id,
                'uuid' => $row->uuid,
                'name' => $row->name,
                'status' => $row->status ?? 'running',
                'resources' => [
                    'cpu' => $row->cpu_used,
                    'ram' => $row->ram_used,
                    'disk' => $row->disk_used,
                    'ports' => $row->ports_used,
                    'databases' => $row->databases_used,
                ],
            ];
        }

        return response()->json($result);
    }

    public function updateServer(Request $request): JsonResponse
    {
        $request->validate([
            'server_id' => 'required|integer',
            'cpu' => 'required|integer|min:1',
            'ram' => 'required|integer|min:64',
            'disk' => 'required|integer|min:256',
            'ports' => 'sometimes|integer|min:1',
            'databases' => 'sometimes|integer|min:0',
        ]);

        $userId = $request->user()->id;
        $split = DB::table('pterostore_split_servers')
            ->where('server_id', $request->server_id)
            ->where('owner_id', $userId)
            ->first();

        if (!$split) {
            return response()->json(['error' => 'Server not found.'], 404);
        }

        $newPorts = $request->has('ports') ? (int)$request->ports : $split->ports_used;
        $newDatabases = $request->has('databases') ? (int)$request->databases : $split->databases_used;

        if ($newPorts < $split->ports_used) {
            return response()->json(['error' => "Ports cannot be lower than current value ({$split->ports_used})."], 422);
        }
        if ($newDatabases < $split->databases_used) {
            return response()->json(['error' => "Databases cannot be lower than current value ({$split->databases_used})."], 422);
        }

        $resources = DB::table('pterostore_resource_splits')
            ->where('user_id', $userId)->first();
        if (!$resources) {
            return response()->json(['error' => 'No resource allocation.'], 403);
        }

        $used = DB::table('pterostore_split_servers')
            ->where('owner_id', $userId)
            ->where('id', '!=', $split->id)
            ->selectRaw('COALESCE(SUM(cpu_used),0) as cpu, COALESCE(SUM(ram_used),0) as ram, COALESCE(SUM(disk_used),0) as disk, COALESCE(SUM(ports_used),0) as ports_sum, COALESCE(SUM(databases_used),0) as dbs_sum')
            ->first();

        $availCpu = $resources->cpu - $used->cpu;
        $availRam = $resources->ram - $used->ram;
        $availDisk = $resources->disk - $used->disk;
        $availPorts = $resources->ports - $used->ports_sum;
        $availDbs = $resources->databases - $used->dbs_sum;

        if ($request->cpu > $availCpu) return response()->json(['error' => "CPU exceeds available ({$availCpu}%)."], 422);
        if ($request->ram > $availRam) return response()->json(['error' => "RAM exceeds available ({$availRam} MB)."], 422);
        if ($request->disk > $availDisk) return response()->json(['error' => "Disk exceeds available ({$availDisk} MB)."], 422);
        if ($newPorts > $availPorts) return response()->json(['error' => "Ports exceed available ({$availPorts})."], 422);
        if ($newDatabases > $availDbs) return response()->json(['error' => "Databases exceed available ({$availDbs})."], 422);

        try {
            $server = Server::findOrFail($split->server_id);
            $server->update([
                'cpu' => $request->cpu,
                'memory' => $request->ram,
                'disk' => $request->disk,
                'allocation_limit' => max(0, $newPorts - 1),
                'database_limit' => $newDatabases,
            ]);

            DB::table('pterostore_split_servers')
                ->where('id', $split->id)
                ->update([
                    'cpu_used' => $request->cpu,
                    'ram_used' => $request->ram,
                    'disk_used' => $request->disk,
                    'ports_used' => $newPorts,
                    'databases_used' => $newDatabases,
                    'updated_at' => now(),
                ]);

            return response()->json(['message' => 'Server updated.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function deleteServer(Request $request): JsonResponse
    {
        $request->validate(['server_id' => 'required|integer']);
        $userId = $request->user()->id;

        $split = DB::table('pterostore_split_servers')
            ->where('server_id', $request->server_id)
            ->where('owner_id', $userId)
            ->first();

        if (!$split) {
            return response()->json(['error' => 'Server not found.'], 404);
        }

        try {
            $server = Server::find($split->server_id);
            if ($server) {
                try {
                    $deletionService = app(\Pterodactyl\Services\Servers\ServerDeletionService::class);
                    $deletionService->handle($server);
                } catch (\Throwable $e) {
                    // Wings may be unreachable — force delete from DB
                    try {
                        $deletionService = app(\Pterodactyl\Services\Servers\ServerDeletionService::class);
                        $deletionService->withForce()->handle($server);
                    } catch (\Throwable $e2) {
                        // Last resort: just delete the DB record
                        $server->delete();
                    }
                }
            }

            DB::table('pterostore_split_servers')->where('id', $split->id)->delete();
            return response()->json(['message' => 'Server deleted. Resources freed.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    public function serverInfo(Request $request, string $uuid): JsonResponse
    {
        $userId = $request->user()->id;
        $this->ensureTables();

        // Find the server by uuid or uuidShort
        $server = Server::where('uuid', $uuid)
            ->orWhere('uuidShort', $uuid)
            ->orWhere(DB::raw("LEFT(uuid, " . strlen($uuid) . ")"), $uuid)
            ->first();

        if (!$server) {
            return response()->json(['is_split' => false]);
        }

        // Check if this is a split server owned by the current user
        $split = DB::table('pterostore_split_servers')
            ->where('server_id', $server->id)
            ->where('owner_id', $userId)
            ->first();

        if (!$split) {
            return response()->json(['is_split' => false]);
        }

        // Use the split owner_id for resource calculations
        $ownerId = (int)$split->owner_id;

        // Get total resources and used amounts for the split owner
        $resources = DB::table('pterostore_resource_splits')
            ->where('user_id', $ownerId)->first();

        $used = DB::table('pterostore_split_servers')
            ->where('owner_id', $ownerId)
            ->selectRaw('COALESCE(SUM(cpu_used),0) as cpu, COALESCE(SUM(ram_used),0) as ram, COALESCE(SUM(disk_used),0) as disk, COALESCE(SUM(ports_used),0) as ports, COALESCE(SUM(databases_used),0) as db_count')
            ->first();

        $totalCpu = $resources ? (int)$resources->cpu : 0;
        $totalRam = $resources ? (int)$resources->ram : 0;
        $totalDisk = $resources ? (int)$resources->disk : 0;
        $totalPorts = $resources ? (int)$resources->ports : 0;
        $totalDbs = $resources ? (int)$resources->databases : 0;

        return response()->json([
            'is_split' => true,
            'id' => $split->id,
            'server_id' => (int)$split->server_id,
            'owner_id' => $ownerId,
            'cpu' => (int)$split->cpu_used,
            'ram' => (int)$split->ram_used,
            'disk' => (int)$split->disk_used,
            'ports' => (int)$split->ports_used,
            'databases' => (int)$split->databases_used,
            'free_cpu' => max(0, $totalCpu - (int)$used->cpu),
            'free_ram' => max(0, $totalRam - (int)$used->ram),
            'free_disk' => max(0, $totalDisk - (int)$used->disk),
            'free_ports' => max(0, $totalPorts - (int)$used->ports),
            'free_databases' => max(0, $totalDbs - (int)$used->db_count),
        ]);
    }
}
