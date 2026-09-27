<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@shaped($schoolName) — @shaped($labels['timetable'])</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c1a16; }
        .masthead { width: 100%; border-bottom: 1px solid #ddd8c9; padding-bottom: 8px; margin-bottom: 14px; }
        .masthead td { border: 0; vertical-align: middle; }
        .mark { width: 34px; height: 34px; background-color: {{ $schoolColor }}; color: #ffffff; font-size: 17px; font-weight: bold; text-align: center; vertical-align: middle; }
        .logo { width: 46px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p.meta { margin: 0; color: #74705f; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #ddd8c9; padding: 6px 8px; text-align: {{ $isArabic ? 'right' : 'left' }}; vertical-align: top; }
        table.grid th { background: #f2efe8; font-size: 10px; letter-spacing: 0.04em; }
        .muted { color: #74705f; }
    </style>
</head>
<body>
    {{--
        Every string here goes through @shaped(): dompdf cannot join Arabic
        letters or reorder a right-to-left run, so the shaper supplies the
        positional glyphs in visual order. Non-Arabic locales pass through
        untouched.
    --}}
    <table class="masthead">
        <tr>
            @if ($schoolLogo)
                <td class="logo"><img class="logo" src="{{ $schoolLogo }}" alt=""></td>
            @else
                <td class="logo"><div class="mark">@shaped($schoolInitial)</div></td>
            @endif
            <td>
                <h1>@shaped($schoolName)</h1>
                <p class="meta">@shaped($labels['timetable']) &middot; @shaped($labels['printed']) @shaped(now()->toDateString())</p>
            </td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>@shaped($labels['time'])</th>
                @foreach ($days as $day)
                    <th>@shaped($dayLabels[$day])</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($entries->groupBy(fn ($entry) => $entry->startsAt().' - '.$entry->endsAt()) as $slot => $slotEntries)
                <tr>
                    <td class="muted">@shaped($slot)</td>
                    @foreach ($days as $day)
                        <td>
                            @foreach ($slotEntries->where('day_of_week', $day) as $entry)
                                <div>@shaped($entry->offering?->subject?->name ?? '—')</div>
                                <div class="muted">@shaped($entry->section?->name)</div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($days) + 1 }}">@shaped($labels['empty'])</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
