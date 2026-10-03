/**
 * A lesson length a person can read: `4:05`, `1:02:30`, or an em dash.
 *
 * Recordings carry a duration in seconds (ffprobe fills it), sessions in
 * progress may not; both render through here so the lists agree.
 */
export function formatDuration(seconds: number | null | undefined): string {
    if (seconds === null || seconds === undefined || seconds <= 0) {
        return '—';
    }

    const whole = Math.floor(seconds);
    const hours = Math.floor(whole / 3600);
    const minutes = Math.floor((whole % 3600) / 60);
    const secs = whole % 60;

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    return `${minutes}:${String(secs).padStart(2, '0')}`;
}
