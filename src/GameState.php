<?php

namespace FiefdomForge;

class GameState
{
    private int $userId;
    private int $currentDay = 1;
    private int $currentYear = 1;
    private int $treasury = 1000;
    private array $settings = [];
    private Database $db;

    public const DAYS_PER_YEAR = 360;
    public const SEASONS = ['Spring', 'Summer', 'Autumn', 'Winter'];
    public const DAYS_PER_SEASON = 90;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
        $this->load();
    }

    private function load(): void
    {
        $state = $this->db->fetch(
            "SELECT * FROM game_states WHERE user_id = ?",
            [$this->userId]
        );

        if ($state) {
            $this->currentDay = $state['current_day'];
            $this->currentYear = $state['current_year'];
            $this->treasury = $state['treasury'];
            $this->settings = json_decode($state['settings'] ?? '{}', true);
        } else {
            $this->create();
        }
    }

    private function create(): void
    {
        $this->db->insert('game_states', [
            'user_id' => $this->userId,
            'current_day' => $this->currentDay,
            'current_year' => $this->currentYear,
            'treasury' => $this->treasury,
            'settings' => json_encode($this->settings),
        ]);
    }

    public function save(): void
    {
        $this->db->update(
            'game_states',
            [
                'current_day' => $this->currentDay,
                'current_year' => $this->currentYear,
                'treasury' => $this->treasury,
                'settings' => json_encode($this->settings),
            ],
            'user_id = ?',
            [$this->userId]
        );
    }

    public function advanceDay(): array
    {
        $events = [];

        $this->currentDay++;

        // Check for year change
        if ($this->currentDay > self::DAYS_PER_YEAR) {
            $this->currentDay = 1;
            $this->currentYear++;
            $events[] = [
                'type' => 'year_change',
                'message' => "A new year begins! Welcome to Year {$this->currentYear}.",
            ];
        }

        // Check for season change
        $dayOfYear = $this->currentDay;
        $seasonIndex = (int) floor(($dayOfYear - 1) / self::DAYS_PER_SEASON);
        $dayInSeason = (($dayOfYear - 1) % self::DAYS_PER_SEASON) + 1;

        if ($dayInSeason === 1) {
            $season = self::SEASONS[$seasonIndex];
            $events[] = [
                'type' => 'season_change',
                'message' => "{$season} has arrived.",
            ];
        }

        $this->save();

        return $events;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCurrentDay(): int
    {
        return $this->currentDay;
    }

    public function getCurrentYear(): int
    {
        return $this->currentYear;
    }

    public function getTotalDays(): int
    {
        return (($this->currentYear - 1) * self::DAYS_PER_YEAR) + $this->currentDay;
    }

    public function getSeason(): string
    {
        $seasonIndex = (int) floor(($this->currentDay - 1) / self::DAYS_PER_SEASON);
        return self::SEASONS[$seasonIndex];
    }

    public function getDayInSeason(): int
    {
        return (($this->currentDay - 1) % self::DAYS_PER_SEASON) + 1;
    }

    public function getTreasury(): int
    {
        return $this->treasury;
    }

    public function addTreasury(int $amount): void
    {
        $this->treasury += $amount;
    }

    public function subtractTreasury(int $amount): bool
    {
        if ($this->treasury < $amount) {
            return false;
        }
        $this->treasury -= $amount;
        return true;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $this->settings[$key] = $value;
    }
}
