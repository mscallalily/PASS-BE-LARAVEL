<?php

namespace App\Services\Registration;

/** Read-only identity from signed OCR evidence, never from submitted name/number fields. */
class InstitutionalIdAutofill
{
    public const POLICY = 'dyci-id-autofill-v1';

    public function evaluate(string $category, array $evidence): array
    {
        $fields = array_fill_keys(['first_name', 'last_name', 'middle_initial', 'suffix', 'user_code'], '');
        foreach ($fields as $key => $_) $fields[$key] = mb_strtoupper(trim((string) ($evidence['fields'][$key] ?? '')));
        $check = app(InstitutionalIdRules::class)->evaluate($category, $evidence, $fields);
        $passed = $check['status'] === 'matched';
        // The old ID-only signup used a separate SHS staff-approval policy.
        // This flow activates all three categories only after ID, inbox and license checks.
        $check['document_policy_version'] = $check['policy_version'];
        $check['policy_version'] = self::POLICY;
        $check['requires_staff_approval'] = false;
        $check['note'] = 'Printed institutional ID details only. Account activation also requires verified institutional email and a matching, unexpired license. School enrollment and document authenticity are not verified against an issuer.';
        return [
            'status' => $passed ? 'passed' : ($check['status'] === 'unavailable' ? 'unavailable' : 'retake'),
            'message' => $passed
                ? 'Your name and ID number were read from your institutional ID. These fields cannot be edited. Complete only the information not printed on the card.'
                : ($check['status'] === 'unavailable' ? 'The ID reader is temporarily unavailable. Try again shortly.' : 'Take a clearer photo of your DYCI ID. Keep the entire card upright and readable, without glare. Do not type over an uncertain reading.'),
            'fields' => $fields,
            // Optional profile fields are included only when actually read with confidence.
            'profile_fields' => $passed ? ($evidence['profile_fields'] ?? []) : [],
            'policy_version' => self::POLICY,
            'evidence' => $check,
        ];
    }
}
