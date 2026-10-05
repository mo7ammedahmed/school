/**
 * Play a WHEP stream in a `<video>` element.
 *
 * WHEP is the read side of the same WebRTC handshake WHIP uses: one POST with
 * an offer, one answer back. The stream arrives as remote tracks, so they are
 * attached to a `MediaStream` the element can render. Latency is sub-second,
 * which is what makes a live lesson feel live.
 *
 * The element is muted until the user unmutes: browsers refuse autoplay with
 * sound, and a lesson that silently fails to start is worse than one that
 * starts muted with a visible unmute control.
 */

const ICE_GATHERING_TIMEOUT_MS = 2500;

export interface WhepSession {
    close: () => void;
    /**
     * Whether the connection is carrying media, rather than merely negotiated.
     *
     * A handshake that succeeds proves the signalling reached the server; it
     * proves nothing about the media path. When a school network blocks the UDP
     * port WebRTC needs, the POST still answers, the peer connection still
     * reports `connected`, and no packet ever arrives — a black rectangle with
     * no error to explain it. Inbound bytes are the only honest evidence, so
     * this is what the HLS fallback watches.
     */
    hasMediaFlow: () => Promise<boolean>;
}

export async function playFromWhep(url: string, video: HTMLVideoElement, token?: string): Promise<WhepSession> {
    const connection = new RTCPeerConnection();
    const stream = new MediaStream();

    video.srcObject = stream;

    connection.addTransceiver('video', { direction: 'recvonly' });
    connection.addTransceiver('audio', { direction: 'recvonly' });

    connection.addEventListener('track', (event) => {
        for (const track of event.streams[0]?.getTracks() ?? []) {
            if (!stream.getTracks().includes(track)) {
                stream.addTrack(track);
            }
        }
    });

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
            throw new Error(`The live server refused the viewer (${response.status}).`);
        }

        answer = await response.text();
    } catch (error) {
        connection.close();
        video.srcObject = null;
        throw error;
    }

    await connection.setRemoteDescription({ type: 'answer', sdp: answer });

    // Muted, because browsers refuse to autoplay an element with sound, and a
    // lesson that waits for a second click is not a lesson that started. The
    // player's own control is how the student unmutes.
    video.muted = true;

    void video.play().catch(() => {
        // Autoplay can still be refused (a detached element, a policy that
        // needs a gesture per element); the controls are there and a click
        // starts it.
    });

    return {
        close: () => {
            connection.close();
            video.srcObject = null;
        },
        hasMediaFlow: () => hasMediaFlow(connection),
    };
}

/**
 * Whether any inbound RTP has been received on this connection.
 *
 * `getStats()` is asynchronous and not free, so callers poll it rather than
 * relying on it per frame. A browser that refuses to answer is read as "no
 * media": the caller's next move is the fallback stream, which is a lesson the
 * viewer can still watch, and the alternative — assuming bytes that are not
 * there — is the silent black screen this exists to catch.
 */
async function hasMediaFlow(connection: RTCPeerConnection): Promise<boolean> {
    try {
        const report = await connection.getStats();
        let flowing = false;

        report.forEach((entry: { type?: string; bytesReceived?: number }) => {
            if (entry.type === 'inbound-rtp' && (entry.bytesReceived ?? 0) > 0) {
                flowing = true;
            }
        });

        return flowing;
    } catch {
        return false;
    }
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
