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
