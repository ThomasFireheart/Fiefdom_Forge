<?php

namespace FiefdomForge;

class HistoricalStats
{
    private int $userId;
    private Database $db;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
    }

    /**
     * Record current stats snapshot
     */
    public function recordSnapshot(
        int $gameDay,
        int $gameYear,
        int $population,
        int $treasury,
        int $buildings,
        int $avgHappiness,
        int $avgHealth
    ): void {
        $this->db->insert('historical_stats', [
            'user_id' => $this->userId,
            'game_day' => $gameDay,
            'game_year' => $gameYear,
            'population' => $population,
            'treasury' => $treasury,
            'buildings' => $buildings,
            'avg_happiness' => $avgHappiness,
            'avg_health' => $avgHealth,
        ]);
    }

    /**
     * Get historical data for a specific metric
     */
    public function getHistory(string $metric, int $limit = 100): array
    {
        $validMetrics = ['population', 'treasury', 'buildings', 'avg_happiness', 'avg_health'];
        if (!in_array($metric, $validMetrics)) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT game_day, game_year, {$metric} as value
             FROM historical_stats
             WHERE user_id = ?
             ORDER BY game_year DESC, game_day DESC
             LIMIT ?",
            [$this->userId, $limit]
        );
    }

    /**
     * Get all historical data (for charts)
     */
    public function getAllHistory(int $limit = 100): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM historical_stats
             WHERE user_id = ?
             ORDER BY game_year ASC, game_day ASC
             LIMIT ?",
            [$this->userId, $limit]
        );
    }

    /**
     * Get data for charts (formatted for Chart.js)
     */
    public function getChartData(int $limit = 50): array
    {
        $history = $this->getAllHistory($limit);

        $labels = [];
        $population = [];
        $treasury = [];
        $happiness = [];
        $health = [];

        foreach ($history as $record) {
            $labels[] = "Y{$record['game_year']} D{$record['game_day']}";
            $population[] = $record['population'];
            $treasury[] = $record['treasury'];
            $happiness[] = $record['avg_happiness'];
            $health[] = $record['avg_health'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'population' => $population,
                'treasury' => $treasury,
                'happiness' => $happiness,
                'health' => $health,
            ],
        ];
    }

    /**
     * Check if we should record (avoid recording too frequently)
     * Only record once per game day
     */
    public function shouldRecord(int $gameDay, int $gameYear): bool
    {
        $existing = $this->db->fetch(
            "SELECT id FROM historical_stats
             WHERE user_id = ? AND game_day = ? AND game_year = ?",
            [$this->userId, $gameDay, $gameYear]
        );

        return $existing === null;
    }

    /**
     * Get latest snapshot
     */
    public function getLatest(): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM historical_stats
             WHERE user_id = ?
             ORDER BY game_year DESC, game_day DESC
             LIMIT 1",
            [$this->userId]
        );
    }
}
