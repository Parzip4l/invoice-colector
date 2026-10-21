@extends('layouts.base', ['subtitle' => 'Login'])

@section('body-attribuet')
class="signal-login-page"
@endsection

@section('content')
<style>
    :root {
        --login-red: #ed1c24;
        --login-red-dark: #c9161d;
        --login-red-soft: #fdeced;
        --login-ink: #252836;
        --login-muted: #74676a;
        --login-line: #eadfe0;
        --login-surface: #fffafa;
    }

    .signal-login-page { min-height: 100vh; margin: 0; background: #f4eff0; color: var(--login-ink); }
    .signal-login-shell { min-height: 100vh; display: grid; place-items: center; padding: 28px; }
    .signal-login-card {
        width: min(1180px, 100%);
        min-height: 690px;
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(430px, .92fr);
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(71, 44, 49, .09);
        border-radius: 28px;
        box-shadow: 0 26px 70px rgba(64, 35, 40, .12);
    }

    .signal-login-visual {
        position: relative;
        display: flex;
        flex-direction: column;
        padding: 44px 48px 34px;
        overflow: hidden;
        background: var(--login-surface);
        border-right: 1px solid var(--login-line);
    }

    .visual-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        width: fit-content;
        color: var(--login-ink);
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .visual-brand-mark { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: var(--login-red); color: #fff; font-size: 18px; }
    .signal-login-visual h1 { max-width: 540px; margin: 44px 0 14px; color: var(--login-ink); font-size: clamp(34px, 4vw, 52px); line-height: 1.08; letter-spacing: -.04em; }
    .signal-login-visual h1 span { color: var(--login-red); }
    .signal-login-visual > p { max-width: 500px; margin: 0; color: var(--login-muted); font-size: 15px; line-height: 1.7; }
    .invoice-login-art { width: 100%; max-height: 380px; flex: 1 1 auto; margin: 16px auto 0; object-fit: contain; }

    .visual-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; }
    .visual-meta span { display: inline-flex; align-items: center; gap: 7px; padding: 8px 12px; border: 1px solid var(--login-line); border-radius: 999px; background: #fff; color: #6d5e61; font-size: 11px; font-weight: 700; }
    .visual-meta iconify-icon { color: var(--login-red); font-size: 15px; }

    .signal-login-form-panel { display: flex; align-items: center; padding: 54px clamp(38px, 5vw, 72px); background: #fff; }
    .signal-login-form { width: 100%; max-width: 430px; margin: 0 auto; }
    .login-logo { display: block; width: 145px; height: auto; margin-bottom: 46px; }
    .login-eyebrow { margin-bottom: 9px; color: var(--login-red); font-size: 11px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .signal-login-form h2 { margin: 0 0 9px; color: var(--login-ink); font-size: 31px; letter-spacing: -.03em; }
    .login-lead { margin: 0 0 30px; color: var(--login-muted); font-size: 14px; line-height: 1.65; }

    .signal-alert { margin-bottom: 18px; padding: 13px 15px; border: 1px solid #f2cfd1; border-radius: 12px; background: var(--login-red-soft); color: #8e1c21; font-size: 13px; }
    .microsoft-login { min-height: 52px; border: 1px solid #d9dfe6; border-radius: 12px; background: #fff; color: var(--login-ink); transition: border-color .18s ease, background-color .18s ease, transform .18s ease; }
    .microsoft-login:hover { border-color: #aeb7c2; background: #f8f9fa; color: var(--login-ink); transform: translateY(-1px); }
    .microsoft-mark { display: grid; grid-template-columns: repeat(2, 9px); gap: 2px; }
    .microsoft-mark i { display: block; width: 9px; height: 9px; }

    .login-divider { display: flex; align-items: center; gap: 14px; margin: 25px 0; color: #9b8f91; font-size: 11px; font-weight: 700; }
    .login-divider::before, .login-divider::after { content: ''; height: 1px; flex: 1; background: var(--login-line); }
    .signal-login-form .form-label { margin-bottom: 7px; color: #493e40; font-size: 12px; font-weight: 700; }
    .signal-login-form .form-control { min-height: 50px; border: 1px solid #ded5d6; border-radius: 11px; padding: 0 15px; color: var(--login-ink); font-size: 13px; box-shadow: none; }
    .signal-login-form .form-control:focus { border-color: var(--login-red); box-shadow: 0 0 0 3px rgba(237, 28, 36, .09); }
    .signal-login-form .form-control::placeholder { color: #b1a7a9; }

    .password-wrap { position: relative; }
    .password-wrap .form-control { padding-right: 52px; }
    .password-toggle { position: absolute; z-index: 3; top: 1px; right: 1px; width: 49px; height: 48px; border: 0; border-left: 1px solid #e7dddf; border-radius: 0 10px 10px 0; background: transparent; color: #776a6d; }
    .login-options { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: 16px 0 22px; }
    .login-options .form-check-label, .login-options a { color: #74676a; font-size: 12px; }
    .login-options a:hover { color: var(--login-red); }
    .form-check-input:checked { background-color: var(--login-red); border-color: var(--login-red); }

    .login-submit { min-height: 51px; border: 1px solid var(--login-red); border-radius: 11px; background: var(--login-red); color: #fff; font-size: 13px; transition: background-color .18s ease, border-color .18s ease, transform .18s ease; }
    .login-submit:hover { background: var(--login-red-dark); border-color: var(--login-red-dark); color: #fff; transform: translateY(-1px); }
    .login-help { margin: 24px 0 0; color: #9a8d8f; font-size: 11px; line-height: 1.6; text-align: center; }

    @media (max-width: 960px) {
        .signal-login-shell { padding: 18px; }
        .signal-login-card { grid-template-columns: 1fr; min-height: auto; max-width: 560px; }
        .signal-login-visual { min-height: 265px; padding: 30px 34px; border-right: 0; border-bottom: 1px solid var(--login-line); }
        .signal-login-visual h1 { max-width: 430px; margin-top: 27px; font-size: 34px; }
        .signal-login-visual > p { max-width: 430px; }
        .invoice-login-art { position: absolute; right: -35px; bottom: -38px; width: 330px; height: 240px; opacity: .34; }
        .visual-meta { display: none; }
        .signal-login-form-panel { padding: 40px 34px 44px; }
        .login-logo { margin-bottom: 30px; }
    }

    @media (max-width: 520px) {
        .signal-login-shell { display: block; min-height: 100vh; padding: 0; }
        .signal-login-card { min-height: 100vh; border: 0; border-radius: 0; box-shadow: none; }
        .signal-login-visual { min-height: 218px; padding: 25px 24px; }
        .signal-login-visual h1 { margin-top: 22px; font-size: 28px; }
        .signal-login-visual > p { font-size: 13px; line-height: 1.55; }
        .invoice-login-art { width: 250px; height: 190px; }
        .signal-login-form-panel { align-items: flex-start; padding: 32px 24px 40px; }
        .login-logo { width: 132px; margin-bottom: 27px; }
        .signal-login-form h2 { font-size: 27px; }
    }
</style>

<main class="signal-login-shell">
    <section class="signal-login-card" aria-label="Login SIGNAL Invoice Verification Control">
        <aside class="signal-login-visual">
            <div class="visual-brand">
                <span class="visual-brand-mark"><iconify-icon icon="solar:document-text-bold"></iconify-icon></span>
                <span>SIGNAL</span>
            </div>
            <h1>Invoice terkendali,<br><span>proses lebih pasti.</span></h1>
            <p>Satu ruang kerja untuk mengumpulkan dokumen, memantau approval, dan memastikan setiap invoice selesai tepat tahap.</p>
            <img src="/images/invoice-collector-login.svg?v=20261021-1" class="invoice-login-art" alt="Ilustrasi alur verifikasi dan persetujuan invoice">
            
        </aside>

        <div class="signal-login-form-panel">
            <div class="signal-login-form">
                <img src="/images/lrt-jakarta-logo.png?v=20261020-1" class="login-logo" alt="LRT Jakarta">
                <div class="login-eyebrow">Invoice Verification Control</div>
                <h2>Selamat datang</h2>
                <p class="login-lead">Masuk dengan akun Microsoft 365 LRT Jakarta atau akun vendor yang telah didaftarkan.</p>

                @if (session('status'))
                    <div class="signal-alert" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="signal-alert" role="alert">
                        <div class="d-flex gap-2">
                            <iconify-icon icon="solar:danger-triangle-outline" class="fs-18 flex-shrink-0"></iconify-icon>
                            <div><strong>Login gagal.</strong> {{ $errors->first() }}</div>
                        </div>
                    </div>
                @endif

                @if (config('services.microsoft_sso.enabled'))
                    <div class="d-grid">
                        <a href="{{ route('auth.microsoft.redirect') }}" class="btn microsoft-login fw-semibold d-inline-flex align-items-center justify-content-center gap-3">
                            <span class="microsoft-mark" aria-hidden="true">
                                <i style="background:#f25022"></i><i style="background:#7fba00"></i>
                                <i style="background:#00a4ef"></i><i style="background:#ffb900"></i>
                            </span>
                            <span>Masuk dengan Microsoft 365</span>
                        </a>
                    </div>
                    <div class="login-divider">atau gunakan akun vendor / LDAP</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="{{ config('services.microsoft_sso.enabled') ? '' : 'mt-4' }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Alamat email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="nama@lrtjakarta.co.id" autocomplete="username" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-0">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrap">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button class="password-toggle d-inline-flex align-items-center justify-content-center" type="button" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                                <iconify-icon icon="solar:eye-outline" class="fs-19"></iconify-icon>
                            </button>
                        </div>
                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="login-options">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="remember-me" name="remember" value="1">
                            <label class="form-check-label" for="remember-me">Ingat saya</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-decoration-none">Lupa password?</a>
                    </div>

                    <div class="d-grid">
                        <button class="btn login-submit fw-semibold d-inline-flex align-items-center justify-content-center gap-2" type="submit">
                            <span>Masuk ke SIGNAL</span>
                            <iconify-icon icon="solar:login-3-outline" class="fs-18"></iconify-icon>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('toggle-password');

        if (!passwordInput || !toggleButton) return;

        toggleButton.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
            toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            toggleButton.innerHTML = '<iconify-icon icon="' + (isHidden ? 'solar:eye-closed-outline' : 'solar:eye-outline') + '" class="fs-19"></iconify-icon>';
        });
    });
</script>
@endsection
