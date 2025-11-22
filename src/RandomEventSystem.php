<?php

namespace FiefdomForge;

class RandomEventSystem
{
    private int $userId;
    private GameState $gameState;
    private Database $db;

    // Event categories with their properties
    public const CATEGORY_POSITIVE = 'positive';
    public const CATEGORY_NEGATIVE = 'negative';
    public const CATEGORY_NEUTRAL = 'neutral';
    public const CATEGORY_SPECIAL = 'special';

    // Base chance for any event per day (percentage)
    private const BASE_EVENT_CHANCE = 8;

    // Event definitions with weights, effects, and seasonal requirements
    private array $eventDefinitions = [
        // === POSITIVE EVENTS ===
        'traveling_merchant' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 15,
            'seasons' => ['Spring', 'Summer', 'Autumn'],
            'messages' => [
                'A traveling merchant arrived with exotic goods from distant lands!',
                'A caravan of traders has set up camp, bringing prosperity to your markets.',
                'Foreign merchants have arrived, eager to trade their wares.',
            ],
        ],
        'bountiful_harvest' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 20,
            'seasons' => ['Autumn'],
            'messages' => [
                'The harvest this season was exceptionally bountiful!',
                'Your farmers report record yields from the fields.',
                'The granaries overflow with this year\'s magnificent harvest!',
            ],
        ],
        'skilled_immigrant' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 10,
            'seasons' => null, // Any season
            'messages' => [
                '{name}, a skilled craftsman, seeks refuge in your fiefdom.',
                '{name}, a wandering artisan, wishes to settle here.',
                '{name}, fleeing hardship elsewhere, brings valuable skills to your realm.',
            ],
        ],
        'festival' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 12,
            'seasons' => ['Spring', 'Summer'],
            'messages' => [
                'A spontaneous celebration erupts in the town square!',
                'The citizens organize a joyous festival in honor of the season.',
                'Music and laughter fill the streets as townfolk celebrate!',
            ],
        ],
        'good_weather' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 18,
            'seasons' => ['Spring', 'Summer'],
            'messages' => [
                'Perfect weather blesses your fields and workshops.',
                'Clear skies and gentle breezes boost productivity.',
                'The weather gods smile upon your fiefdom today.',
            ],
        ],
        'treasure_found' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 5,
            'seasons' => null,
            'messages' => [
                'A citizen discovered a cache of old coins while plowing!',
                'Workers unearthed buried treasure during construction!',
                'An ancient chest filled with gold was found in the hills!',
            ],
        ],
        'royal_favor' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 3,
            'seasons' => null,
            'messages' => [
                'The King sends a gift in recognition of your stewardship!',
                'A royal envoy delivers gold as thanks for your loyalty.',
                'Your fiefdom receives a generous royal grant!',
            ],
        ],
        'miraculous_recovery' => [
            'category' => self::CATEGORY_POSITIVE,
            'weight' => 6,
            'seasons' => null,
            'messages' => [
                'A sick citizen makes a miraculous recovery!',
                'The local healer discovers a new remedy, curing the ill.',
                'Divine blessing aids those suffering from ailments.',
            ],
        ],

        // === NEGATIVE EVENTS ===
        'illness_outbreak' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 12,
            'seasons' => ['Winter', 'Autumn'],
            'messages' => [
                'A fever spreads through the crowded quarters.',
                'Illness has taken hold in some households.',
                'The cold season brings sickness to your citizens.',
            ],
        ],
        'harsh_weather' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 15,
            'seasons' => ['Winter'],
            'messages' => [
                'A fierce blizzard damages buildings and chills spirits.',
                'Freezing temperatures bring hardship to the realm.',
                'Heavy snowfall traps citizens indoors.',
            ],
        ],
        'fire' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 8,
            'seasons' => ['Summer', 'Autumn'],
            'messages' => [
                'A fire broke out and damaged some property!',
                'Flames consumed part of a building before being contained.',
                'A carelessly tended hearth started a small fire.',
            ],
        ],
        'theft' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 10,
            'seasons' => null,
            'messages' => [
                'Thieves have stolen from the treasury during the night!',
                'Bandits raided a merchant\'s wagon on the road.',
                'A burglar was spotted, but escaped with some gold.',
            ],
        ],
        'crop_blight' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 10,
            'seasons' => ['Summer', 'Autumn'],
            'messages' => [
                'A blight has struck the crops, reducing yields.',
                'Pests have infested the fields, destroying produce.',
                'Disease spreads through the orchards.',
            ],
        ],
        'building_collapse' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 5,
            'seasons' => null,
            'messages' => [
                'An old structure partially collapsed from disrepair!',
                'Poor construction led to a building accident.',
                'Storm damage has weakened several structures.',
            ],
        ],
        'worker_accident' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 8,
            'seasons' => null,
            'messages' => [
                'A worker was injured in a mining accident.',
                'An accident at the workshop has hurt a craftsman.',
                'A citizen was injured during construction work.',
            ],
        ],
        'tax_collector' => [
            'category' => self::CATEGORY_NEGATIVE,
            'weight' => 6,
            'seasons' => null,
            'messages' => [
                'The Crown demands additional tribute!',
                'Royal tax collectors arrive demanding their due.',
                'An unexpected levy is imposed by the realm.',
            ],
        ],

        // === NEUTRAL EVENTS ===
        'wandering_minstrel' => [
            'category' => self::CATEGORY_NEUTRAL,
            'weight' => 12,
            'seasons' => ['Spring', 'Summer', 'Autumn'],
            'messages' => [
                'A traveling minstrel entertains the townsfolk with tales.',
                'A bard sings songs of distant lands in the tavern.',
                'Musicians pass through, lifting spirits with their melodies.',
            ],
        ],
        'mysterious_stranger' => [
            'category' => self::CATEGORY_NEUTRAL,
            'weight' => 8,
            'seasons' => null,
            'messages' => [
                'A mysterious cloaked figure was seen near the gates.',
                'A strange traveler asks cryptic questions about the area.',
                'An enigmatic visitor appears and disappears without a trace.',
            ],
        ],
        'wildlife_sighting' => [
            'category' => self::CATEGORY_NEUTRAL,
            'weight' => 15,
            'seasons' => ['Spring', 'Summer'],
            'messages' => [
                'A magnificent stag was spotted in the forest.',
                'Flocks of colorful birds pass through the area.',
                'Wolf tracks are found near the outskirts.',
            ],
        ],
        'market_day' => [
            'category' => self::CATEGORY_NEUTRAL,
            'weight' => 20,
            'seasons' => null,
            'messages' => [
                'The weekly market draws crowds from nearby villages.',
                'Farmers and craftsmen gather for a bustling market day.',
                'Trade is brisk at the marketplace today.',
            ],
        ],
        'pilgrim_passage' => [
            'category' => self::CATEGORY_NEUTRAL,
            'weight' => 10,
            'seasons' => ['Spring', 'Autumn'],
            'messages' => [
                'A group of pilgrims passes through on their holy journey.',
                'Religious travelers seek shelter for the night.',
                'Monks on pilgrimage bless your fiefdom as they pass.',
            ],
        ],

        // === SPECIAL/RARE EVENTS ===
        'comet_sighting' => [
            'category' => self::CATEGORY_SPECIAL,
            'weight' => 2,
            'seasons' => null,
            'messages' => [
                'A brilliant comet streaks across the night sky! Citizens debate its meaning.',
            ],
        ],
        'wandering_knight' => [
            'category' => self::CATEGORY_SPECIAL,
            'weight' => 4,
            'seasons' => null,
            'messages' => [
                'A knight errant arrives, offering services in exchange for lodging.',
            ],
        ],
        'ancient_discovery' => [
            'category' => self::CATEGORY_SPECIAL,
            'weight' => 3,
            'seasons' => null,
            'messages' => [
                'Workers discover ancient ruins beneath the earth!',
            ],
        ],
    ];

    public function __construct(int $userId, GameState $gameState)
    {
        $this->userId = $userId;
        $this->gameState = $gameState;
        $this->db = Database::getInstance();
    }

    public function processDay(): array
    {
        $events = [];

        // Check if an event should occur
        if (rand(1, 100) > self::BASE_EVENT_CHANCE) {
            return $events;
        }

        // Select and process an event
        $selectedEvent = $this->selectRandomEvent();
        if ($selectedEvent) {
            $result = $this->processEvent($selectedEvent);
            if ($result) {
                $events[] = $result;
            }
        }

        return $events;
    }

    private function selectRandomEvent(): ?string
    {
        $currentSeason = $this->gameState->getSeason();
        $eligibleEvents = [];
        $totalWeight = 0;

        foreach ($this->eventDefinitions as $eventId => $definition) {
            // Check seasonal requirement
            if ($definition['seasons'] !== null && !in_array($currentSeason, $definition['seasons'])) {
                continue;
            }

            $eligibleEvents[$eventId] = $definition['weight'];
            $totalWeight += $definition['weight'];
        }

        if (empty($eligibleEvents) || $totalWeight === 0) {
            return null;
        }

        // Weighted random selection
        $roll = rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($eligibleEvents as $eventId => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $eventId;
            }
        }

        return null;
    }

    private function processEvent(string $eventId): ?array
    {
        $definition = $this->eventDefinitions[$eventId] ?? null;
        if (!$definition) {
            return null;
        }

        // Select a random message
        $message = $definition['messages'][array_rand($definition['messages'])];

        // Process event effects based on type
        $result = match ($eventId) {
            'traveling_merchant' => $this->processTravelingMerchant($message),
            'bountiful_harvest' => $this->processBountifulHarvest($message),
            'skilled_immigrant' => $this->processSkilledImmigrant($message),
            'festival' => $this->processFestival($message),
            'good_weather' => $this->processGoodWeather($message),
            'treasure_found' => $this->processTreasureFound($message),
            'royal_favor' => $this->processRoyalFavor($message),
            'miraculous_recovery' => $this->processMiraculousRecovery($message),
            'illness_outbreak' => $this->processIllnessOutbreak($message),
            'harsh_weather' => $this->processHarshWeather($message),
            'fire' => $this->processFire($message),
            'theft' => $this->processTheft($message),
            'crop_blight' => $this->processCropBlight($message),
            'building_collapse' => $this->processBuildingCollapse($message),
            'worker_accident' => $this->processWorkerAccident($message),
            'tax_collector' => $this->processTaxCollector($message),
            'wandering_minstrel' => $this->processWanderingMinstrel($message),
            'mysterious_stranger' => $this->processMysteriousStranger($message),
            'wildlife_sighting' => $this->processWildlifeSighting($message),
            'market_day' => $this->processMarketDay($message),
            'pilgrim_passage' => $this->processPilgrimPassage($message),
            'comet_sighting' => $this->processCometSighting($message),
            'wandering_knight' => $this->processWanderingKnight($message),
            'ancient_discovery' => $this->processAncientDiscovery($message),
            default => null,
        };

        if ($result) {
            $result['category'] = $definition['category'];
            $result['event_id'] = $eventId;
        }

        return $result;
    }

    // === POSITIVE EVENT HANDLERS ===

    private function processTravelingMerchant(string $message): array
    {
        $goldGain = rand(30, 80);
        $this->gameState->addTreasury($goldGain);
        $this->gameState->save();

        return [
            'type' => 'traveling_merchant',
            'message' => $message . " (+{$goldGain} gold)",
        ];
    }

    private function processBountifulHarvest(string $message): array
    {
        $goldGain = rand(50, 150);
        $this->gameState->addTreasury($goldGain);
        $this->boostAllCitizenHappiness(3);
        $this->gameState->save();

        return [
            'type' => 'bountiful_harvest',
            'message' => $message . " (+{$goldGain} gold, +3 happiness)",
        ];
    }

    private function processSkilledImmigrant(string $message): array
    {
        $gender = rand(0, 1) === 0 ? 'male' : 'female';
        $name = $this->generateName($gender);
        $citizen = Citizen::create($this->userId, $name, rand(22, 45), $gender);

        $finalMessage = str_replace('{name}', $name, $message);

        return [
            'type' => 'skilled_immigrant',
            'message' => $finalMessage,
            'citizen_id' => $citizen->getId(),
        ];
    }

    private function processFestival(string $message): array
    {
        $cost = rand(10, 30);
        $this->gameState->spendTreasury($cost);
        $this->boostAllCitizenHappiness(8);
        $this->gameState->save();

        return [
            'type' => 'festival',
            'message' => $message . " (+8 happiness, -{$cost} gold)",
        ];
    }

    private function processGoodWeather(string $message): array
    {
        $goldGain = rand(10, 30);
        $this->gameState->addTreasury($goldGain);
        $this->gameState->save();

        return [
            'type' => 'good_weather',
            'message' => $message . " (+{$goldGain} gold)",
        ];
    }

    private function processTreasureFound(string $message): array
    {
        $goldGain = rand(100, 300);
        $this->gameState->addTreasury($goldGain);
        $this->boostAllCitizenHappiness(5);
        $this->gameState->save();

        return [
            'type' => 'treasure_found',
            'message' => $message . " (+{$goldGain} gold!)",
        ];
    }

    private function processRoyalFavor(string $message): array
    {
        $goldGain = rand(200, 500);
        $this->gameState->addTreasury($goldGain);
        $this->boostAllCitizenHappiness(10);
        $this->gameState->save();

        return [
            'type' => 'royal_favor',
            'message' => $message . " (+{$goldGain} gold, +10 happiness)",
        ];
    }

    private function processMiraculousRecovery(string $message): array
    {
        // Heal all sick citizens
        $this->boostAllCitizenHealth(15);

        return [
            'type' => 'miraculous_recovery',
            'message' => $message . " (+15 health to all)",
        ];
    }

    // === NEGATIVE EVENT HANDLERS ===

    private function processIllnessOutbreak(string $message): array
    {
        $healthLoss = rand(5, 15);
        $this->damageRandomCitizensHealth($healthLoss, 3);

        return [
            'type' => 'illness_outbreak',
            'message' => $message . " (-{$healthLoss} health to some citizens)",
        ];
    }

    private function processHarshWeather(string $message): array
    {
        $goldLoss = rand(20, 50);
        $happinessLoss = 5;
        $this->gameState->spendTreasury($goldLoss);
        $this->damageAllCitizenHappiness($happinessLoss);
        $this->damageRandomBuildingCondition(10, 2);
        $this->gameState->save();

        return [
            'type' => 'harsh_weather',
            'message' => $message . " (-{$goldLoss} gold, -{$happinessLoss} happiness)",
        ];
    }

    private function processFire(string $message): array
    {
        $goldLoss = rand(30, 80);
        $this->gameState->spendTreasury($goldLoss);
        $this->damageRandomBuildingCondition(20, 1);
        $this->gameState->save();

        return [
            'type' => 'fire',
            'message' => $message . " (-{$goldLoss} gold, building damage)",
        ];
    }

    private function processTheft(string $message): array
    {
        $goldLoss = rand(25, 75);
        $this->gameState->spendTreasury($goldLoss);
        $this->damageAllCitizenHappiness(3);
        $this->gameState->save();

        return [
            'type' => 'theft',
            'message' => $message . " (-{$goldLoss} gold)",
        ];
    }

    private function processCropBlight(string $message): array
    {
        $goldLoss = rand(40, 100);
        $this->gameState->spendTreasury($goldLoss);
        $this->gameState->save();

        return [
            'type' => 'crop_blight',
            'message' => $message . " (-{$goldLoss} gold in lost produce)",
        ];
    }

    private function processBuildingCollapse(string $message): array
    {
        $this->damageRandomBuildingCondition(30, 1);
        $goldLoss = rand(20, 60);
        $this->gameState->spendTreasury($goldLoss);
        $this->gameState->save();

        return [
            'type' => 'building_collapse',
            'message' => $message . " (Building damage, -{$goldLoss} gold repairs)",
        ];
    }

    private function processWorkerAccident(string $message): array
    {
        $this->damageRandomCitizensHealth(20, 1);
        $this->damageAllCitizenHappiness(2);

        return [
            'type' => 'worker_accident',
            'message' => $message . " (Citizen injured)",
        ];
    }

    private function processTaxCollector(string $message): array
    {
        $treasury = $this->gameState->getTreasury();
        $taxAmount = (int)($treasury * 0.1); // 10% tax
        $taxAmount = max(20, min(200, $taxAmount)); // Between 20 and 200
        $this->gameState->spendTreasury($taxAmount);
        $this->damageAllCitizenHappiness(5);
        $this->gameState->save();

        return [
            'type' => 'tax_collector',
            'message' => $message . " (-{$taxAmount} gold, -5 happiness)",
        ];
    }

    // === NEUTRAL EVENT HANDLERS ===

    private function processWanderingMinstrel(string $message): array
    {
        $this->boostAllCitizenHappiness(3);

        return [
            'type' => 'wandering_minstrel',
            'message' => $message . " (+3 happiness)",
        ];
    }

    private function processMysteriousStranger(string $message): array
    {
        return [
            'type' => 'mysterious_stranger',
            'message' => $message,
        ];
    }

    private function processWildlifeSighting(string $message): array
    {
        return [
            'type' => 'wildlife_sighting',
            'message' => $message,
        ];
    }

    private function processMarketDay(string $message): array
    {
        $goldGain = rand(5, 20);
        $this->gameState->addTreasury($goldGain);
        $this->gameState->save();

        return [
            'type' => 'market_day',
            'message' => $message . " (+{$goldGain} gold)",
        ];
    }

    private function processPilgrimPassage(string $message): array
    {
        $this->boostAllCitizenHappiness(2);

        return [
            'type' => 'pilgrim_passage',
            'message' => $message . " (+2 happiness)",
        ];
    }

    // === SPECIAL EVENT HANDLERS ===

    private function processCometSighting(string $message): array
    {
        // Random effect - could be good or bad omen
        if (rand(0, 1) === 0) {
            $this->boostAllCitizenHappiness(5);
            return [
                'type' => 'comet_sighting',
                'message' => $message . ' Citizens see it as a good omen! (+5 happiness)',
            ];
        } else {
            $this->damageAllCitizenHappiness(5);
            return [
                'type' => 'comet_sighting',
                'message' => $message . ' Some fear it portends doom. (-5 happiness)',
            ];
        }
    }

    private function processWanderingKnight(string $message): array
    {
        $goldCost = rand(20, 40);
        $this->gameState->spendTreasury($goldCost);
        $this->boostAllCitizenHappiness(5);
        $this->gameState->save();

        return [
            'type' => 'wandering_knight',
            'message' => $message . " (-{$goldCost} gold for lodging, +5 happiness, citizens feel safer)",
        ];
    }

    private function processAncientDiscovery(string $message): array
    {
        $goldGain = rand(50, 150);
        $this->gameState->addTreasury($goldGain);
        $this->boostAllCitizenHappiness(10);
        $this->gameState->save();

        return [
            'type' => 'ancient_discovery',
            'message' => $message . " (+{$goldGain} gold in artifacts, +10 happiness)",
        ];
    }

    // === HELPER METHODS ===

    private function boostAllCitizenHappiness(int $amount): void
    {
        $citizens = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );

        foreach ($citizens as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);
            $citizen->modifyHappiness($amount);
            $citizen->save();
        }
    }

    private function damageAllCitizenHappiness(int $amount): void
    {
        $this->boostAllCitizenHappiness(-$amount);
    }

    private function boostAllCitizenHealth(int $amount): void
    {
        $citizens = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1",
            [$this->userId]
        );

        foreach ($citizens as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);
            $citizen->modifyHealth($amount);
            $citizen->save();
        }
    }

    private function damageRandomCitizensHealth(int $amount, int $count): void
    {
        $citizens = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE user_id = ? AND is_alive = 1 ORDER BY RANDOM() LIMIT ?",
            [$this->userId, $count]
        );

        foreach ($citizens as $citizenData) {
            $citizen = Citizen::fromArray($citizenData);
            $citizen->modifyHealth(-$amount);
            $citizen->save();
        }
    }

    private function damageRandomBuildingCondition(int $amount, int $count): void
    {
        $buildings = $this->db->fetchAll(
            "SELECT * FROM buildings ORDER BY RANDOM() LIMIT ?",
            [$count]
        );

        foreach ($buildings as $buildingData) {
            $building = Building::fromArray($buildingData);
            $newCondition = max(1, $building->getCondition() - $amount);
            $this->db->update('buildings', $building->getId(), [
                'condition_percent' => $newCondition,
            ]);
        }
    }

    private function generateName(string $gender): string
    {
        $maleNames = [
            'William', 'Thomas', 'John', 'Richard', 'Robert', 'Henry', 'Edward',
            'Geoffrey', 'Walter', 'Simon', 'Hugh', 'Roger', 'Ralph', 'Gilbert',
            'Peter', 'Adam', 'Nicholas', 'Stephen', 'Alan', 'Edmund', 'Miles',
            'Oswald', 'Benedict', 'Leonard', 'Martin', 'Philip', 'Godfrey',
        ];

        $femaleNames = [
            'Alice', 'Matilda', 'Joan', 'Agnes', 'Margaret', 'Emma', 'Isabella',
            'Eleanor', 'Beatrice', 'Cecily', 'Edith', 'Mabel', 'Rose', 'Avice',
            'Juliana', 'Sybil', 'Constance', 'Lucy', 'Margery', 'Christine',
            'Amelia', 'Godiva', 'Heloise', 'Millicent', 'Rohesia', 'Wynifred',
        ];

        $surnames = [
            'Smith', 'Miller', 'Baker', 'Taylor', 'Cooper', 'Wright', 'Fletcher',
            'Carter', 'Fisher', 'Shepherd', 'Thatcher', 'Mason', 'Carpenter',
            'Hunter', 'Weaver', 'Tanner', 'Potter', 'Brewer', 'Cook', 'Chandler',
            'Mercer', 'Forester', 'Gardner', 'Palmer', 'Sawyer', 'Wheeler',
        ];

        $firstName = $gender === 'male'
            ? $maleNames[array_rand($maleNames)]
            : $femaleNames[array_rand($femaleNames)];

        $lastName = $surnames[array_rand($surnames)];

        return "{$firstName} {$lastName}";
    }

    public static function getEventCategories(): array
    {
        return [
            self::CATEGORY_POSITIVE => 'Positive',
            self::CATEGORY_NEGATIVE => 'Negative',
            self::CATEGORY_NEUTRAL => 'Neutral',
            self::CATEGORY_SPECIAL => 'Special',
        ];
    }

    /**
     * Get all available event types for admin triggering
     */
    public function getAvailableEvents(): array
    {
        $events = [];
        foreach ($this->eventDefinitions as $eventId => $definition) {
            $events[$eventId] = [
                'id' => $eventId,
                'name' => ucwords(str_replace('_', ' ', $eventId)),
                'category' => $definition['category'],
                'seasons' => $definition['seasons'],
            ];
        }
        return $events;
    }

    /**
     * Manually trigger a specific event (admin function)
     */
    public function triggerEvent(string $eventId): ?array
    {
        if (!isset($this->eventDefinitions[$eventId])) {
            return null;
        }

        return $this->processEvent($eventId);
    }
}
