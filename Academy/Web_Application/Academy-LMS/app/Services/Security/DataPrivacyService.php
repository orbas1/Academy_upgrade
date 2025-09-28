<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\DeviceIp;
use App\Models\Enrollment;
use App\Models\OfflinePayment;
use App\Models\Payment_history;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataPrivacyService
{
    public function export(User $user): array
    {
        $enrollments = Enrollment::with('course:id,title')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'course_title' => $enrollment->course?->title,
                'enrollment_type' => $enrollment->enrollment_type,
                'entry_date' => $enrollment->entry_date,
                'expiry_date' => $enrollment->expiry_date,
                'created_at' => $enrollment->created_at,
                'updated_at' => $enrollment->updated_at,
            ])->all();

        $payments = Payment_history::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($payment) => $payment->toArray())
            ->all();

        $offlinePayments = OfflinePayment::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(function (OfflinePayment $payment) {
                $payload = $payment->toArray();
                return Arr::except($payload, ['doc']);
            })
            ->all();

        $deviceSessions = DeviceIp::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($device) => $device->toArray())
            ->all();

        $auditTrail = AuditLog::query()
            ->where('user_id', $user->id)
            ->latest('performed_at')
            ->limit(100)
            ->get()
            ->map(fn ($log) => $log->toArray())
            ->all();

        return [
            'exported_at' => now()->toIso8601String(),
            'user' => Arr::except($user->fresh()->toArray(), ['password', 'two_factor_secret', 'remember_token']),
            'enrollments' => $enrollments,
            'payments' => $payments,
            'offline_payments' => $offlinePayments,
            'device_sessions' => $deviceSessions,
            'audit_trail' => $auditTrail,
        ];
    }

    public function exportToDisk(User $user, ?string $path = null): string
    {
        $payload = $this->export($user);
        $disk = Storage::disk(config('compliance.export_disk'));
        $targetPath = $path ?: sprintf(
            'compliance/exports/user-%d-%s.json',
            $user->id,
            now()->format('YmdHis') . '-' . Str::lower(Str::random(8))
        );

        $disk->put($targetPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $targetPath;
    }

    public function erase(User $user): void
    {
        DB::transaction(function () use ($user) {
            $anonymizedEmail = sprintf('anonymized+%d@redacted.local', $user->id);

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => $anonymizedEmail,
                'social_links' => null,
                'skills' => null,
                'about' => null,
                'photo' => null,
                'password' => Hash::make(Str::random(40)),
            ])->save();

            $user->clearTwoFactorCredentials();

            DeviceIp::where('user_id', $user->id)->delete();

            OfflinePayment::where('user_id', $user->id)->update([
                'phone_no' => null,
                'bank_no' => null,
                'doc' => null,
            ]);

            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', User::class)
                ->delete();

            AuditLog::create([
                'user_id' => $user->id,
                'actor_role' => 'system',
                'action' => 'user.erasure',
                'metadata' => [
                    'reason' => 'gdpr_erasure_request',
                    'performed_by' => 'system',
                ],
                'performed_at' => now(),
            ]);
        });
    }
}
