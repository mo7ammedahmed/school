/**
 * Publish a browser stream to a WHIP endpoint.
 *
 * WHIP is plain WebRTC with a POST on top: the offer is sent as the request
 * body, the answer comes back as the response body. MediaMTX speaks it, so a
 * teacher's camera or screen is published without any client library.
 *
 * The offer is sent after ICE gathering finishes (non-trickle): MediaMTX
 * accepts trickle candidates in principle, but a single complete offer is one
 * request instead of many, and the wait is bounded so a firewall that never
 * reports a candidate cannot hang the studio.
 */

const ICE_GATHERING_TIMEOUT_MS = 2500;

/**
 * The codecs MediaMTX can actually write to disk, most compatible first.
 *
 * The recorder stores fMP4, which carries H264, AV1 and VP9 — but not VP8.
 * VP8 is a perfectly valid WebRTC codec and Chrome's default, so without an
 * explicit preference a lesson publishes, plays, and is never recorded: the
 * live server logs "no supported tracks found" and writes nothing. The order
 * is deliberate: H264 plays everywhere, the other two compress better.
 */
const RECORDABLE_VIDEO_CODECS: readonly (readonly string[])[] = [
    ['video/h264', 'video/av1', 'video/vp9'],
    ['video/av1', 'video/vp9'],
];

/** One entry of the browser's own video codec list. */
type VideoCodecCapability = NonNullable<ReturnType<typeof RTCRtpSender.getCapabilities>>['codecs'][number];

/**
 * Build the codec preferences to try, in order, from the browser's own
 * capabilities. Each entry is one complete `setCodecPreferences` argument.
 *
 * The second list exists because browsers are not uniform: some reject a list
 * that names a codec they cannot use for the transceiver at all (Chromium
 * refuses H264 here with `InvalidModificationError`), and being refused must
 * not stop a lesson from going out — it must fall back to the next list.
 *
 * VP8 never appears: the recorder would drop it.
 */
export function recordableVideoCodecPreferences(
    capabilities: readonly VideoCodecCapability[],
): VideoCodecCapability[][] {
    return RECORDABLE_VIDEO_CODECS.map((mimes) =>
        mimes.flatMap((mime) => capabilities.filter((capability) => capability.mimeType.toLowerCase() === mime)),
    ).filter((preferences) => preferences.length > 0);
}

/**
 * Ask the connection for a recordable video codec before the offer is made.
 *
 * Preference is best-effort by design: if the browser refuses every list, the
 * stream is still published on the browser's defaults, exactly as before.
 */
function preferRecordableVideoCodec(connection: RTCPeerConnection): void {
    const capabilities = RTCRtpSender.getCapabilities?.('video')?.codecs ?? [];

    if (capabilities.length === 0) {
        return;
    }

    const transceiver = connection
        .getTransceivers()
        .find((candidate) => candidate.sender.track?.kind === 'video');

    if (transceiver === undefined || typeof transceiver.setCodecPreferences !== 'function') {
        return;
    }

    for (const preferences of recordableVideoCodecPreferences(capabilities)) {
        try {
            transceiver.setCodecPreferences(preferences);

            return;
        } catch {
            // Some browsers reject a list they cannot satisfy; try the next one.
        }
    }
}

export interface WhipSession {
    /** Stop publishing and release the camera / screen tracks. */
    close: () => void;
}

export async function publishToWhip(url: string, stream: MediaStream, token?: string): Promise<WhipSession> {
    const connection = new RTCPeerConnection();

    for (const track of stream.getTracks()) {
        connection.addTrack(track, stream);
    }

    preferRecordableVideoCodec(connection);

    const offer = await connection.createOffer();
    await connection.setLocalDescription(offer);
    await waitForIceGathering(connection);

    let answer: string;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/sdp', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
            body: connection.localDescription?.sdp ?? '',
        });

        if (!response.ok) {
            throw new Error(`The live server refused the stream (${response.status}).`);
        }

        answer = await response.text();
    } catch (error) {
        connection.close();
        throw error;
    }

    await connection.setRemoteDescription({ type: 'answer', sdp: answer });

    return {
        close: () => {
            for (const sender of connection.getSenders()) {
                sender.track?.stop();
            }
            connection.close();
        },
    };
}

function waitForIceGathering(connection: RTCPeerConnection): Promise<void> {
    if (connection.iceGatheringState === 'complete') {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        const finish = () => {
            if (connection.iceGatheringState === 'complete') {
                connection.removeEventListener('icegatheringstatechange', finish);
                resolve();
            }
        };

        connection.addEventListener('icegatheringstatechange', finish);
        window.setTimeout(() => {
            connection.removeEventListener('icegatheringstatechange', finish);
            resolve();
        }, ICE_GATHERING_TIMEOUT_MS);
    });
}
