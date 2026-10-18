<?php

namespace App\Http\Controllers;

use App\Models\VideoAsset;
use App\Models\VideoSession;
use App\Services\LocalVideoProvider;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves HLS playlists/segments for locally-hosted videos.
 *
 * Authorization: the path signature binds (asset, session) and every
 * request re-checks the live VideoSession — heartbeat keeps it alive,
 * termination/expiry instantly revokes access. Files are delivered by
 * Nginx via X-Accel-Redirect so PHP never streams bytes itself.
 */
class StreamController extends Controller
{
    public function file(int $asset, int $session, string $sig, string $file): Response
    {
        abort_unless(
            hash_equals(LocalVideoProvider::signature($asset, $session), $sig),
            403
        );

        $sessionModel = VideoSession::find($session);
        abort_unless(
            $sessionModel
            && $sessionModel->lesson?->videoAsset?->id === $asset
            && $sessionModel->isValid(),
            403
        );

        // Confine to the asset's own HLS directory; block traversal.
        $file = basename($file);
        abort_unless(preg_match('/^(index\.m3u8|seg_\d+\.ts)$/', $file), 404);

        $path = Storage::disk('local')->path("videos/{$asset}/{$file}");
        abort_unless(is_file($path), 404);

        $mime = str_ends_with($file, '.m3u8')
            ? 'application/vnd.apple.mpegurl'
            : 'video/mp2t';

        $response = response('', 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Accel-Redirect' => "/protected-videos/{$asset}/{$file}",
        ]);

        return $response;
    }
}
