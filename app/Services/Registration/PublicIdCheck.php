<?php

namespace App\Services\Registration;

/** Internal OCR diagnostics belong in the authorized staff review, not public forms. */
final class PublicIdCheck
{
    public static function summary(array $result): array
    {
        return ['status' => $result['status'], 'message' => match ($result['status']) {
            'matched' => 'Review the suggested details against your ID before continuing.',
            'needs_correction' => 'The document and submitted details could not be matched. Check your details or upload a clearer ID.',
            default => 'We could not confirm all details. Review your information; PASS staff must review this submission.',
        }];
    }
}
