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
}

export async function playFromWhep(url: string, video: HTMLVideoElement): Promise<WhepSession> {
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
            headers: { 'Content-Type': 'application/sdp' },
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

    void video.play().catch(() => {
        // Autoplay may be refused before the stream has audio; the controls are
        // there, and a click starts it.
    });

    return {
        close: () => {
            connection.close();
            video.srcObject = null;
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
