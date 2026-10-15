<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('department_name')
            ->get([
                'department_id',
                'department_code',
                'department_name',
            ]);

        return response()->json([
            'data' => $departments,
        ]);
    }
}