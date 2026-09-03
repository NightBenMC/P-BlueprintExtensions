<?php

namespace Pterodactyl\BlueprintFramework\Extensions\fileflow;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use GuzzleHttp\Client;

class FileFlowController extends Controller
{
    /**
     * Recursively search files on a server via Wings API.
     */
    public function searchFiles(Request $request, $server)
    {
        try {
            $query = strtolower(trim($request->query('q', '')));
            if (strlen($query) < 1) return response()->json(['results' => []]);

            $user = $request->user();
            if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

            $serverModel = ($server instanceof Server) ? $server : Server::where('uuid', $server)->orWhere('uuidShort', $server)->first();
            if (!$serverModel) return response()->json(['error' => 'Server not found'], 404);

            if (!$user->root_admin && $serverModel->owner_id !== $user->id) {
                $subuser = Subuser::where('server_id', $serverModel->id)->where('user_id', $user->id)->first();
                if (!$subuser || !in_array('file.read', $subuser->permissions)) return response()->json(['error' => 'Access denied'], 403);
            }

            $node = $serverModel->node;
            if (!$node) return response()->json(['error' => 'Node not found'], 404);

            try {
                $daemonSecret = method_exists($node, 'getDecryptedKey') ? $node->getDecryptedKey() : Crypt::decrypt($node->daemon_token);
            } catch (Exception $e) {
                $daemonSecret = $node->daemon_token;
            }

            $baseUrl = $node->getConnectionAddress();
            if (empty($baseUrl)) return response()->json(['error' => 'Node address not found'], 404);
            $maxResults = (int) ($request->query('max', 500));
            $maxDepth = (int) ($request->query('depth', 10));

            $client = new Client(['verify' => false, 'timeout' => 20, 'connect_timeout' => 10]);
            $instance = $this;

            if (session_id()) session_write_close();

            return response()->stream(function() use ($instance, $client, $baseUrl, $serverModel, $daemonSecret, $query, $maxResults, $maxDepth) {
                @set_time_limit(25);
                @ini_set('memory_limit', '512M');
                @ini_set('zlib.output_compression', 0);
                @ini_set('implicit_flush', 1);
                while (ob_get_level() > 0) ob_end_flush();

                $scanned = 0;
                $resultsCount = 0;
                $startTime = time();
                try {
                    $instance->searchDirectoryStream($client, $baseUrl, $serverModel->uuid, $daemonSecret, '/', $query, $resultsCount, $scanned, $maxResults, $maxDepth, 0, $startTime);
                } catch (Exception $e) {
                    echo json_encode(['error' => 'Connection to Wings failed: ' . $e->getMessage()]) . "\n";
                }
                echo json_encode(['final' => true, 'scanned' => $scanned]) . "\n";
            }, 200, [
                'Content-Type' => 'application/x-ndjson',
                'X-Accel-Buffering' => 'no',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchDirectoryStream($client, $baseUrl, $serverUuid, $token, $dir, $query, &$resultsCount, &$scanned, $maxResults, $maxDepth, $depth, $startTime): void
    {
        if ($resultsCount >= $maxResults || $depth > $maxDepth || connection_aborted() || (time() - $startTime) > 20) return;

        $baseUrl = rtrim($baseUrl, '/');
        if (preg_match('/^([0-9a-fA-F]{0,4}:){2,7}[0-9a-fA-F]{0,4}$/', $baseUrl)) { $baseUrl = '[' . $baseUrl . ']'; }
        elseif (preg_match('/^https?:\/\/(([0-9a-fA-F]{0,4}:){2,7}[0-9a-fA-F]{0,4})(:\d+)?/', $baseUrl, $matches)) { $baseUrl = str_replace($matches[1], '[' . $matches[1] . ']', $baseUrl); }
        if (!str_starts_with($baseUrl, 'http')) { $baseUrl = 'https://' . $baseUrl; }

        try {
            $response = $client->get("{$baseUrl}/api/servers/{$serverUuid}/files/list-directory", [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                'query' => ['directory' => $dir],
                'timeout' => 10,
            ]);

            $files = json_decode($response->getBody(), true);
            if (!is_array($files)) return;

            $pattern = preg_quote($query, "/");
            $pattern = str_replace("\\*", ".*", $pattern);
            foreach ($files as $file) {
                if ($resultsCount >= $maxResults || connection_aborted()) return;

                $name = $file['name'] ?? '';
                if (str_starts_with($name, '.')) continue;

                $isFile = isset($file['file']) ? (bool)$file['file'] : !($file['is_directory'] ?? false);
                $path = ($dir === '/' ? '/' : $dir . '/') . $name;

                $matches = (stripos($name, $query) !== false) || @preg_match("/$pattern/i", $name);

                if ($matches) {
                    $item = [
                        'name' => $name,
                        'path' => $path,
                        'is_file' => $isFile,
                        'size' => $file['size'] ?? 0,
                        'modified' => $file['modified'] ?? null,
                    ];
                    $resultsCount++;
                    echo json_encode(['match' => $item, 'scanned' => $scanned]) . "\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }

                $scanned++;
                if ($scanned % 10 === 0) {
                    echo json_encode(['progress' => true, 'scanned' => $scanned]) . "\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }

                if (!$isFile && $depth < $maxDepth) {
                    $this->searchDirectoryStream($client, $baseUrl, $serverUuid, $token, $path, $query, $resultsCount, $scanned, $maxResults, $maxDepth, $depth + 1, $startTime);
                }
            }
        } catch (Exception $e) {
            if ($depth === 0) echo json_encode(['error' => $e->getMessage()]) . "\n";
        }
    }

    /**
     * Manage Quick Commands
     */
    public function getCommands(Request $request, $server): JsonResponse
    {
        try {
            $serverModel = ($server instanceof Server) ? $server : Server::where('uuid', $server)->orWhere('uuidShort', $server)->first();
            if (!$serverModel) return response()->json(['error' => 'Server not found'], 404);

            $commands = DB::table('fileflow_commands')
                ->where('server_id', $serverModel->uuid)
                ->where('user_id', $request->user()->id)
                ->get();
            return response()->json(['commands' => $commands]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addCommand(Request $request, $server): JsonResponse
    {
        try {
            $serverModel = ($server instanceof Server) ? $server : Server::where('uuid', $server)->orWhere('uuidShort', $server)->first();
            if (!$serverModel) return response()->json(['error' => 'Server not found'], 404);

            $validated = $request->validate([
                'label' => 'required|string|max:100',
                'command' => 'required|string|max:1000',
                'v1_name' => 'nullable|string|max:50',
                'v1_default' => 'nullable|string|max:100',
                'v2_name' => 'nullable|string|max:50',
                'v2_default' => 'nullable|string|max:100',
                'v3_name' => 'nullable|string|max:50',
                'v3_default' => 'nullable|string|max:100',
            ]);

            DB::table('fileflow_commands')->insert([
                'server_id' => $serverModel->uuid,
                'user_id' => $request->user()->id,
                'label' => $validated['label'],
                'command' => $validated['command'],
                'v1_name' => $validated['v1_name'],
                'v1_default' => $validated['v1_default'],
                'v2_name' => $validated['v2_name'],
                'v2_default' => $validated['v2_default'],
                'v3_name' => $validated['v3_name'],
                'v3_default' => $validated['v3_default'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteCommand(Request $request, $server, int $id): JsonResponse
    {
        try {
            $serverModel = ($server instanceof Server) ? $server : Server::where('uuid', $server)->orWhere('uuidShort', $server)->first();
            if (!$serverModel) return response()->json(['error' => 'Server not found'], 404);

            DB::table('fileflow_commands')
                ->where('id', $id)
                ->where('server_id', $serverModel->uuid)
                ->where('user_id', $request->user()->id)
                ->delete();
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
