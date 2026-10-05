<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MediaMTX endpoints
    |--------------------------------------------------------------------------
    |
    | The live server (MediaMTX) is a separate process from the app. The browser
    | publishes over WHIP and watches over WHEP against the same HTTP port, and
    | both URLs are handed to the frontend per session. In production this is
    | the public HTTPS origin nginx proxies to MediaMTX; in local development it
    | is the compose service's published port.
    |
    | One origin, three protocols: WHIP publishes, WHEP watches with sub-second
    | latency, and `hls_url` below watches without WebRTC at all.
    |
    | When unset, the app still shows the session pages but the studio cannot
    | publish — an honest "not configured" state rather than a broken button.
    |
    */

    'webrtc_url' => env('MEDIA_WEBRTC_URL'),

    /*
    |--------------------------------------------------------------------------
    | MediaMTX control API
    |--------------------------------------------------------------------------
    |
    | The live server's own HTTP API, used to reconcile sessions the teacher
    | never ended: the scheduler asks whether a session's stream is still being
    | published. Unset disables that reconciliation (the four-hour stale rule
    | still applies).
    |
    */

    'api_url' => env('MEDIA_API_URL'),
    'api_user' => env('MEDIA_API_USER'),
    'api_password' => env('MEDIA_API_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | HLS fallback
    |--------------------------------------------------------------------------
    |
    | The same lesson over plain HTTP, from the live server's HLS endpoint. It
    | is the path that survives a school network which allows 443 and nothing
    | else: WHEP needs UDP (or a second TCP port) to the media server, and a
    | network that blocks either one leaves the low-latency player with a black
    | rectangle and no error to show for it.
    |
    | The browser-reachable base only; the player appends the session's own
    | `<stream_key>/index.m3u8`. Unset disables the fallback — a deployment that
    | leaves it empty has no answer when UDP is blocked, which is why production
    | should set it. Local development needs it too when the spike config turns
    | HLS off (`hls: no`), because then the fallback has nothing to fall back to.
    |
    */

    'hls_url' => env('MEDIA_HLS_URL'),
    'hls_internal_url' => env('MEDIA_HLS_INTERNAL_URL', env('MEDIA_HLS_URL')),

    /*
    |--------------------------------------------------------------------------
    | Recording disk and path
    |--------------------------------------------------------------------------
    |
    | MediaMTX writes recordings to a directory shared with the app (a Docker
    | volume, or `storage/app/recordings` locally). The `recordings` disk is
    | scratch space: the finalize job copies the finished file onto the private
    | `local` disk — where the uploads live — and removes it from here, so the
    | stream endpoint has exactly one disk to guard.
    |
    */

    'disk' => env('MEDIA_RECORDINGS_DISK', 'recordings'),

    /*
    |--------------------------------------------------------------------------
    | Scratch retention
    |--------------------------------------------------------------------------
    |
    | How long a file may sit on the `recordings` disk before
    | `live-sessions:prune` deletes it. The finalize job retries for minutes,
    | not days, so a recording past this window is a leftover: a segment of a
    | lesson longer than one record-segment, or a stream whose session failed.
    | Files belonging to a session that already produced its material are
    | deleted immediately, whatever this window says.
    |
    */

    'recordings_retention_hours' => (int) env('MEDIA_RECORDINGS_RETENTION_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Shared secret for MediaMTX hooks
    |--------------------------------------------------------------------------
    |
    | MediaMTX calls back into the app when a publisher disconnects. The hook
    | carries no user session, so it authenticates with this secret instead. An
    | empty secret refuses every call rather than accepting every call.
    |
    */

    'hook_secret' => env('MEDIA_HOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | ffmpeg / ffprobe
    |--------------------------------------------------------------------------
    |
    | Used to remux recorded fMP4 into a faststart MP4 and to read durations.
    | When the binaries are absent the job keeps the original file rather than
    | failing the recording — the lesson is still watchable.
    |
    */

    'ffmpeg' => env('MEDIA_FFMPEG_BINARY', 'ffmpeg'),

    'ffprobe' => env('MEDIA_FFPROBE_BINARY', 'ffprobe'),

];
