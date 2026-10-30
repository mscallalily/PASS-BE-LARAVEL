<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Registration\InstitutionalIdReader;
use App\Services\Registration\InstitutionalIdReceipt;
use App\Services\Registration\InstitutionalIdRules;
use App\Support\CampusTime;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationIdController extends Controller
{
    public function autofill(Request $request, InstitutionalIdReader $reader, InstitutionalIdReceipt $receipts, \App\Services\Registration\InstitutionalIdAutofill $autofill)
    {
        $data = $request->validate([
            'registration_category' => ['required', Rule::in(InstitutionalIdRules::CATEGORIES)],
            'school_id_file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
            'first_name' => ['prohibited'], 'last_name' => ['prohibited'], 'user_code' => ['prohibited'],
            'middle_name' => ['prohibited'], 'middle_initial' => ['prohibited'], 'suffix' => ['prohibited'],
        ]);
        $file = $request->file('school_id_file'); $size = @getimagesize($file->getRealPath());
        if (!$size || $size[0] * $size[1] > 20000000 || max($size[0], $size[1]) > 12000) throw \Illuminate\Validation\ValidationException::withMessages(['school_id_file' => 'Use a JPG/PNG photo of at most 20 megapixels and 5 MB.']);
        try { $evidence = $reader->read($file->getRealPath(), $file->getMimeType(), $data['registration_category']); }
        catch (\Throwable $error) { report($error); $evidence = ['kind' => 'image', 'engine_available' => false, 'fields' => []]; }
        $result = $autofill->evaluate($data['registration_category'], $evidence);
        unset($result['evidence'], $result['policy_version']);
        $result['receipt'] = $result['status'] === 'passed' ? $receipts->issue($file, $data['registration_category'], $evidence) : null;
        return response()->json(['data' => $result])->header('Cache-Control', 'no-store, private');
    }

    public function options()
    {
        return response()->json(['current_year' => CampusTime::now()->year,
            'categories' => InstitutionalIdRules::CATEGORIES, 'receipt_minutes' => 20]);
    }

    public function check(Request $request, InstitutionalIdReader $reader, InstitutionalIdReceipt $receipts, InstitutionalIdRules $rules)
    {
        $data = $request->validate([
            'registration_category' => ['required', Rule::in(InstitutionalIdRules::CATEGORIES)],
            'school_id_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'id_check_receipt' => ['nullable', 'string', 'max:30000'],
            'request_manual_review' => ['sometimes', 'boolean'],
            'first_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'middle_initial' => ['nullable', 'string', 'max:3'], 'user_code' => ['nullable', 'string', 'max:50'],
        ]);
        $file = $request->file('school_id_file');
        $category = $data['registration_category'];
        $size = @getimagesize($file->getRealPath());
        if ($size && $size[0] * $size[1] > 20000000) {
            return response()->json(['message' => 'Use a photo with at most 20 megapixels.', 'errors' => ['school_id_file' => ['Resize the image to at most 20 megapixels.']]], 422);
        }
        if (! empty($data['id_check_receipt'])) {
            $evidence = $receipts->read($data['id_check_receipt'], $file, $category);
            $receipt = $data['id_check_receipt'];
        } else {
            try {
                $evidence = $reader->read($file->getRealPath(), $file->getMimeType(), $category);
            } catch (\Throwable $error) {
                report($error);
                $evidence = ['kind' => 'image', 'engine_available' => false, 'fields' => []];
            }
            $receipt = $receipts->issue($file, $category, $evidence);
        }
        $submitted = [];
        foreach (['first_name', 'last_name', 'middle_initial', 'suffix', 'user_code'] as $key) {
            $submitted[$key] = array_key_exists($key, $data) ? trim($data[$key] ?? '') : ($evidence['fields'][$key] ?? '');
        }
        $result = $rules->requestManualReview($rules->evaluate($category, $evidence, $submitted), $request->boolean('request_manual_review'));

        return response()->json(['data' => \App\Services\Registration\PublicIdCheck::summary($result) + [
            'fields' => array_map(fn ($v) => is_string($v) ? mb_strtoupper($v) : $v, $evidence['fields'] ?? []),
            'receipt' => $receipt, 'current_year' => CampusTime::now()->year,
        ]])->header('Cache-Control', 'no-store, private');
    }
}
