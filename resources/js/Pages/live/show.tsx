import { useEffect, useRef, useState } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, MonitorUp, Radio, Square, Video } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { publishToWhip, type WhipSession } from '@/lib/media/whip';
import { formatDuration } from '@/lib/media/format';

type LiveSession = {
    id: number;
    title: string;
    status: string;
    recording_status: string;
    duration_seconds: number | null;
    stream_key: string;
    offering: { subject: { name: string } | null; section: { name: string } | null } | null;
    material: { id: number } | null;
};

type Media = {
    configured: boolean;
    whipUrl: string | null;
    whepUrl: string | null;
};

type StudioState = 'idle' | 'connecting' | 'publishing';

export default function LiveShow({
    session,
    canManage,
    media,
}: {
    session: LiveSession;
    canManage: boolean;
    media: Media;
}) {
    const previewRef = useRef<HTMLVideoElement>(null);
    const publisherRef = useRef<WhipSession | null>(null);
    const localStreamRef = useRef<MediaStream | null>(null);
    const [studio, setStudio] = useState<StudioState>('idle');
    const [error, setError] = useState<string | null>(null);

    const stopPublishing = () => {
        publisherRef.current?.close();
        publisherRef.current = null;

        for (const track of localStreamRef.current?.getTracks() ?? []) {
            track.stop();
        }

        localStreamRef.current = null;

        if (previewRef.current) {
            previewRef.current.srcObject = null;
        }

        setStudio('idle');
    };

    // A page left mid-broadcast must not keep the camera on.
    useEffect(
        () => () => {
            publisherRef.current?.close();

            for (const track of localStreamRef.current?.getTracks() ?? []) {
                track.stop();
            }
        },
        [],
    );

    const startRequest = () =>
        new Promise<void>((resolve, reject) => {
            router.post(
                `/live/${session.id}/start`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(),
                    onError: () => reject(new Error('The session could not be started.')),
                },
            );
        });

    const begin = async (source: 'camera' | 'screen') => {
        setError(null);
        setStudio('connecting');

        try {
            if (!media.configured || !media.whipUrl) {
                throw new Error('The live server is not configured. See the deployment guide.');
            }

            const stream =
                source === 'camera'
                    ? await navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                    : await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });

            localStreamRef.current = stream;

            if (previewRef.current) {
                previewRef.current.srcObject = stream;
                void previewRef.current.play().catch(() => {});
            }

            // The row flips to `live` before the WHIP handshake so students see
            // the session the moment the teacher starts streaming.
            if (session.status === 'draft') {
                await startRequest();
            }

            publisherRef.current = await publishToWhip(media.whipUrl, stream);
            setStudio('publishing');
        } catch (caught) {
            stopPublishing();
            setError(caught instanceof Error ? caught.message : 'The broadcast could not be started.');
        }
    };

    const endSession = () => {
        stopPublishing();
        router.post(`/live/${session.id}/end`);
    };

    return (
        <AppShell
            title="Live Studio"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Live Lessons', href: '/live' },
                { label: session.title },
            ]}
        >
            <PageHeader
                title={session.title}
                description={`${session.offering?.subject?.name ?? '—'} · ${session.offering?.section?.name ?? '—'}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/live">
                                <ArrowLeft className="me-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>
                        {canManage && (
                            <Button variant="destructive" onClick={endSession} disabled={session.status === 'ended'}>
                                <Square className="me-2 h-4 w-4" />
                                End session
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <Card className="overflow-hidden">
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle>Broadcast</CardTitle>
                        {studio === 'publishing' ? (
                            <Badge variant="success">
                                <Radio className="me-1 h-3 w-3" />
                                Live
                            </Badge>
                        ) : (
                            <Badge variant="neutral">Off air</Badge>
                        )}
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="aspect-video w-full overflow-hidden rounded-lg border border-border/70 bg-black">
                            {/* The camera preview; it stays black until a source starts. */}
                            <video ref={previewRef} className="h-full w-full object-contain" muted playsInline />
                        </div>

                        {error && (
                            <p role="alert" className="text-sm text-destructive">
                                {error}
                            </p>
                        )}

                        {!media.configured && (
                            <p className="text-sm text-muted-foreground">
                                The live server address is not configured, so the studio cannot publish. Set
                                MEDIA_WEBRTC_URL and restart the app.
                            </p>
                        )}

                        {canManage && (
                            <div className="flex flex-wrap gap-3">
                                {studio === 'publishing' ? (
                                    <Button variant="outline" onClick={stopPublishing}>
                                        Stop sharing
                                    </Button>
                                ) : (
                                    <>
                                        <Button onClick={() => void begin('camera')} disabled={studio === 'connecting'}>
                                            <Video className="me-2 h-4 w-4" />
                                            Share camera
                                        </Button>
                                        <Button
                                            variant="outline"
                                            onClick={() => void begin('screen')}
                                            disabled={studio === 'connecting'}
                                        >
                                            <MonitorUp className="me-2 h-4 w-4" />
                                            Share screen
                                        </Button>
                                    </>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Session</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Status</span>
                            {session.status === 'live' ? (
                                <Badge variant="success">Live</Badge>
                            ) : session.status === 'ended' ? (
                                <Badge variant="neutral">Ended</Badge>
                            ) : (
                                <Badge variant="secondary">Draft</Badge>
                            )}
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Duration</span>
                            <span className="tabular-nums">{formatDuration(session.duration_seconds)}</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Recording</span>
                            {session.recording_status === 'ready' ? (
                                <Badge variant="success">Ready</Badge>
                            ) : session.recording_status === 'failed' ? (
                                <Badge variant="error">Failed</Badge>
                            ) : session.status === 'ended' ? (
                                <Badge variant="warning">Preparing</Badge>
                            ) : (
                                <Badge variant="neutral">Starts with the lesson</Badge>
                            )}
                        </div>
                        {session.material && (
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Material</span>
                                <Link className="text-primary underline-offset-4 hover:underline" href={`/materials/${session.material.id}`}>
                                    Open recording
                                </Link>
                            </div>
                        )}
                        <p className="pt-2 text-xs leading-relaxed text-muted-foreground">
                            Students watch from their portal. The recording is written by the live server and lands in
                            this subject&apos;s materials when the session ends.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
