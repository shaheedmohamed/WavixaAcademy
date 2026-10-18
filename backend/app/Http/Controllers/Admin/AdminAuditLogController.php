<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,student_number')->latest();

        if ($action = $request->string('action')->toString()) {
            $query->where('action', 'like', $action.'%');
        }
        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        return response()->json($query->paginate(30));
    }
}
