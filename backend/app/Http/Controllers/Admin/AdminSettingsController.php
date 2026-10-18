<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    private const EDITABLE_GROUPS = ['general', 'security', 'video', 'social'];

    public function __construct(
        private SettingsService $settings,
        private AuditLogger $audit,
    ) {}

    public function index(): JsonResponse
    {
        $settings = Setting::all()
            ->groupBy('group')
            ->map(fn ($group) => $group->mapWithKeys(fn ($s) => [$s->key => $s->value['v'] ?? null]));

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'group' => ['required', 'string', 'in:'.implode(',', self::EDITABLE_GROUPS)],
        ]);

        $this->settings->setMany($data['settings'], $data['group']);
        $this->audit->log('settings.updated', null, ['group' => $data['group'], 'keys' => array_keys($data['settings'])]);

        return response()->json(['ok' => true]);
    }
}
