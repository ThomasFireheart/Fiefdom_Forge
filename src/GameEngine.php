<?php

namespace FiefdomForge;

class GameEngine
{
    private int $userId;
    private GameState $gameState;
    private CitizenSimulator $citizenSimulator;
    private EconomySimulator $economySimulator;
    private RandomEventSystem $randomEventSystem;
    private Achievement $achievementSystem;
    private Database $db;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
        $this->gameState = new GameState($userId);
        $this->citizenSimulator = new CitizenSimulator($userId, $this->gameState);
        $this->economySimulator = new EconomySimulator($userId, $this->gameState);
        $this->randomEventSystem = new RandomEventSystem($userId, $this->gameState);
        $this->achievementSystem = new Achievement($userId);
    }

    public function initializeNewGame(): array
    {
        $events = [];

        // Seed goods if not already done
        Good::seedGoods();

        // Seed skills if not already done
        Skill::seedSkills();

        // Seed roles if not already done
        Role::seedRoles();

        // Seed starter inventory
        Inventory::seedStarterInventory($this->userId);

        // Create starter area
        $existingAreas = Area::getAll();
        if (empty($existingAreas)) {
            Area::create('Town Center', 'The heart of your fiefdom', 200);
            Area::create('Farmlands', 'Fertile lands for agriculture', 150);
            Area::create('Market District', 'Where commerce thrives', 100);

            $events[] = [
                'type' => 'area_created',
                'message' => 'The foundational areas of your fiefdom have been established.',
            ];
        }

        // Create starter population
        $populationStats = $this->citizenSimulator->getPopulationStats();
        if ($populationStats['total'] === 0) {
            $events = array_merge($events, $this->citizenSimulator->createStarterPopulation(10));
        }

        // Create some starter buildings
        $buildingCount = Building::countAll();
        if ($buildingCount === 0) {
            $areas = Area::getAll();
            $townCenter = $areas[0] ?? null;

            if ($townCenter) {
                // Create some starter cottages
                for ($i = 1; $i <= 5; $i++) {
                    Building::createFromTemplate('cottage', "Cottage #{$i}", $townCenter->getId());
                }

                // Create a few businesses
                $workshop = Building::createFromTemplate('workshop', 'Town Workshop', $townCenter->getId());
                if ($workshop) {
                    Business::create('Town Smithy', $workshop->getId(), 'blacksmith');
                }

                $farmBuilding = Building::createFromTemplate('farm', 'Community Farm', $townCenter->getId());
                if ($farmBuilding) {
                    Business::create('Town Farm', $farmBuilding->getId(), 'farm');
                }

                $events[] = [
                    'type' => 'buildings_created',
                    'message' => 'Starter buildings have been constructed.',
                ];
            }
        }

        // Assign homeless citizens to homes
        $this->assignHomelessCitizens();

        // Assign unemployed adults to jobs
        $this->assignUnemployedCitizens();

        // Log events
        foreach ($events as $event) {
            $this->logEvent($event);
        }

        return $events;
    }

    public function advanceDay(): array
    {
        $events = [];

        // Advance game time
        $timeEvents = $this->gameState->advanceDay();
        $events = array_merge($events, $timeEvents);

        // Run citizen simulation
        $citizenEvents = $this->citizenSimulator->simulateDay();
        $events = array_merge($events, $citizenEvents);

        // Run economy simulation
        $economyEvents = $this->economySimulator->simulateDay();
        $events = array_merge($events, $economyEvents);

        // Random events (using the expanded event system)
        $randomEvents = $this->randomEventSystem->processDay();
        $events = array_merge($events, $randomEvents);

        // Log all events
        foreach ($events as $event) {
            $this->logEvent($event);
        }

        // Check for new achievements
        $stats = $this->getDashboardStats();
        $newAchievements = $this->achievementSystem->checkAchievements($stats);

        // Record historical stats
        $this->recordHistoricalStats($stats);
        foreach ($newAchievements as $achievement) {
            $events[] = [
                'type' => 'achievement',
                'message' => "Achievement Unlocked: {$achievement['name']}!",
            ];
            $this->logEvent([
                'type' => 'achievement',
                'message' => "Achievement Unlocked: {$achievement['name']} - {$achievement['description']}",
            ]);
        }

        return $events;
    }

    public function advanceDays(int $days): array
    {
        $allEvents = [];

        for ($i = 0; $i < $days; $i++) {
            $events = $this->advanceDay();
            $allEvents = array_merge($allEvents, $events);
        }

        return $allEvents;
    }

    private function assignHomelessCitizens(): void
    {
        $homeless = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1 AND home_building_id IS NULL",
            [$this->userId]
        );

        foreach ($homeless as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);

            // Find a house with space
            $houses = $this->db->fetchAll(
                "SELECT b.*,
                        (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) as occupants
                 FROM buildings b
                 WHERE b.type = 'house'
                   AND (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) < b.capacity
                 LIMIT 1"
            );

            if (!empty($houses)) {
                $citizen->setHomeBuildingId($houses[0]['id']);
                $citizen->save();
            }
        }
    }

    private function assignUnemployedCitizens(): void
    {
        $unemployed = $this->db->fetchAll(
            "SELECT * FROM citizens
             WHERE user_id = ? AND is_alive = 1 AND work_business_id IS NULL
             AND age >= ? AND age < ?",
            [$this->userId, Citizen::AGE_ADULT, Citizen::AGE_ELDER]
        );

        foreach ($unemployed as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);

            // Find a business with open positions
            $businesses = Business::getAll();
            foreach ($businesses as $business) {
                if ($business->canHire()) {
                    $business->hire($citizen);
                    break;
                }
            }
        }
    }

    private function logEvent(array $event): void
    {
        GameEvent::create(
            $this->userId,
            $event['type'],
            $event['message'],
            $this->gameState->getCurrentDay(),
            $this->gameState->getCurrentYear(),
            $event['citizen_id'] ?? null
        );
    }

    private function recordHistoricalStats(array $stats): void
    {
        $historicalStats = new HistoricalStats($this->userId);

        // Only record once per game day
        if (!$historicalStats->shouldRecord(
            $this->gameState->getCurrentDay(),
            $this->gameState->getCurrentYear()
        )) {
            return;
        }

        $historicalStats->recordSnapshot(
            $this->gameState->getCurrentDay(),
            $this->gameState->getCurrentYear(),
            $stats['population'],
            $stats['treasury'],
            $stats['buildings'],
            $stats['population_stats']['avg_happiness'],
            $stats['population_stats']['avg_health']
        );
    }

    public function getGameState(): GameState
    {
        return $this->gameState;
    }

    public function getDashboardStats(): array
    {
        $populationStats = $this->citizenSimulator->getPopulationStats();
        $economyStats = $this->economySimulator->getEconomyStats();
        $citizenNeeds = $this->economySimulator->getCitizenNeedsStats();

        return [
            'current_day' => $this->gameState->getCurrentDay(),
            'current_year' => $this->gameState->getCurrentYear(),
            'season' => $this->gameState->getSeason(),
            'day_in_season' => $this->gameState->getDayInSeason(),
            'treasury' => $this->gameState->getTreasury(),
            'population' => $populationStats['total'],
            'buildings' => Building::countAll(),
            'population_stats' => $populationStats,
            'economy_stats' => $economyStats,
            'citizen_needs' => $citizenNeeds,
        ];
    }

    public function getRecentEvents(int $limit = 10): array
    {
        $events = GameEvent::getRecent($this->userId, $limit);
        return array_map(fn($e) => $e->toArray(), $events);
    }
}
