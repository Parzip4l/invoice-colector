@extends('layouts.base', ['subtitle' => 'Login'])

@section('body-attribuet')
class="authentication-bg"
@endsection

@section('content')
<div class="account-pages py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center">
                            <div class="mx-auto mb-4 text-center auth-logo">
                                <a href="{{ route('any', 'index') }}" class="logo-dark">
                                    <img src="/images/logo.png" height="84" alt="logo dark">
                                </a>

                                <a href="{{ route('any', 'index') }}" class="logo-light">
                                    <img src="/images/logo.png" height="84" alt="logo light">
                                </a>
                            </div>
                            
                        </div>
                        @if (session('status'))
                            <div class="alert alert-warning border-0 shadow-sm mt-4" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm mt-4" role="alert">
                                <div class="d-flex gap-2">
                                    <iconify-icon icon="solar:danger-triangle-outline" class="fs-20 flex-shrink-0"></iconify-icon>
                                    <div>
                                        <div class="fw-semibold mb-1">Login gagal</div>
                                        <div>{{ $errors->first() }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (config('services.microsoft_sso.enabled'))
                            <div class="d-grid mt-4">
                                <a href="{{ route('auth.microsoft.redirect') }}"
                                    class="btn btn-light border btn-lg fw-semibold d-inline-flex align-items-center justify-content-center gap-3">
                                    <span class="d-inline-grid" style="grid-template-columns: repeat(2, 10px); gap: 2px;">
                                        <span style="width: 10px; height: 10px; background: #f25022;"></span>
                                        <span style="width: 10px; height: 10px; background: #7fba00;"></span>
                                        <span style="width: 10px; height: 10px; background: #00a4ef;"></span>
                                        <span style="width: 10px; height: 10px; background: #ffb900;"></span>
                                    </span>
                                    <span>Sign in with Microsoft</span>
                                </a>
                            </div>

                            <div class="d-flex align-items-center gap-3 my-4 text-muted small fw-semibold">
                                <span class="border-top flex-grow-1"></span>
                                <span>or use LDAP / vendor account</span>
                                <span class="border-top flex-grow-1"></span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="{{ config('services.microsoft_sso.enabled') ? '' : 'mt-4' }}">

                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}"
                                    placeholder="Enter your email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="{{ route('second', ['auth', 'password']) }}"
                                        class="text-decoration-none small text-muted">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                                        placeholder="Enter your password">
                                    <button class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" type="button" id="toggle-password" aria-label="Show password" aria-pressed="false">
                                        <iconify-icon icon="solar:eye-outline" class="fs-20"></iconify-icon>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="remember-me" name="remember" value="1">
                                <label class="form-check-label" for="remember-me">Remember me</label>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-auth-primary btn-lg fw-semibold d-inline-flex align-items-center justify-content-center gap-2" type="submit">
                                    <span>Sign In</span>
                                    <iconify-icon icon="solar:login-3-outline" class="fs-20"></iconify-icon>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('toggle-password');

        if (!passwordInput || !toggleButton) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            toggleButton.innerHTML = '<iconify-icon icon="' + (isHidden ? 'solar:eye-closed-outline' : 'solar:eye-outline') + '" class="fs-20"></iconify-icon>';
        });
    });
</script>
@endsection
