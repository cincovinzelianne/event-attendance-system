<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class QrTokenService
{
    public function generateForUser(User $user): string
    {
        $payload = [
            'user_id' => (int) $user->id,
            'issued_at' => now()->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function resolveUserId(string $token): int
    {
        try {
            $json = Crypt::decryptString($token);
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($payload['user_id']) || ! is_numeric($payload['user_id'])) {
                throw new RuntimeException('Invalid token payload.');
            }

            return (int) $payload['user_id'];
        } catch (\Throwable) {
            throw new RuntimeException('Invalid or expired QR token.');
        }
    }
}
