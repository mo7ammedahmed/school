<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Timetable') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c1a16; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p.meta { margin: 0 0 14px; color: #74705f; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd8c9; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f2efe8; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        .muted { color: #74705f; }
    </style>
</head>
<body>
    <h1>{{ $schoolName }}</h1>
    <p class="meta">{{ __('Timetable') }} &middot; {{ now()->toDateString() }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Time') }}</th>
                @foreach ($days as $day)
                    <th>{{ ucfirst($day) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($entries->groupBy(fn ($entry) => $entry->startsAt().' - '.$entry->endsAt()) as $slot => $slotEntries)
                <tr>
                    <td class="muted">{{ $slot }}</td>
                    @foreach ($days as $day)
                        <td>
                            @foreach ($slotEntries->where('day_of_week', $day) as $entry)
                                <div>{{ $entry->offering?->subject?->name ?? '—' }}</div>
                                <div class="muted">{{ $entry->section?->name }}</div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($days) + 1 }}">{{ __('No timetable entries.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
