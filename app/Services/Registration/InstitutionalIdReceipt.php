<?php

namespace App\Services\Registration;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/** Short-lived, authenticated evidence bound to category and exact upload bytes. */
class InstitutionalIdReceipt
{
    public function issue(UploadedFile $file, string $category, array $evidence): string
    {
        return Crypt::encryptString(json_encode([
            'policy' => InstitutionalIdRules::POLICY, 'sha256' => hash_file('sha256', $file->getRealPath()),
            'category' => $category, 'expires_at' => now()->addMinutes(20)->timestamp, 'evidence' => $evidence,
        ], JSON_THROW_ON_ERROR));
    }

    public function read(?string $receipt, UploadedFile $file, string $category): array
    {
        try {
            $data = json_decode(Crypt::decryptString((string) $receipt), true, 32, JSON_THROW_ON_ERROR);
            if (($data['policy'] ?? '') !== InstitutionalIdRules::POLICY || ($data['category'] ?? '') !== $category
                || ($data['expires_at'] ?? 0) <= now()->timestamp
                || ! hash_equals($data['sha256'] ?? '', hash_file('sha256', $file->getRealPath()))) {
                throw new \RuntimeException('Receipt does not match this upload.');
            }

            return $data['evidence'];
        } catch (\Throwable $error) {
            throw ValidationException::withMessages(['id_check_receipt' => 'Read the current ID again. The previous result expired, changed, or does not match this file/category.']);
        }
    }
}
