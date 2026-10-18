<?php

use Illuminate\Support\Facades\Route;

// The Vue SPA is served statically by Nginx from public/index.html.
// Laravel only owns /api/* and /sanctum/* routes.
Route::fallback(fn () => response()->json(['message' => 'Not found'], 404));
