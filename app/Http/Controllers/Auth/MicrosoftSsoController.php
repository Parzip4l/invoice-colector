<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MicrosoftSsoController extends Controller
{
    public function redirect(Request $request)
    {
        $this->ensureConfigured();

        $state = Str::random(40);
        $request->session()->put('microsoft_sso_state', $state);

        $tenantId = config('services.microsoft_sso.tenant_id');
        $parameters = [
            'client_id' => config('services.microsoft_sso.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => config('services.microsoft_sso.scopes', 'openid profile email User.Read'),
            'state' => $state,
        ];

        if (filled(config('services.microsoft_sso.domain_hint'))) {
            $parameters['domain_hint'] = config('services.microsoft_sso.domain_hint');
        }

        if ($request->boolean('silent')) {
            $parameters['prompt'] = 'none';
        } elseif (filled(config('services.microsoft_sso.prompt'))) {
            $parameters['prompt'] = config('services.microsoft_sso.prompt');
        }

        if ($request->filled('login_hint')) {
            $parameters['login_hint'] = $request->query('login_hint');
            $request->session()->put('microsoft_sso_login_hint', $request->query('login_hint'));
        }

        return redirect("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?".http_build_query($parameters));
    }

    public function callback(Request $request)
    {
        $this->ensureConfigured();

        if ($request->filled('error')) {
            Log::warning('Microsoft SSO returned an error.', [
                'error' => $request->query('error'),
                'description' => $request->query('error_description'),
            ]);

            if ($this->shouldRetryInteractive($request)) {
                return redirect()->route('auth.microsoft.redirect', [
                    'login_hint' => $request->session()->pull('microsoft_sso_login_hint'),
                ]);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Login Microsoft gagal: '.$request->query('error_description', $request->query('error')),
            ]);
        }

        if (! hash_equals((string) $request->session()->pull('microsoft_sso_state'), (string) $request->query('state'))) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi login Microsoft tidak valid. Silakan coba lagi.',
            ]);
        }

        if (! $request->filled('code')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Kode otorisasi Microsoft tidak ditemukan.',
            ]);
        }

        try {
            $token = $this->exchangeCodeForToken($request->query('code'));
            $profile = $this->fetchProfile($token['access_token']);
            $user = $this->whitelistedUser($profile);

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        } catch (\Throwable $exception) {
            Log::warning('Microsoft SSO login rejected.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => $exception->getMessage(),
            ]);
        }
    }

    private function exchangeCodeForToken(string $code): array
    {
        $tenantId = config('services.microsoft_sso.tenant_id');
        $response = Http::asForm()
            ->timeout((int) config('services.microsoft_sso.timeout', 20))
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => config('services.microsoft_sso.client_id'),
                'client_secret' => config('services.microsoft_sso.client_secret'),
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
                'scope' => config('services.microsoft_sso.scopes', 'openid profile email User.Read'),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Token Microsoft tidak valid. Silakan hubungi administrator.');
        }

        return $response->json();
    }

    private function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->timeout((int) config('services.microsoft_sso.timeout', 20))
            ->get('https://graph.microsoft.com/v1.0/me', [
                '$select' => 'id,displayName,mail,userPrincipalName',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Profil Microsoft tidak berhasil dibaca. Silakan hubungi administrator.');
        }

        return $response->json();
    }

    private function whitelistedUser(array $profile): User
    {
        $email = Str::lower($profile['mail'] ?? $profile['userPrincipalName'] ?? '');

        if ($email === '') {
            throw new \RuntimeException('Profil Microsoft tidak memiliki email.');
        }

        $allowedDomain = config('services.microsoft_sso.allowed_domain');
        if ($allowedDomain && ! Str::endsWith($email, '@'.ltrim($allowedDomain, '@'))) {
            throw new \RuntimeException('Email Microsoft tidak berada di domain yang diizinkan.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user || ! $user->is_active || ! $user->role_code) {
            throw new \RuntimeException('Akun belum masuk whitelist Invoice Collector.');
        }

        if ($user->hasRole(RoleCode::VENDOR)) {
            throw new \RuntimeException('Akun vendor tidak dapat login menggunakan Microsoft SSO internal.');
        }

        $user->forceFill([
            'name' => $profile['displayName'] ?? $user->name,
            'email_verified_at' => $user->email_verified_at ?: now(),
            'last_synced_at' => now(),
        ])->save();

        return $user;
    }

    private function redirectUri(): string
    {
        return config('services.microsoft_sso.redirect_uri') ?: route('auth.microsoft.callback');
    }

    private function shouldRetryInteractive(Request $request): bool
    {
        $silentErrors = ['login_required', 'interaction_required', 'account_selection_required'];

        return in_array($request->query('error'), $silentErrors, true)
            && hash_equals((string) $request->session()->pull('microsoft_sso_state'), (string) $request->query('state'))
            && filled($request->session()->get('microsoft_sso_login_hint'));
    }

    private function ensureConfigured(): void
    {
        abort_unless(config('services.microsoft_sso.enabled'), 404);

        foreach (['tenant_id', 'client_id', 'client_secret'] as $key) {
            if (blank(config("services.microsoft_sso.{$key}"))) {
                throw new \RuntimeException("Konfigurasi Microsoft SSO {$key} belum diisi.");
            }
        }
    }
}
