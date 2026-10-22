<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseVerification\LicenseDocumentReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DocumentAutofillController extends Controller
{
    public function __invoke(Request $request, LicenseDocumentReader $reader): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:school_id,driver_license'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Read PHP's temporary upload only; do not create an account, retain the
        // document, approve anything, or log the extracted personal information.
        try {
            $file = $request->file('document');
            $result = $reader->read($file->getRealPath(), $file->getMimeType());
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Document reading is unavailable. You can enter the details manually.'], 503);
        }

        $text = trim($result['text'] ?? '');
        if ($text === '') {
            return response()->json(['message' => 'No readable text was found. Try a clearer image or enter the details manually.'], 422);
        }

        $fields = [];
        if ($data['document_type'] === 'school_id') {
            if (preg_match('/STUDENT\s*(?:NO\.?|NUMBER|ID)\s*[:#]?\s*(\d{4}\s*-\s*\d{4,6})/i', $text, $match)) {
                $fields['idNum'] = preg_replace('/\s+/', '', $match[1]);
            }
            // DYCI sample layout: student number, surname, then given name.
            $lines = preg_split('/\R/', $text);
            foreach ($lines as $i => $line) {
                if (!preg_match('/STUDENT\s*(?:NO|NUMBER|ID)/i', $line)) continue;
                $next = array_values(array_filter(array_map('trim', array_slice($lines, $i + 1)), fn ($v) => $v !== ''));
                if (count($next) >= 2 && preg_match('/^[\p{L} .\x{2019}\'-]+$/u', $next[0]) && preg_match('/^[\p{L} .\x{2019}\'-]+$/u', $next[1]) && !preg_match('/BACHELOR|DEPARTMENT|COLLEGE/i', $next[0].' '.$next[1])) {
                    $fields['fullName'] = trim($next[1].' '.$next[0]);
                }
                break;
            }
        } else {
            if (preg_match('/\b([A-Z]\d{2})[- ]?(\d{2})[- ]?(\d{6})\b/i', $text, $match)) {
                $fields['licenseNo'] = strtoupper($match[1]).'-'.$match[2].'-'.$match[3];
            }
            if (preg_match('/NON[ -]?PROFESSIONAL/i', $text)) $fields['licenseType'] = 'non_professional';
            elseif (preg_match('/\bPROFESSIONAL\b/i', $text)) $fields['licenseType'] = 'professional';
            elseif (preg_match('/STUDENT\s+PERMIT/i', $text)) $fields['licenseType'] = 'student_permit';
            elseif (preg_match('/INTERNATIONAL\s+(?:DRIV|PERMIT)/i', $text)) $fields['licenseType'] = 'international';

            // Only suggest a date found near its expiration label. Never pick
            // an arbitrary date of birth or issue date as the expiry.
            if (preg_match('/(?:EXPIRATION\s*DATE|EXPIRY(?:\s*DATE)?)\s*[:.]?\s*(\d{4})[\/.-](\d{2})[\/.-](\d{2})/i', $text, $match) && checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
                $fields['licenseExpiry'] = $match[1].'-'.$match[2].'-'.$match[3];
            }
        }

        return response()->json([
            'fields' => $fields,
            'text' => mb_substr($text, 0, 12000),
            'confidence' => $result['confidence'] ?? null,
            'message' => 'Review the suggested details against your document. All fields remain editable. This does not verify authenticity or approve access.',
        ])->header('Cache-Control', 'no-store, private');
    }
}
