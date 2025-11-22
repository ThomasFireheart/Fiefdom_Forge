<?php

namespace FiefdomForge;

class Achievement
{
    private int $userId;
    private Database $db;

    // Achievement definitions
    public const ACHIEVEMENTS = [
        // Population achievements
        'first_citizen' => [
            'name' => 'First Citizen',
            'description' => 'Have at least 1 citizen in your fiefdom',
            'category' => 'population',
            'icon' => 'P',
            'requirement' => ['type' => 'population', 'value' => 1],
        ],
        'village' => [
            'name' => 'Village',
            'description' => 'Grow your population to 25 citizens',
            'category' => 'population',
            'icon' => 'V',
            'requirement' => ['type' => 'population', 'value' => 25],
        ],
        'town' => [
            'name' => 'Town',
            'description' => 'Grow your population to 50 citizens',
            'category' => 'population',
            'icon' => 'T',
            'requirement' => ['type' => 'population', 'value' => 50],
        ],
        'city' => [
            'name' => 'City',
            'description' => 'Grow your population to 100 citizens',
            'category' => 'population',
            'icon' => 'C',
            'requirement' => ['type' => 'population', 'value' => 100],
        ],

        // Building achievements
        'first_building' => [
            'name' => 'First Structure',
            'description' => 'Construct your first building',
            'category' => 'building',
            'icon' => 'B',
            'requirement' => ['type' => 'buildings', 'value' => 1],
        ],
        'builder' => [
            'name' => 'Builder',
            'description' => 'Construct 10 buildings',
            'category' => 'building',
            'icon' => 'B',
            'requirement' => ['type' => 'buildings', 'value' => 10],
        ],
        'architect' => [
            'name' => 'Master Architect',
            'description' => 'Construct 25 buildings',
            'category' => 'building',
            'icon' => 'A',
            'requirement' => ['type' => 'buildings', 'value' => 25],
        ],
        'public_works' => [
            'name' => 'Public Works',
            'description' => 'Build a church, tavern, and market',
            'category' => 'building',
            'icon' => 'W',
            'requirement' => ['type' => 'public_buildings', 'value' => 3],
        ],

        // Economy achievements
        'entrepreneur' => [
            'name' => 'Entrepreneur',
            'description' => 'Establish your first business',
            'category' => 'economy',
            'icon' => 'E',
            'requirement' => ['type' => 'businesses', 'value' => 1],
        ],
        'merchant_lord' => [
            'name' => 'Merchant Lord',
            'description' => 'Establish 5 businesses',
            'category' => 'economy',
            'icon' => 'M',
            'requirement' => ['type' => 'businesses', 'value' => 5],
        ],
        'wealthy' => [
            'name' => 'Wealthy',
            'description' => 'Accumulate 1,000 gold in treasury',
            'category' => 'economy',
            'icon' => 'G',
            'requirement' => ['type' => 'treasury', 'value' => 1000],
        ],
        'rich' => [
            'name' => 'Rich',
            'description' => 'Accumulate 5,000 gold in treasury',
            'category' => 'economy',
            'icon' => 'R',
            'requirement' => ['type' => 'treasury', 'value' => 5000],
        ],
        'full_employment' => [
            'name' => 'Full Employment',
            'description' => 'Have all working-age adults employed',
            'category' => 'economy',
            'icon' => 'F',
            'requirement' => ['type' => 'employment_rate', 'value' => 100],
        ],

        // Time achievements
        'first_year' => [
            'name' => 'First Anniversary',
            'description' => 'Survive your first year',
            'category' => 'time',
            'icon' => 'Y',
            'requirement' => ['type' => 'years', 'value' => 1],
        ],
        'five_years' => [
            'name' => 'Established',
            'description' => 'Rule for 5 years',
            'category' => 'time',
            'icon' => 'Y',
            'requirement' => ['type' => 'years', 'value' => 5],
        ],
        'decade' => [
            'name' => 'A Decade of Rule',
            'description' => 'Rule for 10 years',
            'category' => 'time',
            'icon' => 'D',
            'requirement' => ['type' => 'years', 'value' => 10],
        ],

        // Special achievements
        'matchmaker' => [
            'name' => 'Matchmaker',
            'description' => 'Have 5 married couples',
            'category' => 'special',
            'icon' => 'H',
            'requirement' => ['type' => 'married_couples', 'value' => 5],
        ],
        'all_housed' => [
            'name' => 'No Homeless',
            'description' => 'House all citizens',
            'category' => 'special',
            'icon' => 'H',
            'requirement' => ['type' => 'housing_rate', 'value' => 100],
        ],
        'happy_realm' => [
            'name' => 'Happy Realm',
            'description' => 'Achieve average happiness above 80%',
            'category' => 'special',
            'icon' => 'S',
            'requirement' => ['type' => 'avg_happiness', 'value' => 80],
        ],
        'healthy_realm' => [
            'name' => 'Healthy Realm',
            'description' => 'Achieve average health above 80%',
            'category' => 'special',
            'icon' => '+',
            'requirement' => ['type' => 'avg_health', 'value' => 80],
        ],
    ];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
    }

    public function checkAchievements(array $stats): array
    {
        $newAchievements = [];
        $unlockedAchievements = $this->getUnlockedAchievements();

        foreach (self::ACHIEVEMENTS as $achievementId => $achievement) {
            // Skip already unlocked
            if (in_array($achievementId, $unlockedAchievements)) {
                continue;
            }

            if ($this->isAchievementMet($achievementId, $achievement, $stats)) {
                $this->unlockAchievement($achievementId);
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    private function isAchievementMet(string $achievementId, array $achievement, array $stats): bool
    {
        $requirement = $achievement['requirement'];
        $type = $requirement['type'];
        $value = $requirement['value'];

        return match ($type) {
            'population' => ($stats['population'] ?? 0) >= $value,
            'buildings' => ($stats['buildings'] ?? 0) >= $value,
            'businesses' => ($stats['economy_stats']['businesses_count'] ?? 0) >= $value,
            'treasury' => ($stats['treasury'] ?? 0) >= $value,
            'years' => ($stats['current_year'] ?? 1) > $value,
            'public_buildings' => $this->countPublicBuildings() >= $value,
            'employment_rate' => $this->calculateEmploymentRate($stats) >= $value,
            'housing_rate' => $this->calculateHousingRate($stats) >= $value,
            'married_couples' => (int)(($stats['population_stats']['married'] ?? 0) / 2) >= $value,
            'avg_happiness' => ($stats['population_stats']['avg_happiness'] ?? 0) >= $value,
            'avg_health' => ($stats['population_stats']['avg_health'] ?? 0) >= $value,
            default => false,
        };
    }

    private function countPublicBuildings(): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM buildings WHERE type = 'public'"
        );
        return $result['count'] ?? 0;
    }

    private function calculateEmploymentRate(array $stats): int
    {
        $adults = $stats['population_stats']['adults'] ?? 0;
        $employed = $stats['population_stats']['employed'] ?? 0;

        if ($adults === 0) {
            return 0;
        }

        return (int)(($employed / $adults) * 100);
    }

    private function calculateHousingRate(array $stats): int
    {
        $total = $stats['population'] ?? 0;
        $housed = $stats['population_stats']['housed'] ?? 0;

        if ($total === 0) {
            return 0;
        }

        return (int)(($housed / $total) * 100);
    }

    public function unlockAchievement(string $achievementId): void
    {
        // Store achievement unlock (we'll use the game_states settings JSON)
        $gameState = $this->db->fetch(
            "SELECT settings FROM game_states WHERE user_id = ?",
            [$this->userId]
        );

        $settings = [];
        if ($gameState && $gameState['settings']) {
            $settings = json_decode($gameState['settings'], true) ?? [];
        }

        if (!isset($settings['achievements'])) {
            $settings['achievements'] = [];
        }

        $settings['achievements'][$achievementId] = [
            'unlocked_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->query(
            "UPDATE game_states SET settings = ? WHERE user_id = ?",
            [json_encode($settings), $this->userId]
        );
    }

    public function getUnlockedAchievements(): array
    {
        $gameState = $this->db->fetch(
            "SELECT settings FROM game_states WHERE user_id = ?",
            [$this->userId]
        );

        if (!$gameState || !$gameState['settings']) {
            return [];
        }

        $settings = json_decode($gameState['settings'], true) ?? [];
        return array_keys($settings['achievements'] ?? []);
    }

    public function getAllAchievementsWithStatus(array $stats): array
    {
        $unlocked = $this->getUnlockedAchievements();
        $result = [];

        foreach (self::ACHIEVEMENTS as $id => $achievement) {
            $isUnlocked = in_array($id, $unlocked);
            $progress = $this->calculateProgress($id, $achievement, $stats);

            $result[] = [
                'id' => $id,
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'category' => $achievement['category'],
                'icon' => $achievement['icon'],
                'unlocked' => $isUnlocked,
                'progress' => $progress,
                'requirement' => $achievement['requirement']['value'],
            ];
        }

        return $result;
    }

    private function calculateProgress(string $achievementId, array $achievement, array $stats): int
    {
        $requirement = $achievement['requirement'];
        $type = $requirement['type'];
        $target = $requirement['value'];

        $current = match ($type) {
            'population' => $stats['population'] ?? 0,
            'buildings' => $stats['buildings'] ?? 0,
            'businesses' => $stats['economy_stats']['businesses_count'] ?? 0,
            'treasury' => $stats['treasury'] ?? 0,
            'years' => $stats['current_year'] ?? 1,
            'public_buildings' => $this->countPublicBuildings(),
            'employment_rate' => $this->calculateEmploymentRate($stats),
            'housing_rate' => $this->calculateHousingRate($stats),
            'married_couples' => (int)(($stats['population_stats']['married'] ?? 0) / 2),
            'avg_happiness' => $stats['population_stats']['avg_happiness'] ?? 0,
            'avg_health' => $stats['population_stats']['avg_health'] ?? 0,
            default => 0,
        };

        return min(100, (int)(($current / max(1, $target)) * 100));
    }

    public static function getCategories(): array
    {
        return [
            'population' => 'Population',
            'building' => 'Building',
            'economy' => 'Economy',
            'time' => 'Time',
            'special' => 'Special',
        ];
    }
}
