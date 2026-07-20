<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Services\Auth\LdapAuthenticator;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower($this->input('email'));
        $password = (string) $this->input('password');
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user || ! $user->is_active || ! $user->role_code) {
            $this->failedLogin('Account is inactive or not whitelisted.');
        }

        if ($user->hasRole(RoleCode::VENDOR)) {
            if (! $this->authenticateLocalUser($user, $password)) {
                $this->failedLogin(__('auth.failed'));
            }

            $this->completeLogin($user);

            return;
        }

        if ($this->ldapLoginEnabled()) {
            try {
                if (! app(LdapAuthenticator::class)->attempt($email, $password)) {
                    $this->failedLogin(__('auth.failed'));
                }

                $this->completeLogin($user);

                return;
            } catch (Throwable $exception) {
                report($exception);

                if (! $this->localFallbackEnabled() || ! $this->authenticateLocalUser($user, $password)) {
                    $this->failedLogin('LDAP server error. Please contact administrator.');
                }

                $this->completeLogin($user);

                return;
            }
        }

        if (! $this->localFallbackEnabled() || ! $this->authenticateLocalUser($user, $password)) {
            $this->failedLogin(__('auth.failed'));
        }

        $this->completeLogin($user);
    }

    private function authenticateLocalUser(User $user, string $password): bool
    {
        return is_string($user->password)
            && $user->password !== ''
            && Hash::check($password, $user->password);
    }

    private function completeLogin(User $user): void
    {
        Auth::login($user, $this->filled('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    private function failedLogin(string $message): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }

    private function ldapLoginEnabled(): bool
    {
        return filter_var(config('ldap.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function localFallbackEnabled(): bool
    {
        return filter_var(config('ldap.local_fallback', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('email')) . '|' . $this->ip();
    }
}
