/**
 * Play a live lesson, with the fallback a locked-down network needs.
 *
 * WHEP is the good path: sub-second latency, one POST, no player library. It is
 * also the fragile one. WebRTC carries media over UDP, and a school network that
 * allows HTTP(S) and nothing else leaves the handshake succeeding and no picture
 * arriving — the failure mode has no error in it, just a black rectangle.
 *
 * HLS travels the same way every other page does, over the port the network has
 * already decided to allow, so it is the path that survives. The cost is delay:
 * several seconds instead of half of one. That is a trade a pupil can be told
 * about, which is why the player reports which path it ended up on.
 *
 * The switch is decided by evidence rather than by preference: if the WHEP
 * connection has not actually received media bytes by the deadline, the stream
 * is torn down and the fallback starts. A connection that is merely slow to
 * start is given the whole window; a connection that is never going to work
 * does not wait for a timeout the user cannot see.
 */

import { playFromWhep, type WhepSession } from './whep';

export type LiveMode = 'whep' | 'hls';

export interface LiveSources {
    whepUrl: string | null;
    hlsUrl: string | null;
    readToken?: string;
}

export interface LivePlayback {
    close: () => void;
}

/** How long the WHEP connection gets to produce its first media bytes. */
export const MEDIA_FLOW_TIMEOUT_MS = 6000;

/** How often it is asked whether it has. */
export const MEDIA_FLOW_POLL_MS = 750;

/**
 * The two URLs for one session, from the bases the server passed to the page.
 *
 * The path shapes (`/<stream_key>/whep`, `/<stream_key>/index.m3u8`) are
 * MediaMTX's, and they live here — next to each other — because a fallback that
 * silently derived a different path would fail exactly when it is needed.
 */
export function sessionSources(
    media: { webrtcUrl: string | null; hlsUrl: string | null },
    streamKey: string,
): LiveSources {
    const join = (base: string | null, path: string): string | null => {
        const trimmed = base?.replace(/\/$/, '') ?? '';

        return trimmed === '' ? null : `${trimmed}/${path}`;
    };

    return {
        whepUrl: join(media.webrtcUrl, `${streamKey}/whep`),
        hlsUrl: join(media.hlsUrl, `${streamKey}/index.m3u8`),
    };
}

/**
 * Start the lesson, watching the low-latency path and falling back when it
 * cannot deliver. `onMode` is called with the path that is actually playing —
 * `whep` first when there is a WHEP endpoint, then `hls` if it had to switch.
 */
export async function playLive(
    sources: LiveSources,
    video: HTMLVideoElement,
    onMode: (mode: LiveMode) => void = () => {},
): Promise<LivePlayback> {
    if (sources.whepUrl === null && sources.hlsUrl === null) {
        throw new Error('Live viewing is not configured on this server yet.');
    }

    let closed = false;
    let stopCurrent: () => void = () => {};
    let stopWatchdog: () => void = () => {};

    const teardown = () => {
        closed = true;
        stopWatchdog();

        // Whatever is playing — the peer connection, hls.js, or the browser's
        // own player — is torn down here. Leaving it attached would keep the
        // stream downloading for a lesson the viewer has left.
        const stop = stopCurrent;
        stopCurrent = () => {};
        stop();
    };

    /**
     * Move to the fallback stream. Returns false when there is nothing to move
     * to, so the caller can surface the error it already has instead of a
     * message about a stream that was never configured.
     */
    const switchToHls = async (): Promise<boolean> => {
        const hlsUrl = sources.hlsUrl;

        if (hlsUrl === null || closed) {
            return false;
        }

        stopWatchdog();
        stopCurrent();
        stopCurrent = () => {};

        const stopHls = await startHls(hlsUrl, video);

        if (closed) {
            // Closed while the manifest was loading; leaving the player attached
            // would keep fetching segments for a lesson nobody is watching.
            stopHls();

            return false;
        }

        stopCurrent = stopHls;
        onMode('hls');

        return true;
    };

    if (sources.whepUrl !== null) {
        try {
            const session = await playFromWhep(sources.whepUrl, video, sources.readToken);

            stopCurrent = () => session.close();
            onMode('whep');
            stopWatchdog = watchForMedia(session, () => {
                void switchToHls();
            });

            return { close: teardown };
        } catch (error) {
            if (closed) {
                return { close: teardown };
            }

            if (!(await switchToHls())) {
                throw error;
            }

            return { close: teardown };
        }
    }

    // Only the fallback is configured — still a lesson, so play it.
    await switchToHls();

    return { close: teardown };
}

/**
 * Watch a WHEP connection for its first media bytes, and give up at the
 * deadline. Returns a function that stops watching.
 */
export function watchForMedia(
    session: WhepSession,
    onStall: () => void,
    timeoutMs: number = MEDIA_FLOW_TIMEOUT_MS,
    pollMs: number = MEDIA_FLOW_POLL_MS,
): () => void {
    let stopped = false;
    let timer: number | null = null;

    const deadline = Date.now() + timeoutMs;

    const poll = async (): Promise<void> => {
        if (stopped) {
            return;
        }

        // A probe that throws is a probe with no answer, and the caller's next
        // move is the stream that can still be watched.
        const flowing = await session.hasMediaFlow().catch(() => false);

        if (stopped) {
            return;
        }

        if (flowing) {
            return;
        }

        if (Date.now() >= deadline) {
            onStall();

            return;
        }

        timer = window.setTimeout(() => {
            void poll();
        }, pollMs);
    };

    timer = window.setTimeout(() => {
        void poll();
    }, pollMs);

    return () => {
        stopped = true;

        if (timer !== null) {
            window.clearTimeout(timer);
            timer = null;
        }
    };
}

/**
 * Play an HLS playlist, with the browser's own player where there is one.
 *
 * Safari plays HLS natively and needs no library. Everything else gets hls.js,
 * imported at the moment it is needed rather than shipped with the page: the
 * fallback is for a minority of networks, and making every other page load a
 * player library it will never use is a cost with no benefit.
 */
export async function startHls(url: string, video: HTMLVideoElement): Promise<() => void> {
    // Muted for the same reason as the WHEP path: an element with sound is not
    // allowed to autoplay, and a fallback nobody can hear is still better than
    // a fallback nobody can see. The controls are how the student unmutes.
    video.muted = true;

    if (video.canPlayType('application/vnd.apple.mpegurl') !== '') {
        video.src = url;

        void video.play().catch(() => {
            // Autoplay may be refused; the controls are there and a click starts it.
        });

        return () => {
            video.removeAttribute('src');
            video.load();
        };
    }

    const { default: Hls } = await import('hls.js');

    if (!Hls.isSupported()) {
        throw new Error('This browser cannot play the backup stream either.');
    }

    // `lowLatencyMode` matches MediaMTX's low-latency playlist: partial segments
    // are fetched as they are written, which keeps the fallback near the live
    // edge instead of a minute behind it.
    const hls = new Hls({ lowLatencyMode: true });

    hls.loadSource(url);
    hls.attachMedia(video);

    void video.play().catch(() => {
        // As above: a muted click is the user's to make.
    });

    return () => hls.destroy();
}
