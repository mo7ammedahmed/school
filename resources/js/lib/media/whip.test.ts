import { describe, expect, it } from 'vitest';
import { recordableVideoCodecPreferences } from './whip';

const codec = (mimeType: string) => ({ mimeType, clockRate: 90000 });

// The order a Chromium build reports: VP8 first, H264 next, then AV1 and VP9.
const capabilities = [
    codec('video/VP8'),
    codec('video/rtx'),
    codec('video/H264'),
    codec('video/AV1'),
    codec('video/VP9'),
    codec('video/VP9'),
];

describe('recordable video codec preferences', () => {
    it('never offers VP8, which the recorder cannot write', () => {
        const offered = recordableVideoCodecPreferences(capabilities).flat();

        expect(offered.some((candidate) => candidate.mimeType.toLowerCase() === 'video/vp8')).toBe(false);
    });

    it('offers H264 first, for the browsers that can use it', () => {
        expect(recordableVideoCodecPreferences(capabilities)[0].map((candidate) => candidate.mimeType)).toEqual([
            'video/H264',
            'video/AV1',
            'video/VP9',
            'video/VP9',
        ]);
    });

    it('falls back to a list without H264, which some browsers refuse outright', () => {
        expect(recordableVideoCodecPreferences(capabilities)[1].map((candidate) => candidate.mimeType)).toEqual([
            'video/AV1',
            'video/VP9',
            'video/VP9',
        ]);
    });

    it('drops a list that has nothing left to prefer', () => {
        expect(recordableVideoCodecPreferences([codec('video/VP8'), codec('video/rtx')])).toEqual([]);
    });
});
