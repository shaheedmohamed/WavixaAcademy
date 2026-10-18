<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $certificates = $request->user()->certificates()
            ->with(['course:id,title,slug,thumbnail,instructor_id,certificate_title,certificate_language', 'course.instructor:id,name', 'user:id,name,student_number'])
            ->latest('issued_at')
            ->get()
            ->map(fn ($cert) => [
                'id' => $cert->id,
                'number' => $cert->certificate_number,
                'course' => $cert->course,
                'student_name' => $cert->user?->name,
                'student_number' => $cert->user?->student_number,
                'instructor_name' => $cert->course?->instructor?->name,
                'issued_at' => $cert->issued_at,
                'verification_token' => $cert->verification_token,
                'verify_url' => url('/certificates/'.$cert->verification_token),
            ]);

        return response()->json(['certificates' => $certificates]);
    }
}
