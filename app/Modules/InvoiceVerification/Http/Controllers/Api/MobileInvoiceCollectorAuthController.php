<?php

namespace App\Modules\InvoiceVerification\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileInvoiceCollectorToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileInvoiceCollectorAuthController extends Controller
{
    public function exchange(Request $request): JsonResponse
    {
        $secret = (string) config('invoice_verification.mobile_sso.shared_secret');
        if ($secret === '') {
            return response()->json(['success' => false, 'message' => 'Invoice Collector mobile SSO belum dikonfigurasi.'], 503);
        }

        $timestamp = (int) $request->header('X-Invoice-Timestamp');
        $signature = (string) $request->header('X-Invoice-Signature');
        $tolerance = (int) config('invoice_verification.mobile_sso.signature_tolerance_seconds', 300);
        if ($timestamp <= 0 || abs(now()->timestamp - $timestamp) > $tolerance) {
            return response()->json(['success' => false, 'message' => 'SSO exchange timestamp tidak valid.'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) {
            return response()->json(['success' => false, 'message' => 'SSO exchange signature tidak valid.'], 401);
        }

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:150',
            'device_id' => 'nullable|string|max:120',
            'device_name' => 'nullable|string|max:150',
            'platform' => 'nullable|in:android,ios,web',
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])->first();
        if (! $user || ! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'User belum aktif atau belum terdaftar di Invoice Collector.'], 403);
        }

        $plainToken = Str::random(80);
        $expiresAt = now()->addMinutes((int) config('invoice_verification.mobile_sso.token_ttl_minutes', 129600));
        MobileInvoiceCollectorToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'client_name' => 'lrtj-space',
            'device_id' => $data['device_id'] ?? null,
            'device_name' => $data['device_name'] ?? null,
            'platform' => $data['platform'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        return response()->json(['success' => true, 'data' => [
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role_code?->value ?? $user->role_code,
            ],
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_invoice_collector_token')?->delete();

        return response()->json(['success' => true, 'message' => 'Session Invoice Collector mobile dihapus.']);
    }
}
