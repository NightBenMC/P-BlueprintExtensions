<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\fileflow;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;

class fileflowExtensionController extends Controller
{
    public BlueprintExtensionLibrary $blueprint;

    public function __construct(BlueprintExtensionLibrary $blueprint)
    {
        $this->blueprint = $blueprint;
    }

    public function index(Request $request)
    {
        return view('admin.extensions.fileflow.index', [
            'blueprint' => $this->blueprint,
            'root' => $request->user()->root_admin,
            'registration_enabled' => $this->blueprint->dbGet('fileflow', 'registration_enabled') ?? '1',
            'rate_limit_max' => $this->blueprint->dbGet('fileflow', 'rate_limit_max') ?? '3',
            'rate_limit_window' => $this->blueprint->dbGet('fileflow', 'rate_limit_window') ?? '3600',
            'recaptcha_enabled' => $this->blueprint->dbGet('fileflow', 'recaptcha_enabled') ?? '0',
            'recaptcha_site_key' => $this->blueprint->dbGet('fileflow', 'recaptcha_site_key') ?? '',
            'recaptcha_secret_key' => $this->blueprint->dbGet('fileflow', 'recaptcha_secret_key') ?? '',
            'email_domain_whitelist' => $this->blueprint->dbGet('fileflow', 'email_domain_whitelist') ?? '',
            'email_domain_blacklist' => $this->blueprint->dbGet('fileflow', 'email_domain_blacklist') ?? '',
            'require_email_verification' => $this->blueprint->dbGet('fileflow', 'require_email_verification') ?? '0',
            'password_min_length' => $this->blueprint->dbGet('fileflow', 'password_min_length') ?? '8',
            'anim_speed' => $this->blueprint->dbGet('fileflow', 'anim_speed') ?? '0.35',
            'anim_stagger' => $this->blueprint->dbGet('fileflow', 'anim_stagger') ?? '0.04',
            'row_gap' => $this->blueprint->dbGet('fileflow', 'row_gap') ?? '6',
            'max_depth' => $this->blueprint->dbGet('fileflow', 'max_depth') ?? '2',
            'skip_shortcut' => $this->blueprint->dbGet('fileflow', 'skip_shortcut') ?? 'x',
            'custom_icons' => $this->blueprint->dbGet('fileflow', 'custom_icons') ?? '{}',
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (!$request->user() || !$request->user()->root_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'anim_speed' => 'nullable|string|max:10',
            'anim_stagger' => 'nullable|string|max:10',
            'row_gap' => 'nullable|string|max:10',
            'max_depth' => 'nullable|integer|min:1|max:5',
            'skip_shortcut' => 'nullable|string|max:10',
            'custom_icons' => 'nullable|string|max:10000',
            'registration_enabled' => 'required|in:0,1',
            'rate_limit_max' => 'required|integer|min:1|max:100',
            'rate_limit_window' => 'required|integer|min:60|max:86400',
            'recaptcha_enabled' => 'required|in:0,1',
            'recaptcha_site_key' => 'nullable|string|max:255',
            'recaptcha_secret_key' => 'nullable|string|max:255',
            'email_domain_whitelist' => 'nullable|string|max:1000',
            'email_domain_blacklist' => 'nullable|string|max:1000',
            'require_email_verification' => 'required|in:0,1',
            'password_min_length' => 'required|integer|min:6|max:64',
        ]);

        foreach ($validated as $key => $value) {
            $this->blueprint->dbSet('fileflow', $key, $value ?? '');
        }

        return redirect()->route('admin.extensions.fileflow.index')
            ->with('success', 'Settings saved successfully.');
    }

    public function post(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->root_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $action = $request->input('action');

        if ($action === 'register_user') {
            return $this->handleRegistration($request);
        }

        return response()->json(['error' => 'Unknown action'], 400);
    }

    private function handleRegistration(Request $request): JsonResponse
    {
        // Check if registration is enabled
        $enabled = $this->blueprint->dbGet('fileflow', 'registration_enabled') ?? '1';
        if ($enabled !== '1') {
            return response()->json(['error' => 'Registration is currently disabled.'], 403);
        }

        // Rate limiting
        $maxAttempts = (int) ($this->blueprint->dbGet('fileflow', 'rate_limit_max') ?? 3);
        $windowSeconds = (int) ($this->blueprint->dbGet('fileflow', 'rate_limit_window') ?? 3600);
        $ip = $request->ip();
        $cacheKey = 'fileflow_rate_' . md5($ip);
        $attempts = (int) cache()->get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'error' => "Too many registration attempts. Please try again later.",
            ], 429);
        }

        cache()->put($cacheKey, $attempts + 1, $windowSeconds);

        // reCAPTCHA verification
        $recaptchaEnabled = $this->blueprint->dbGet('fileflow', 'recaptcha_enabled') ?? '0';
        if ($recaptchaEnabled === '1') {
            $recaptchaSecret = $this->blueprint->dbGet('fileflow', 'recaptcha_secret_key') ?? '';
            $recaptchaToken = $request->input('recaptcha_token');

            if (empty($recaptchaToken)) {
                return response()->json(['error' => 'reCAPTCHA verification required.'], 422);
            }

            $verifyResponse = file_get_contents(
                'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($recaptchaSecret)
                . '&response=' . urlencode($recaptchaToken)
                . '&remoteip=' . urlencode($ip)
            );
            $verifyData = json_decode($verifyResponse, true);

            if (!$verifyData || !($verifyData['success'] ?? false)) {
                return response()->json(['error' => 'reCAPTCHA verification failed.'], 422);
            }
        }

        // Input validation
        $minPassword = (int) ($this->blueprint->dbGet('fileflow', 'password_min_length') ?? 8);
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'first_name' => 'required|string|min:1|max:100',
            'last_name' => 'required|string|min:1|max:100',
            'password' => "required|string|min:$minPassword|max:255",
        ], [
            'username.regex' => 'Username may only contain letters, numbers, and underscores.',
            'email.unique' => 'This email address is already registered.',
        ]);

        // Email domain validation
        $email = $request->input('email');
        $emailDomain = strtolower(substr($email, strrpos($email, '@') + 1));

        $whitelist = $this->blueprint->dbGet('fileflow', 'email_domain_whitelist') ?? '';
        if (!empty($whitelist)) {
            $allowedDomains = array_map('trim', array_map('strtolower', explode(',', $whitelist)));
            if (!in_array($emailDomain, $allowedDomains)) {
                return response()->json([
                    'error' => 'Registration is not allowed with this email domain.',
                ], 422);
            }
        }

        $blacklist = $this->blueprint->dbGet('fileflow', 'email_domain_blacklist') ?? '';
        if (!empty($blacklist)) {
            $blockedDomains = array_map('trim', array_map('strtolower', explode(',', $blacklist)));
            if (in_array($emailDomain, $blockedDomains)) {
                return response()->json([
                    'error' => 'Registration is not allowed with this email domain.',
                ], 422);
            }
        }

        // Sanitize inputs
        $firstName = strip_tags(trim($request->input('first_name')));
        $lastName = strip_tags(trim($request->input('last_name')));
        $username = strip_tags(trim($request->input('username')));

        // Create user via Pterodactyl's UserCreationService
        try {
            $connection = app(\Illuminate\Database\ConnectionInterface::class);
            $hasher = app(\Illuminate\Contracts\Hashing\Hasher::class);
            $passwordBroker = app(\Illuminate\Contracts\Auth\PasswordBroker::class);
            $repository = app(\Pterodactyl\Contracts\Repository\UserRepositoryInterface::class);

            $service = new \Pterodactyl\Services\Users\UserCreationService(
                $connection, $hasher, $passwordBroker, $repository
            );

            $user = $service->handle([
                'email' => $email,
                'username' => $username,
                'name_first' => $firstName,
                'name_last' => $lastName,
                'password' => $request->input('password'),
                'root_admin' => false,
            ]);

            return response()->json([
                'message' => 'Account created successfully. You can now log in.',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Registration failed. Please try again later.',
            ], 500);
        }
    }
}
