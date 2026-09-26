<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\DTO;

/**
 * One entry on the academic calendar, normalised across every source so the
 * month/week/day views never have to know where an item came from.
 */
final readonly class CalendarItem
{
    public function __construct(
        public string $date,
        public ?string $endDate,
        public string $type,
        public string $title,
        public string $source,
        public ?int $id = null,
        public ?string $href = null,
        public ?string $time = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'end_date' => $this->endDate,
            'type' => $this->type,
            'title' => $this->title,
            'source' => $this->source,
            'id' => $this->id,
            'href' => $this->href,
            'time' => $this->time,
        ];
    }
}
