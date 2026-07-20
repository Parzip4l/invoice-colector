<?php $__env->startSection('body-attribuet'); ?>
class="authentication-bg"
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="account-pages py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center">
                            <div class="mx-auto mb-4 text-center auth-logo">
                                <a href="<?php echo e(route('any', 'index')); ?>" class="logo-dark">
                                    <img src="/images/logo.png" height="84" alt="logo dark">
                                </a>

                                <a href="<?php echo e(route('any', 'index')); ?>" class="logo-light">
                                    <img src="/images/logo.png" height="84" alt="logo light">
                                </a>
                            </div>
                            
                        </div>
                        <form method="POST" action="<?php echo e(route('login')); ?>" class="mt-4">

                            <?php echo csrf_field(); ?>

                            <?php if(session('status')): ?>
                                <div class="alert alert-warning border-0 shadow-sm" role="alert">
                                    <?php echo e(session('status')); ?>

                                </div>
                            <?php endif; ?>

                            <?php if($errors->any()): ?>
                                <div class="alert alert-danger border-0 shadow-sm" role="alert">
                                    <div class="d-flex gap-2">
                                        <iconify-icon icon="solar:danger-triangle-outline" class="fs-20 flex-shrink-0"></iconify-icon>
                                        <div>
                                            <div class="fw-semibold mb-1">Login gagal</div>
                                            <div><?php echo e($errors->first()); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="email" name="email" value="<?php echo e(old('email')); ?>"
                                    placeholder="Enter your email">
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="<?php echo e(route('second', ['auth', 'password'])); ?>"
                                        class="text-decoration-none small text-muted">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <input type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="password" name="password"
                                        placeholder="Enter your password">
                                    <button class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" type="button" id="toggle-password" aria-label="Show password" aria-pressed="false">
                                        <iconify-icon icon="solar:eye-outline" class="fs-20"></iconify-icon>
                                    </button>
                                </div>
                                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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

                        <div class="alert alert-info border-0 mt-4 mb-0" role="alert">
                            <div class="fw-semibold mb-2">Akun demo setelah reset database</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted">Vendor Eksternal</td>
                                            <td><code>vendor@demo.local</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Vendor Internal</td>
                                            <td><code>user.divisi@demo.local</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Accounting</td>
                                            <td><code>akuntansi@demo.local</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Finance</td>
                                            <td><code>finance@demo.local</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="small mt-2">Password semua akun: <code>password</code></div>
                        </div>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', ['subtitle' => 'Login'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/auth/signin.blade.php ENDPATH**/ ?>