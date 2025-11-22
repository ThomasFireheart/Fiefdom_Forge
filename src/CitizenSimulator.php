<?php

namespace FiefdomForge;

class CitizenSimulator
{
    private int $userId;
    private GameState $gameState;
    private Database $db;

    // Simulation constants
    private const BIRTH_CHANCE = 15; // % chance per eligible couple per year
    private const MARRIAGE_CHANCE = 10; // % chance per eligible adult per year
    private const ILLNESS_CHANCE = 5; // % chance per citizen per season

    // Name pools for generating citizens
    private const MALE_NAMES = [
        'William', 'John', 'Thomas', 'Robert', 'Richard', 'Henry', 'Edward', 'George',
        'Edmund', 'Walter', 'Hugh', 'Ralph', 'Roger', 'Geoffrey', 'Simon', 'Adam',
        'Peter', 'Stephen', 'Nicholas', 'Gilbert', 'Bartholomew', 'Martin', 'Benedict'
    ];

    private const FEMALE_NAMES = [
        'Alice', 'Margaret', 'Joan', 'Agnes', 'Elizabeth', 'Mary', 'Emma', 'Matilda',
        'Eleanor', 'Isabella', 'Catherine', 'Anne', 'Cecily', 'Edith', 'Beatrice',
        'Rose', 'Mabel', 'Juliana', 'Millicent', 'Avice', 'Sybil', 'Clarice'
    ];

    public function __construct(int $userId, GameState $gameState)
    {
        $this->userId = $userId;
        $this->gameState = $gameState;
        $this->db = Database::getInstance();
    }

    public function simulateDay(): array
    {
        $events = [];

        // Get all living citizens
        $citizens = $this->getLivingCitizens();

        // Daily health/happiness fluctuations
        foreach ($citizens as $citizen) {
            $this->simulateDailyLife($citizen);
        }

        // Check for new year events (aging happens yearly)
        if ($this->gameState->getCurrentDay() === 1) {
            $events = array_merge($events, $this->simulateYearlyEvents($citizens));
        }

        // Seasonal events
        if ($this->gameState->getDayInSeason() === 1) {
            $events = array_merge($events, $this->simulateSeasonalEvents($citizens));
        }

        return $events;
    }

    private function getLivingCitizens(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );

        return array_map(fn($row) => Citizen::fromArray($row), $rows);
    }

    private function simulateDailyLife(Citizen $citizen): void
    {
        // Small random happiness fluctuation
        $happinessChange = rand(-2, 2);

        // Homeless penalty
        if ($citizen->getHomeBuildingId() === null) {
            $happinessChange -= 3;
            $citizen->modifyHealth(-1);
        }

        // Unemployed adult penalty
        if ($citizen->canWork() && $citizen->getWorkBusinessId() === null) {
            $happinessChange -= 2;
        }

        // Wealth affects happiness
        if ($citizen->getWealth() < 10) {
            $happinessChange -= 2;
        }

        $citizen->modifyHappiness($happinessChange);
        $citizen->save();
    }

    private function simulateYearlyEvents(array $citizens): array
    {
        $events = [];

        // Age all citizens
        foreach ($citizens as $citizen) {
            $ageEvents = $citizen->ageOneYear();
            $events = array_merge($events, $ageEvents);

            // Check for death
            $deathEvent = $citizen->checkDeath();
            if ($deathEvent) {
                $events[] = $deathEvent;
            }

            $citizen->save();
        }

        // Refresh citizen list (remove dead ones)
        $citizens = $this->getLivingCitizens();

        // Process marriages
        $events = array_merge($events, $this->processMarriages($citizens));

        // Process births
        $events = array_merge($events, $this->processBirths($citizens));

        return $events;
    }

    private function simulateSeasonalEvents(array $citizens): array
    {
        $events = [];
        $season = $this->gameState->getSeason();

        foreach ($citizens as $citizen) {
            // Winter is harsh
            if ($season === 'Winter') {
                if (rand(1, 100) <= self::ILLNESS_CHANCE * 2) {
                    $healthLoss = rand(5, 15);
                    $citizen->modifyHealth(-$healthLoss);
                    $events[] = [
                        'type' => 'illness',
                        'message' => "{$citizen->getName()} has fallen ill during the harsh winter.",
                        'citizen_id' => $citizen->getId(),
                    ];
                }
            }

            // Spring brings renewal
            if ($season === 'Spring') {
                $citizen->modifyHealth(rand(1, 5));
                $citizen->modifyHappiness(rand(1, 5));
            }

            // Summer abundance
            if ($season === 'Summer') {
                $citizen->modifyHappiness(rand(0, 3));
            }

            // Autumn harvest
            if ($season === 'Autumn') {
                // Workers get bonus from harvest
                if ($citizen->getWorkBusinessId() !== null) {
                    $citizen->modifyWealth(rand(1, 10));
                }
            }

            $citizen->save();
        }

        return $events;
    }

    private function processMarriages(array $citizens): array
    {
        $events = [];

        // Get eligible bachelors and bachelorettes
        $eligibleMales = array_filter($citizens, fn($c) => $c->canMarry() && $c->getGender() === 'male');
        $eligibleFemales = array_filter($citizens, fn($c) => $c->canMarry() && $c->getGender() === 'female');

        // Shuffle for randomness
        $eligibleMales = array_values($eligibleMales);
        $eligibleFemales = array_values($eligibleFemales);
        shuffle($eligibleMales);
        shuffle($eligibleFemales);

        // Try to match couples
        $maxMarriages = min(count($eligibleMales), count($eligibleFemales));
        for ($i = 0; $i < $maxMarriages; $i++) {
            if (rand(1, 100) <= self::MARRIAGE_CHANCE) {
                $male = $eligibleMales[$i];
                $female = $eligibleFemales[$i];

                // Create marriage
                $male->setSpouseId($female->getId());
                $female->setSpouseId($male->getId());

                // Happiness boost
                $male->modifyHappiness(20);
                $female->modifyHappiness(20);

                $male->save();
                $female->save();

                $events[] = [
                    'type' => 'marriage',
                    'message' => "{$male->getName()} and {$female->getName()} have been wed!",
                ];
            }
        }

        return $events;
    }

    private function processBirths(array $citizens): array
    {
        $events = [];

        // Get married women who can have children
        $eligibleMothers = array_filter($citizens, fn($c) => $c->canHaveChildren());

        // Calculate birth chance modifier based on conditions
        $birthChance = self::BIRTH_CHANCE;

        // Better conditions = higher birth rate
        $avgHappiness = 0;
        $avgHealth = 0;
        if (!empty($citizens)) {
            foreach ($citizens as $c) {
                $avgHappiness += $c->getHappiness();
                $avgHealth += $c->getHealth();
            }
            $avgHappiness = $avgHappiness / count($citizens);
            $avgHealth = $avgHealth / count($citizens);
        }

        // High happiness increases birth rate
        if ($avgHappiness >= 70) {
            $birthChance += 5;
        } elseif ($avgHappiness < 40) {
            $birthChance -= 5;
        }

        // Poor health decreases birth rate
        if ($avgHealth < 50) {
            $birthChance -= 5;
        }

        $birthChance = max(5, min(30, $birthChance)); // Clamp between 5% and 30%

        foreach ($eligibleMothers as $mother) {
            if (rand(1, 100) <= $birthChance) {
                // Determine gender
                $gender = rand(0, 1) === 0 ? 'male' : 'female';
                $name = $this->generateName($gender);

                // Create the baby
                $baby = Citizen::create($this->userId, $name, 0, $gender);

                // Baby lives with mother
                if ($mother->getHomeBuildingId()) {
                    $baby->setHomeBuildingId($mother->getHomeBuildingId());
                    $baby->save();
                }

                // Parents get happiness boost
                $mother->modifyHappiness(15);
                $mother->save();

                $father = Citizen::loadById($mother->getSpouseId());
                if ($father) {
                    $father->modifyHappiness(15);
                    $father->save();
                }

                $events[] = [
                    'type' => 'birth',
                    'message' => "A child named {$name} has been born to {$mother->getName()}!",
                    'citizen_id' => $baby->getId(),
                ];
            }
        }

        return $events;
    }

    public function generateName(string $gender): string
    {
        $names = $gender === 'male' ? self::MALE_NAMES : self::FEMALE_NAMES;
        return $names[array_rand($names)];
    }

    public function createStarterPopulation(int $count = 10): array
    {
        $events = [];

        for ($i = 0; $i < $count; $i++) {
            $gender = rand(0, 1) === 0 ? 'male' : 'female';
            $name = $this->generateName($gender);
            $age = rand(18, 45); // Working age adults

            Citizen::create($this->userId, $name, $age, $gender);
        }

        $events[] = [
            'type' => 'population_created',
            'message' => "{$count} citizens have joined your fiefdom!",
        ];

        return $events;
    }

    public function getPopulationStats(): array
    {
        $citizens = $this->getLivingCitizens();

        $stats = [
            'total' => count($citizens),
            'children' => 0,
            'adults' => 0,
            'elders' => 0,
            'employed' => 0,
            'housed' => 0,
            'married' => 0,
            'avg_health' => 0,
            'avg_happiness' => 0,
            'eligible_mothers' => 0,
            'birth_rate_modifier' => 0,
            'growth_potential' => 'stable',
        ];

        if (empty($citizens)) {
            return $stats;
        }

        $totalHealth = 0;
        $totalHappiness = 0;

        foreach ($citizens as $citizen) {
            $stage = $citizen->getLifeStage();
            if ($stage === 'child' || $stage === 'youth') {
                $stats['children']++;
            } elseif ($stage === 'elder') {
                $stats['elders']++;
            } else {
                $stats['adults']++;
            }

            if ($citizen->getWorkBusinessId() !== null) {
                $stats['employed']++;
            }
            if ($citizen->getHomeBuildingId() !== null) {
                $stats['housed']++;
            }
            if ($citizen->getSpouseId() !== null) {
                $stats['married']++;
            }
            if ($citizen->canHaveChildren()) {
                $stats['eligible_mothers']++;
            }

            $totalHealth += $citizen->getHealth();
            $totalHappiness += $citizen->getHappiness();
        }

        $stats['avg_health'] = round($totalHealth / count($citizens));
        $stats['avg_happiness'] = round($totalHappiness / count($citizens));

        // Calculate birth rate modifier
        $birthMod = 0;
        if ($stats['avg_happiness'] >= 70) {
            $birthMod += 5;
        } elseif ($stats['avg_happiness'] < 40) {
            $birthMod -= 5;
        }
        if ($stats['avg_health'] < 50) {
            $birthMod -= 5;
        }
        $stats['birth_rate_modifier'] = $birthMod;

        // Determine growth potential
        if ($stats['eligible_mothers'] === 0) {
            $stats['growth_potential'] = 'none';
        } elseif ($birthMod > 0) {
            $stats['growth_potential'] = 'high';
        } elseif ($birthMod < 0) {
            $stats['growth_potential'] = 'low';
        } else {
            $stats['growth_potential'] = 'stable';
        }

        return $stats;
    }
}
