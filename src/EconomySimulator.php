<?php

namespace FiefdomForge;

class EconomySimulator
{
    private int $userId;
    private GameState $gameState;
    private Database $db;

    // Economic constants
    private const BASE_WAGE = 5;
    private const CONSUMPTION_RATE = 0.3; // Citizens spend 30% of wealth on goods
    private const TAX_COLLECTION_DAY = 30; // Day of season to collect taxes
    private const FOOD_PER_CITIZEN = 1; // Food units consumed per citizen per day

    // Seasonal production modifiers
    private const SEASONAL_MODIFIERS = [
        'Spring' => ['farm' => 1.2, 'ranch' => 1.1, 'default' => 1.0],
        'Summer' => ['farm' => 1.5, 'ranch' => 1.2, 'lumber_mill' => 1.1, 'default' => 1.0],
        'Autumn' => ['farm' => 1.3, 'ranch' => 1.0, 'default' => 1.0],
        'Winter' => ['farm' => 0.3, 'ranch' => 0.7, 'mine' => 1.2, 'default' => 0.9],
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

        // Daily production (with resource consumption)
        $events = array_merge($events, $this->processProduction());

        // Pay wages
        $events = array_merge($events, $this->processWages());

        // Food consumption for citizens
        $events = array_merge($events, $this->processFoodConsumption());

        // Citizen monetary consumption
        $events = array_merge($events, $this->processConsumption());

        // Building upkeep (monthly)
        if ($this->gameState->getCurrentDay() % 30 === 0) {
            $events = array_merge($events, $this->processBuildingUpkeep());
        }

        // Tax collection (end of each season)
        if ($this->gameState->getDayInSeason() === self::TAX_COLLECTION_DAY) {
            $events = array_merge($events, $this->collectTaxes());
        }

        return $events;
    }

    private function processProduction(): array
    {
        $events = [];
        $businesses = Business::getAll();
        $inventory = new Inventory($this->userId);
        $totalProduction = 0;
        $totalResourcesConsumed = 0;

        // Get current season for modifiers
        $season = $this->gameState->getSeason();
        $seasonModifiers = self::SEASONAL_MODIFIERS[$season] ?? self::SEASONAL_MODIFIERS['Spring'];

        foreach ($businesses as $business) {
            $building = Building::loadById($business->getBuildingId());
            if (!$building || !$building->isOperational()) {
                continue;
            }

            // Apply seasonal modifier
            $businessType = $business->getType();
            $seasonalMod = $seasonModifiers[$businessType] ?? $seasonModifiers['default'];

            // Get products this business makes
            foreach ($business->getProducts() as $goodId) {
                $good = Good::loadById($goodId);
                if (!$good) {
                    continue;
                }

                // Check if we can produce (have required resources)
                $canProduce = true;
                $resourcesNeeded = $good->getResourceNeeded();

                // For manufactured goods, check resource availability
                if (!$good->isResource() && !empty($resourcesNeeded)) {
                    foreach ($resourcesNeeded as $resourceId => $qty) {
                        if ($inventory->getQuantity((int)$resourceId) < $qty) {
                            $canProduce = false;
                            break;
                        }
                    }

                    // Consume resources if we can produce
                    if ($canProduce) {
                        foreach ($resourcesNeeded as $resourceId => $qty) {
                            $inventory->removeGood((int)$resourceId, $qty);
                            $totalResourcesConsumed += $qty;
                        }
                    }
                }

                if ($canProduce) {
                    // Calculate production quantity based on capacity and season
                    $baseQty = $good->isResource() ? 5 : 2;
                    $capacity = $business->getProductionCapacity();
                    $quantity = (int) floor($baseQty * $capacity * $seasonalMod);

                    if ($quantity > 0) {
                        // Add produced goods to inventory
                        $inventory->addGood($goodId, $quantity);
                        $value = $quantity * $good->getBasePrice();
                        $totalProduction += $value;
                        $business->addTreasury($value);
                    }
                }
            }

            // Successful production can improve reputation
            if ($totalProduction > 0 && rand(1, 100) <= 10) {
                $business->modifyReputation(1);
            }

            $business->save();
        }

        if ($totalProduction > 0) {
            $message = "Businesses produced goods worth {$totalProduction} gold today";
            if ($totalResourcesConsumed > 0) {
                $message .= " (consuming {$totalResourcesConsumed} resources)";
            }
            $events[] = [
                'type' => 'production',
                'message' => $message . ".",
            ];
        }

        return $events;
    }

    // Map business types to relevant skills
    private const BUSINESS_SKILLS = [
        'blacksmith' => ['Smithing'],
        'farm' => ['Farming'],
        'mine' => ['Mining'],
        'lumber' => ['Logging'],
        'carpenter' => ['Carpentry'],
        'weaver' => ['Weaving'],
        'brewery' => ['Brewing'],
        'baker' => ['Baking'],
        'fishery' => ['Fishing'],
        'hunter' => ['Hunting'],
        'merchant' => ['Trading'],
        'tavern' => ['Brewing', 'Trading'],
        'healer' => ['Medicine'],
    ];

    private function processWages(): array
    {
        $events = [];
        $totalWages = 0;

        $workers = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1 AND work_business_id IS NOT NULL",
            [$this->userId]
        );

        foreach ($workers as $workerData) {
            $worker = Citizen::fromArray($workerData);
            $business = Business::loadById($worker->getWorkBusinessId());

            if (!$business) {
                continue;
            }

            // Calculate wage based on business reputation and role
            $wage = self::BASE_WAGE;
            $wage += (int) floor($business->getReputation() / 20);

            // Pay from business treasury
            if ($business->subtractTreasury($wage)) {
                $worker->modifyWealth($wage);
                $totalWages += $wage;

                // Improve relevant skills through work (small chance each day)
                if (rand(1, 100) <= 15) {
                    $this->improveWorkerSkills($worker, $business);
                }

                // Paying wages on time slightly improves reputation
                if (rand(1, 100) <= 5) {
                    $business->modifyReputation(1);
                }
            } else {
                // Business can't afford wages - unhappy worker and reputation loss
                $worker->modifyHappiness(-5);
                $business->modifyReputation(-2);
            }

            $worker->save();
            $business->save();
        }

        return $events;
    }

    private function improveWorkerSkills(Citizen $worker, Business $business): void
    {
        $businessType = $business->getType();
        $relevantSkills = self::BUSINESS_SKILLS[$businessType] ?? [];

        foreach ($relevantSkills as $skillName) {
            $skill = Skill::loadByName($skillName);
            if ($skill) {
                $currentLevel = $worker->getSkillLevel($skill->getId());
                // Skill gain is slower at higher levels
                $gainChance = max(10, 100 - $currentLevel);
                if (rand(1, 100) <= $gainChance) {
                    $worker->setSkillLevel($skill->getId(), $currentLevel + 1);
                }
            }
        }
    }

    private function processFoodConsumption(): array
    {
        $events = [];
        $inventory = new Inventory($this->userId);

        // Food goods in order of preference: Bread is better than raw Wheat
        $breadId = Good::loadByName('Bread')?->getId();
        $wheatId = Good::loadByName('Wheat')?->getId();

        $citizens = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );

        $totalCitizens = count($citizens);
        $fedCitizens = 0;
        $hungryCount = 0;

        // Apply seasonal food consumption modifier
        $season = $this->gameState->getSeason();
        $foodMultiplier = ($season === 'Winter') ? 1.5 : 1.0; // Need more food in winter

        foreach ($citizens as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);
            $foodNeeded = (int) ceil(self::FOOD_PER_CITIZEN * $foodMultiplier);
            $fed = false;

            // Try to consume bread first (more nutritious)
            if ($breadId && $inventory->getQuantity($breadId) >= $foodNeeded) {
                $inventory->removeGood($breadId, $foodNeeded);
                $fed = true;
                // Bread gives happiness bonus
                if (rand(1, 100) <= 20) {
                    $citizen->modifyHappiness(1);
                }
            }
            // Fall back to wheat
            elseif ($wheatId && $inventory->getQuantity($wheatId) >= $foodNeeded * 2) {
                // Wheat is less efficient (need 2x)
                $inventory->removeGood($wheatId, $foodNeeded * 2);
                $fed = true;
            }

            if ($fed) {
                $fedCitizens++;
                // Being fed helps health
                if (rand(1, 100) <= 10) {
                    $citizen->modifyHealth(1);
                }
            } else {
                // Hungry citizen suffers
                $hungryCount++;
                $citizen->modifyHappiness(-5);
                $citizen->modifyHealth(-2);
            }

            $citizen->save();
        }

        // Generate event for food status
        if ($hungryCount > 0) {
            $events[] = [
                'type' => 'hunger',
                'category' => 'negative',
                'message' => "{$hungryCount} citizens went hungry today! Build farms and bakeries.",
            ];
        }

        return $events;
    }

    private function processConsumption(): array
    {
        $events = [];

        $citizens = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );

        foreach ($citizens as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);

            // Citizens consume based on their wealth
            $consumption = (int) floor($citizen->getWealth() * self::CONSUMPTION_RATE / 30);
            $consumption = max(1, min($consumption, 10)); // Min 1, max 10 per day

            if ($citizen->getWealth() >= $consumption) {
                $citizen->modifyWealth(-$consumption);

                // Consumption increases happiness slightly
                if (rand(1, 10) <= 3) {
                    $citizen->modifyHappiness(1);
                }
            } else {
                // Can't afford basic needs
                $citizen->modifyHappiness(-3);
                $citizen->modifyHealth(-1);
            }

            $citizen->save();
        }

        return $events;
    }

    private function processBuildingUpkeep(): array
    {
        $events = [];
        $totalUpkeep = 0;

        $buildings = $this->db->fetchAll("SELECT * FROM buildings");

        foreach ($buildings as $buildingData) {
            $building = Building::fromArray($buildingData);
            $upkeep = $building->getUpkeepCost();

            if ($upkeep > 0) {
                // Try to pay from treasury
                if ($this->gameState->subtractTreasury($upkeep)) {
                    $totalUpkeep += $upkeep;
                } else {
                    // Can't afford upkeep - building degrades
                    $building->degrade(5);
                    $building->save();
                }
            }

            // Natural degradation
            if (rand(1, 100) <= 10) {
                $building->degrade(1);
                $building->save();
            }
        }

        if ($totalUpkeep > 0) {
            $this->gameState->save();
            $events[] = [
                'type' => 'upkeep',
                'message' => "Building upkeep cost {$totalUpkeep} gold this month.",
            ];
        }

        return $events;
    }

    private function collectTaxes(): array
    {
        $events = [];
        $totalTax = 0;

        $areas = Area::getAll();

        foreach ($areas as $area) {
            $taxRate = $area->getTaxRate();

            // Get all citizens in this area
            $citizens = $this->db->fetchAll(
                "SELECT c.* FROM citizens c
                 JOIN buildings b ON c.home_building_id = b.id
                 WHERE b.area_id = ? AND c.is_alive = 1 AND c.user_id = ?",
                [$area->getId(), $this->userId]
            );

            foreach ($citizens as $citizenData) {
                $citizen = Citizen::fromArray($citizenData);
                $tax = (int) floor($citizen->getWealth() * $taxRate);

                if ($tax > 0 && $citizen->getWealth() >= $tax) {
                    $citizen->modifyWealth(-$tax);
                    $totalTax += $tax;

                    // High taxes make citizens unhappy
                    if ($taxRate > 0.1) {
                        $citizen->modifyHappiness(-2);
                    }

                    $citizen->save();
                }
            }
        }

        if ($totalTax > 0) {
            $this->gameState->addTreasury($totalTax);
            $this->gameState->save();

            $events[] = [
                'type' => 'tax_collection',
                'message' => "Collected {$totalTax} gold in taxes this season.",
            ];
        }

        return $events;
    }

    public function getEconomyStats(): array
    {
        $stats = [
            'treasury' => $this->gameState->getTreasury(),
            'total_citizen_wealth' => 0,
            'total_business_treasury' => 0,
            'employed_count' => 0,
            'businesses_count' => 0,
            'production_capacity' => 0,
        ];

        // Citizen wealth
        $result = $this->db->fetch(
            "SELECT SUM(wealth) as total FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );
        $stats['total_citizen_wealth'] = $result['total'] ?? 0;

        // Business stats
        $businesses = Business::getAll();
        $stats['businesses_count'] = count($businesses);

        foreach ($businesses as $business) {
            $stats['total_business_treasury'] += $business->getTreasury();
            $stats['production_capacity'] += $business->getProductionCapacity();
        }

        // Employment
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM citizens WHERE user_id = ? AND is_alive = 1 AND work_business_id IS NOT NULL",
            [$this->userId]
        );
        $stats['employed_count'] = $result['count'] ?? 0;

        return $stats;
    }

    /**
     * Get citizen needs status (food, housing, etc.)
     */
    public function getCitizenNeedsStats(): array
    {
        $inventory = new Inventory($this->userId);

        // Count citizens
        $result = $this->db->fetch(
            "SELECT COUNT(*) as total FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );
        $totalCitizens = $result['total'] ?? 0;

        // Food supply
        $breadId = Good::loadByName('Bread')?->getId();
        $wheatId = Good::loadByName('Wheat')?->getId();
        $breadStock = $breadId ? $inventory->getQuantity($breadId) : 0;
        $wheatStock = $wheatId ? $inventory->getQuantity($wheatId) : 0;

        // Calculate days of food supply
        $foodPerDay = $totalCitizens * self::FOOD_PER_CITIZEN;
        $daysOfBread = $foodPerDay > 0 ? (int)floor($breadStock / $foodPerDay) : 0;
        $daysOfWheat = $foodPerDay > 0 ? (int)floor($wheatStock / ($foodPerDay * 2)) : 0;
        $totalFoodDays = $daysOfBread + $daysOfWheat;

        // Housing stats
        $homeless = $this->db->fetch(
            "SELECT COUNT(*) as count FROM citizens WHERE user_id = ? AND is_alive = 1 AND home_building_id IS NULL",
            [$this->userId]
        );
        $homelessCount = $homeless['count'] ?? 0;

        // Unhappy citizens
        $unhappy = $this->db->fetch(
            "SELECT COUNT(*) as count FROM citizens WHERE user_id = ? AND is_alive = 1 AND happiness < 40",
            [$this->userId]
        );
        $unhappyCount = $unhappy['count'] ?? 0;

        // Unhealthy citizens
        $sick = $this->db->fetch(
            "SELECT COUNT(*) as count FROM citizens WHERE user_id = ? AND is_alive = 1 AND health < 40",
            [$this->userId]
        );
        $sickCount = $sick['count'] ?? 0;

        // Season and modifiers
        $season = $this->gameState->getSeason();
        $seasonModifiers = self::SEASONAL_MODIFIERS[$season] ?? self::SEASONAL_MODIFIERS['Spring'];

        return [
            'total_citizens' => $totalCitizens,
            'food_bread' => $breadStock,
            'food_wheat' => $wheatStock,
            'food_days_supply' => $totalFoodDays,
            'food_status' => $totalFoodDays >= 7 ? 'good' : ($totalFoodDays >= 3 ? 'warning' : 'critical'),
            'homeless_count' => $homelessCount,
            'housing_status' => $homelessCount === 0 ? 'good' : ($homelessCount < 3 ? 'warning' : 'critical'),
            'unhappy_count' => $unhappyCount,
            'sick_count' => $sickCount,
            'season' => $season,
            'season_farm_modifier' => ($seasonModifiers['farm'] ?? 1.0) * 100,
            'season_default_modifier' => ($seasonModifiers['default'] ?? 1.0) * 100,
        ];
    }
}
