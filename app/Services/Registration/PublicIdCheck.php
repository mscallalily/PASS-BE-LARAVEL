<?php

namespace App\Services\Registration;

/** Internal OCR diagnostics belong in the authorized staff review, not public forms. */
final class PublicIdCheck
{
    public static function summary(array $result): array
    {
        $identityMatched = collect($result['checks'] ?? [])->only(['id_format', 'user_code', 'first_name', 'last_name', 'middle_initial', 'suffix']);
        $identityMatched = $identityMatched->has(['id_format', 'user_code', 'first_name', 'last_name'])
            && $identityMatched->every(fn ($check) => ($check['result'] ?? '') === 'pass');
        $shs = ! empty($result['requires_staff_approval']);
        return ['status' => $result['status'], 'message' => match ($result['status']) {
            'matched' => ! empty($result['requires_staff_approval'])
                ? 'Review the suggested details against your ID. SHS applications require staff approval after email verification.'
                : 'Review the suggested details against your ID before continuing.',
            'needs_correction' => 'The document and submitted details could not be matched. Check your details or upload a clearer ID.',
            'manual_review' => $shs && $identityMatched
                ? 'Your name and LRN match the document text. Review the suggestions before submitting; PASS staff must complete the document review and approve your SHS application.'
                : ($shs ? 'Review the suggested details before submitting. PASS staff must review this SHS document. Middle initial and suffix may be left blank when not printed on the ID.' : 'We could not confirm all details. Review your information; PASS staff must review this submission.'),
            default => 'We could not confirm all details. Review your information; PASS staff must review this submission.',
        }, 'identity_matched' => $identityMatched];
    }
}
