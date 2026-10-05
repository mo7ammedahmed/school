import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    MEDIA_FLOW_POLL_MS,
    MEDIA_FLOW_TIMEOUT_MS,
    playLive,
    sessionSources,
    watchForMedia,
    type LiveMode,
} from './live';
import { playFromWhep, type WhepSession } from './whep';

vi.mock('./whep', () => ({ playFromWhep: vi.fn() }));

/**
 * hls.js is mocked rather than loaded: what is under test is *when* the player
 * is attached and to which URL, not the library's demuxing. Loading the real one
 * in happy-dom would fail for reasons that have nothing to do with this code.
 */
const hls = vi.hoisted(() => {
    class FakeHls {
        static supported = true;

        url: string | null = null;

        attached: HTMLVideoElement | null = null;

        destroyed = false;

        static instances: FakeHls[] = [];

        static isSupported(): boolean {
            return FakeHls.supported;
        }

        constructor() {
            FakeHls.instances.push(this);
        }

        loadSource(url: string): void {
            this.url = url;
        }

        attachMedia(video: HTMLVideoElement): void {
            this.attached = video;
        }

        destroy(): void {
            this.destroyed = true;
        }
    }

    return { FakeHls };
});

vi.mock('hls.js', () => ({ default: hls.FakeHls }));

const WHEP = 'https://media.example.test/stream-key/whep';
const HLS = 'https://media.example.test/stream-key/index.m3u8';

/** A `<video>` that reports the given native-HLS support and records play(). */
function videoWithNativeHls(nativeHls: boolean): HTMLVideoElement {
    const video = document.createElement('video');
    const support: CanPlayTypeResult = nativeHls ? 'maybe' : '';

    // happy-dom does not implement canPlayType, and the property is read-only
    // enough that defining it is clearer than casting a spy onto it.
    Object.defineProperty(video, 'canPlayType', { value: () => support, configurable: true });

    video.play = vi.fn(() => Promise.resolve());
    video.load = vi.fn();

    return video;
}

function whepSession(flowing: boolean | (() => Promise<boolean>)): WhepSession {
    return {
        close: vi.fn(),
        hasMediaFlow: vi.fn(typeof flowing === 'function' ? flowing : async () => flowing),
    };
}

function collectModes(): { modes: LiveMode[]; onMode: (mode: LiveMode) => void } {
    const modes: LiveMode[] = [];

    return { modes, onMode: (mode) => modes.push(mode) };
}

afterEach(() => {
    hls.FakeHls.instances = [];
    hls.FakeHls.supported = true;
    vi.mocked(playFromWhep).mockReset();
    vi.useRealTimers();
});

describe('live lesson sources', () => {
    it('builds both MediaMTX URLs from the configured bases', () => {
        expect(sessionSources({ webrtcUrl: 'https://media.example.test', hlsUrl: 'https://media.example.test:8443/' }, 'abc'))
            .toEqual({ whepUrl: 'https://media.example.test/abc/whep', hlsUrl: 'https://media.example.test:8443/abc/index.m3u8' });
    });

    it('leaves a mode out when its base is missing', () => {
        expect(sessionSources({ webrtcUrl: null, hlsUrl: null }, 'abc')).toEqual({ whepUrl: null, hlsUrl: null });
        expect(sessionSources({ webrtcUrl: '', hlsUrl: 'https://hls.test' }, 'abc'))
            .toEqual({ whepUrl: null, hlsUrl: 'https://hls.test/abc/index.m3u8' });
    });
});

describe('playing a live lesson', () => {
    it('stays on WHEP when media is flowing, and never loads hls.js', async () => {
        const session = whepSession(true);
        vi.mocked(playFromWhep).mockResolvedValue(session);

        const { modes, onMode } = collectModes();
        const playback = await playLive({ whepUrl: WHEP, hlsUrl: HLS }, videoWithNativeHls(false), onMode);

        expect(modes).toEqual(['whep']);
        expect(hls.FakeHls.instances).toHaveLength(0);

        playback.close();
        expect(session.close).toHaveBeenCalled();
    });

    it('falls back to HLS when the handshake is refused', async () => {
        vi.mocked(playFromWhep).mockRejectedValue(new Error('The live server refused the viewer (500).'));

        const { modes, onMode } = collectModes();
        const video = videoWithNativeHls(false);

        await playLive({ whepUrl: WHEP, hlsUrl: HLS }, video, onMode);

        expect(modes).toEqual(['hls']);
        expect(hls.FakeHls.instances[0]?.url).toBe(HLS);
        expect(hls.FakeHls.instances[0]?.attached).toBe(video);
    });

    it('surfaces the handshake error when there is no fallback to take', async () => {
        vi.mocked(playFromWhep).mockRejectedValue(new Error('The live server refused the viewer (500).'));

        await expect(playLive({ whepUrl: WHEP, hlsUrl: null }, videoWithNativeHls(false)))
            .rejects.toThrow('refused the viewer');
    });

    it('switches to HLS when the connection never carries media (a blocked UDP port)', async () => {
        vi.useFakeTimers();

        const session = whepSession(false);
        vi.mocked(playFromWhep).mockResolvedValue(session);

        const { modes, onMode } = collectModes();
        const playback = await playLive({ whepUrl: WHEP, hlsUrl: HLS }, videoWithNativeHls(false), onMode);

        expect(modes).toEqual(['whep']);
        expect(hls.FakeHls.instances).toHaveLength(0);

        await vi.advanceTimersByTimeAsync(MEDIA_FLOW_TIMEOUT_MS + MEDIA_FLOW_POLL_MS);

        expect(modes).toEqual(['whep', 'hls']);
        expect(session.close).toHaveBeenCalled();
        expect(hls.FakeHls.instances[0]?.url).toBe(HLS);

        playback.close();
    });

    it('treats a probe that cannot answer as no media', async () => {
        vi.useFakeTimers();

        const session = whepSession(() => Promise.reject(new Error('getStats is unavailable')));
        vi.mocked(playFromWhep).mockResolvedValue(session);

        const { modes, onMode } = collectModes();
        await playLive({ whepUrl: WHEP, hlsUrl: HLS }, videoWithNativeHls(false), onMode);

        await vi.advanceTimersByTimeAsync(MEDIA_FLOW_TIMEOUT_MS + MEDIA_FLOW_POLL_MS);

        expect(modes).toEqual(['whep', 'hls']);
    });

    it('plays HLS directly when only the fallback is configured', async () => {
        const { modes, onMode } = collectModes();
        const video = videoWithNativeHls(true);

        await playLive({ whepUrl: null, hlsUrl: HLS }, video, onMode);

        expect(modes).toEqual(['hls']);
        expect(playFromWhep).not.toHaveBeenCalled();
        expect(video.src).toBe(HLS);
    });

    it('refuses to start when no watch path is configured', async () => {
        await expect(playLive({ whepUrl: null, hlsUrl: null }, videoWithNativeHls(false)))
            .rejects.toThrow('not configured');
    });

    it('uses the browser’s own player for HLS instead of loading hls.js', async () => {
        const { modes, onMode } = collectModes();
        const video = videoWithNativeHls(true);

        const playback = await playLive({ whepUrl: null, hlsUrl: HLS }, video, onMode);

        expect(modes).toEqual(['hls']);
        expect(video.src).toBe(HLS);
        expect(hls.FakeHls.instances).toHaveLength(0);

        playback.close();
        expect(video.load).toHaveBeenCalled();
    });

    it('destroys the hls.js instance when the player closes', async () => {
        const playback = await playLive({ whepUrl: null, hlsUrl: HLS }, videoWithNativeHls(false));

        playback.close();

        expect(hls.FakeHls.instances[0]?.destroyed).toBe(true);
    });

    it('stops watching when the viewer leaves before the deadline', async () => {
        vi.useFakeTimers();

        const session = whepSession(false);
        vi.mocked(playFromWhep).mockResolvedValue(session);

        const { modes, onMode } = collectModes();
        const playback = await playLive({ whepUrl: WHEP, hlsUrl: HLS }, videoWithNativeHls(false), onMode);

        playback.close();

        await vi.advanceTimersByTimeAsync(MEDIA_FLOW_TIMEOUT_MS + MEDIA_FLOW_POLL_MS);

        expect(modes).toEqual(['whep']);
        expect(hls.FakeHls.instances).toHaveLength(0);
    });
});

describe('the media-flow watchdog', () => {
    it('gives up once, at the deadline', async () => {
        vi.useFakeTimers();

        const session = whepSession(false);
        const stalls: number[] = [];

        const stop = watchForMedia(session, () => stalls.push(Date.now()));

        await vi.advanceTimersByTimeAsync(MEDIA_FLOW_TIMEOUT_MS - MEDIA_FLOW_POLL_MS);
        expect(stalls).toHaveLength(0);

        await vi.advanceTimersByTimeAsync(MEDIA_FLOW_POLL_MS * 2);
        expect(stalls).toHaveLength(1);

        stop();
    });

    it('stops asking once the answer is yes', async () => {
        vi.useFakeTimers();

        const session = whepSession(true);
        const onStall = vi.fn();

        const stop = watchForMedia(session, onStall);

        await vi.advanceTimersByTimeAsync(MEDIA_FLOW_TIMEOUT_MS * 2);

        expect(session.hasMediaFlow).toHaveBeenCalledTimes(1);
        expect(onStall).not.toHaveBeenCalled();

        stop();
    });
});
