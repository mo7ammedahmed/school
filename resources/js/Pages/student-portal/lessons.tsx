import { useEffect, useRef, useState } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Play, Radio, Square } from 'lucide-react';
import { playLive, sessionSources, type LiveMode, type LivePlayback } from '@/lib/media/live';
import { formatDuration } from '@/lib/media/format';

type LiveSession = {
    id: number;
    title: string;
    stream_key: string;
    read_token: string;
    hls_playback_url: string | null;
    started_at: string | null;
    offering: { subject: { name: string } | null; section: { name: string } | null } | null;
};

type Recording = {
    id: number;
    title: string;
    kind: string;
    duration_seconds: number | null;
    created_at: string | null;
    offering: { subject: { name: string } | null; section: { name: string } | null } | null;
};

type Player = { kind: 'live'; id: number } | { kind: 'recording'; id: number } | null;

export default function StudentLessons({
    liveSessions,
    recordings,
    media,
}: {
    liveSessions: LiveSession[];
    recordings: Recording[];
    media: { configured: boolean; webrtcUrl: string | null; hlsUrl: string | null };
}) {
    const liveVideoRef = useRef<HTMLVideoElement>(null);
    const playbackRef = useRef<LivePlayback | null>(null);
    const [player, setPlayer] = useState<Player>(null);
    const [mode, setMode] = useState<LiveMode | null>(null);
    const [error, setError] = useState<string | null>(null);

    const activeLive = player?.kind === 'live' ? liveSessions.find((session) => session.id === player.id) : null;
    const activeRecording = player?.kind === 'recording' ? recordings.find((item) => item.id === player.id) : null;

    /*
     * The playback starts from an effect, not from the click: the video element
     * only exists after React renders the player card in response to the click,
     * so a ref read in the handler is still null on the first watch. The effect
     * runs on the render that mounts the element, and its cleanup stops whatever
     * is playing — peer connection, hls.js, or the browser's own player.
     *
     * `playLive` decides the path: WHEP first, and the HLS fallback when the
     * connection never receives media (a school network that blocks the UDP port
     * WebRTC needs). `onMode` reports which one is running, so the pupil is told
     * why the picture is a few seconds behind rather than left guessing.
     */
    useEffect(() => {
        if (player?.kind !== 'live') {
            return;
        }

        const session = liveSessions.find((item) => item.id === player.id);
        const video = liveVideoRef.current;

        if (!session || !video) {
            return;
        }

        let cancelled = false;

        void playLive({ ...sessionSources(media, session.stream_key), readToken: session.read_token, hlsUrl: session.hls_playback_url }, video, (next) => {
            if (!cancelled) {
                setMode(next);
            }
        })
            .then((playback) => {
                if (cancelled) {
                    playback.close();

                    return;
                }

                playbackRef.current = playback;
            })
            .catch((caught: unknown) => {
                if (cancelled) {
                    return;
                }

                setPlayer(null);
                setMode(null);
                setError(caught instanceof Error ? caught.message : 'The lesson could not be opened.');
            });

        return () => {
            cancelled = true;
            playbackRef.current?.close();
            playbackRef.current = null;
        };
    }, [player, liveSessions, media.webrtcUrl, media.hlsUrl]);

    const watchLive = (session: LiveSession) => {
        setError(null);
        setMode(null);

        if (!media.configured) {
            setError('Live viewing is not configured on this server yet.');

            return;
        }

        setPlayer({ kind: 'live', id: session.id });
    };

    const leaveLive = () => {
        setPlayer(null);
        setMode(null);
    };

    return (
        <AppShell
            title="My Lessons"
            breadcrumbs={[
                { label: 'Student Portal', href: '/student/dashboard' },
                { label: 'Lessons' },
            ]}
        >
            <PageHeader title="My Lessons" description="Watch a live lesson, or catch up on a recorded one" />

            {activeLive && (
                <Card className="mb-6 overflow-hidden">
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle>{activeLive.title}</CardTitle>
                        {/*
                            Two labels rather than one ternary: the extraction script
                            reads text between tags, so a string inside an expression
                            never reaches the Arabic dictionary.
                        */}
                        {mode === 'hls' ? (
                            <Badge variant="info">
                                <Radio className="me-1 h-3 w-3" />
                                Backup stream
                            </Badge>
                        ) : (
                            <Badge variant="success">
                                <Radio className="me-1 h-3 w-3" />
                                Live now
                            </Badge>
                        )}
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="aspect-video w-full overflow-hidden rounded-lg border border-border/70 bg-black">
                            <video ref={liveVideoRef} className="h-full w-full object-contain" controls playsInline />
                        </div>
                        {mode === 'hls' && (
                            <p className="text-sm text-muted-foreground">
                                Playing the backup stream: the low-latency one needs a network port this
                                connection does not allow, so this may run a few seconds behind.
                            </p>
                        )}
                        <Button variant="outline" onClick={leaveLive}>
                            <Square className="me-2 h-4 w-4" />
                            Leave the lesson
                        </Button>
                    </CardContent>
                </Card>
            )}

            {activeRecording && (
                <Card className="mb-6 overflow-hidden">
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle>{activeRecording.title}</CardTitle>
                        <span className="text-sm tabular-nums text-muted-foreground">
                            {formatDuration(activeRecording.duration_seconds)}
                        </span>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* The private disk is streamed through the app with Range
                            support, so seeking works and no file URL is exposed. */}
                        <video
                            className="aspect-video w-full rounded-lg border border-border/70 bg-black"
                            controls
                            playsInline
                            preload="metadata"
                            src={`/materials/${activeRecording.id}/stream`}
                        />
                        <Button variant="outline" onClick={() => setPlayer(null)}>
                            Close
                        </Button>
                    </CardContent>
                </Card>
            )}

            {error && (
                <p role="alert" className="mb-6 text-sm text-destructive">
                    {error}
                </p>
            )}

            <div className="space-y-8">
                <div>
                    <h2 className="mb-3 text-lg font-semibold">Live now</h2>
                    {liveSessions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No lesson is live right now.</p>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {liveSessions.map((session) => (
                                <Card key={session.id}>
                                    <CardContent className="flex items-center justify-between gap-4 pt-6">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">{session.title}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {session.offering?.subject?.name ?? '—'}
                                            </p>
                                        </div>
                                        <Button onClick={() => watchLive(session)}>
                                            <Play className="me-2 h-4 w-4" />
                                            Watch
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <h2 className="mb-3 text-lg font-semibold">Recorded lessons</h2>
                    {recordings.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No recorded lessons yet.</p>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {recordings.map((recording) => (
                                <Card key={recording.id}>
                                    <CardContent className="flex items-center justify-between gap-4 pt-6">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">{recording.title}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {recording.offering?.subject?.name ?? '—'} ·{' '}
                                                {formatDuration(recording.duration_seconds)}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                setError(null);
                                                setPlayer({ kind: 'recording', id: recording.id });
                                            }}
                                        >
                                            <Play className="me-2 h-4 w-4" />
                                            Play
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppShell>
    );
}
