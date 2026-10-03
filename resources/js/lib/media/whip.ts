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

export interface WhipSession {
    /** Stop publishing and release the camera / screen tracks. */
    close: () => void;
}

export async function publishToWhip(url: string, stream: MediaStream): Promise<WhipSession> {
    const connection = new RTCPeerConnection();

    for (const track of stream.getTracks()) {
        connection.addTrack(track, stream);
    }

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
