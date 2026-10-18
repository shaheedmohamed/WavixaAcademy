<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminFaqController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Faq::orderBy('sort_order')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $faq = Faq::create($this->validateFaq($request));
        Cache::forget('faqs:public');

        return response()->json(['faq' => $faq], 201);
    }

    public function show(Faq $faq): JsonResponse
    {
        return response()->json(['faq' => $faq]);
    }

    public function update(Request $request, Faq $faq): JsonResponse
    {
        $faq->update($this->validateFaq($request));
        Cache::forget('faqs:public');

        return response()->json(['faq' => $faq]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();
        Cache::forget('faqs:public');

        return response()->json(['ok' => true]);
    }

    private function validateFaq(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }
}
