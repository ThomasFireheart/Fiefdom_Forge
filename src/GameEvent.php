<?php

namespace FiefdomForge;

class GameEvent
{
    private ?int $id = null;
    private int $userId;
    private string $eventType;
    private string $message;
    private ?int $relatedCitizenId = null;
    private int $gameDay;
    private int $gameYear;
    private string $category = 'neutral';

    private Database $db;

    // Event type to category mapping
    private const TYPE_CATEGORIES = [
        // Positive events
        'traveling_merchant' => 'positive',
        'bountiful_harvest' => 'positive',
        'skilled_immigrant' => 'positive',
        'festival' => 'positive',
        'good_weather' => 'positive',
        'treasure_found' => 'positive',
        'royal_favor' => 'positive',
        'miraculous_recovery' => 'positive',
        'birth' => 'positive',
        'marriage' => 'positive',
        'immigration' => 'positive',
        'area_created' => 'positive',
        'buildings_created' => 'positive',
        'hired' => 'positive',

        // Negative events
        'illness_outbreak' => 'negative',
        'harsh_weather' => 'negative',
        'fire' => 'negative',
        'theft' => 'negative',
        'crop_blight' => 'negative',
        'building_collapse' => 'negative',
        'worker_accident' => 'negative',
        'tax_collector' => 'negative',
        'death' => 'negative',
        'citizen_death' => 'negative',

        // Neutral events
        'wandering_minstrel' => 'neutral',
        'mysterious_stranger' => 'neutral',
        'wildlife_sighting' => 'neutral',
        'market_day' => 'neutral',
        'pilgrim_passage' => 'neutral',
        'season_change' => 'neutral',
        'year_change' => 'neutral',
        'aging' => 'neutral',
        'production' => 'neutral',
        'tax' => 'neutral',

        // Special events
        'comet_sighting' => 'special',
        'wandering_knight' => 'special',
        'ancient_discovery' => 'special',
        'achievement' => 'special',
    ];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
    }

    public static function create(
        int $userId,
        string $eventType,
        string $message,
        int $gameDay,
        int $gameYear,
        ?int $relatedCitizenId = null
    ): GameEvent {
        $event = new GameEvent($userId);
        $event->eventType = $eventType;
        $event->message = $message;
        $event->gameDay = $gameDay;
        $event->gameYear = $gameYear;
        $event->relatedCitizenId = $relatedCitizenId;

        $event->id = $event->db->insert('game_events', [
            'user_id' => $userId,
            'event_type' => $eventType,
            'message' => $message,
            'related_citizen_id' => $relatedCitizenId,
            'game_day' => $gameDay,
            'game_year' => $gameYear,
        ]);

        return $event;
    }

    public static function getRecent(int $userId, int $limit = 20): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT * FROM game_events WHERE user_id = ? ORDER BY id DESC LIMIT ?",
            [$userId, $limit]
        );

        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getByDay(int $userId, int $day, int $year): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT * FROM game_events WHERE user_id = ? AND game_day = ? AND game_year = ? ORDER BY id DESC",
            [$userId, $day, $year]
        );

        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function fromArray(array $data): GameEvent
    {
        $event = new GameEvent($data['user_id']);
        $event->id = $data['id'];
        $event->eventType = $data['event_type'];
        $event->message = $data['message'];
        $event->relatedCitizenId = $data['related_citizen_id'];
        $event->gameDay = $data['game_day'];
        $event->gameYear = $data['game_year'];

        return $event;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->eventType,
            'message' => $this->message,
            'day' => $this->gameDay,
            'year' => $this->gameYear,
            'citizen_id' => $this->relatedCitizenId,
            'category' => $this->getCategory(),
        ];
    }

    public function getCategory(): string
    {
        return self::TYPE_CATEGORIES[$this->eventType] ?? 'neutral';
    }

    public static function getCategoryIcon(string $category): string
    {
        return match ($category) {
            'positive' => '+',
            'negative' => '!',
            'special' => '*',
            default => '-',
        };
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getEventType(): string { return $this->eventType; }
    public function getMessage(): string { return $this->message; }
    public function getRelatedCitizenId(): ?int { return $this->relatedCitizenId; }
    public function getGameDay(): int { return $this->gameDay; }
    public function getGameYear(): int { return $this->gameYear; }
}
