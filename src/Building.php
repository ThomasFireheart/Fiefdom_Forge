<?php

namespace FiefdomForge;

class Building
{
    private ?int $id = null;
    private string $name;
    private string $type; // house, business, public, farm, resource
    private int $areaId;
    private ?int $ownerCitizenId = null;
    private int $capacity = 1;
    private int $condition = 100;
    private int $constructionCost = 0;
    private int $upkeepCost = 0;
    private ?int $xCoord = null;
    private ?int $yCoord = null;

    private Database $db;

    public const TYPES = ['house', 'business', 'public', 'farm', 'resource'];

    // Building templates with unlock requirements and historical descriptions
    // 'unlock' key specifies which achievement is needed (null = always available)
    public const TEMPLATES = [
        'cottage' => [
            'type' => 'house', 'capacity' => 4, 'cost' => 100, 'upkeep' => 5, 'unlock' => null,
            'description' => 'A small dwelling for peasant families. Medieval cottages were typically single-room structures with thatched roofs, housing entire families alongside their livestock.'
        ],
        'manor' => [
            'type' => 'house', 'capacity' => 8, 'cost' => 500, 'upkeep' => 20, 'unlock' => 'village',
            'description' => 'A noble residence and administrative center. The manor house was the heart of the feudal estate, where the lord collected rents and administered justice.'
        ],
        'workshop' => [
            'type' => 'business', 'capacity' => 3, 'cost' => 200, 'upkeep' => 10, 'unlock' => null,
            'description' => 'A craftsman\'s workplace. Medieval workshops were where skilled artisans practiced trades passed down through apprenticeships, often living above their shops.'
        ],
        'shop' => [
            'type' => 'business', 'capacity' => 2, 'cost' => 150, 'upkeep' => 8, 'unlock' => 'entrepreneur',
            'description' => 'A merchant\'s storefront. Shops in medieval towns often had open fronts with shutters that folded down to create counters for displaying goods.'
        ],
        'farm' => [
            'type' => 'farm', 'capacity' => 5, 'cost' => 250, 'upkeep' => 15, 'unlock' => null,
            'description' => 'Agricultural land for growing crops. The three-field system was common in medieval Europe, rotating crops to maintain soil fertility.'
        ],
        'mine' => [
            'type' => 'resource', 'capacity' => 10, 'cost' => 400, 'upkeep' => 25, 'unlock' => 'builder',
            'description' => 'An extraction site for ore and stone. Medieval mining was dangerous work, with miners using pickaxes and hand tools in cramped tunnels lit by candles.'
        ],
        'lumber_mill' => [
            'type' => 'resource', 'capacity' => 6, 'cost' => 300, 'upkeep' => 15, 'unlock' => 'first_building',
            'description' => 'A facility for processing timber. Wood was essential for construction, fuel, and tools. Water-powered sawmills appeared in the 11th century.'
        ],
        'church' => [
            'type' => 'public', 'capacity' => 50, 'cost' => 1000, 'upkeep' => 30, 'unlock' => 'town',
            'description' => 'A place of worship and community gathering. The medieval church was central to daily life, marking time with bells and providing education and charity.'
        ],
        'tavern' => [
            'type' => 'public', 'capacity' => 20, 'cost' => 400, 'upkeep' => 15, 'unlock' => 'village',
            'description' => 'An establishment serving ale and food. Taverns served as social hubs where travelers shared news and locals conducted business over drinks.'
        ],
        'market' => [
            'type' => 'public', 'capacity' => 30, 'cost' => 600, 'upkeep' => 20, 'unlock' => 'entrepreneur',
            'description' => 'A trading center for goods. Market rights were valuable privileges granted by lords, with weekly markets forming the backbone of medieval commerce.'
        ],
        'guild_hall' => [
            'type' => 'public', 'capacity' => 15, 'cost' => 800, 'upkeep' => 25, 'unlock' => 'merchant_lord',
            'description' => 'Headquarters for trade guilds. Guilds regulated quality, prices, and training, wielding significant political power in medieval towns.'
        ],
        'castle' => [
            'type' => 'public', 'capacity' => 100, 'cost' => 5000, 'upkeep' => 100, 'unlock' => 'city',
            'description' => 'A fortified residence and military stronghold. Castles evolved from wooden motte-and-bailey structures to imposing stone fortresses over centuries.'
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function create(
        string $name,
        string $type,
        int $areaId,
        int $capacity = 1,
        int $constructionCost = 0,
        int $upkeepCost = 0
    ): Building {
        $building = new Building();
        $building->name = $name;
        $building->type = $type;
        $building->areaId = $areaId;
        $building->capacity = $capacity;
        $building->constructionCost = $constructionCost;
        $building->upkeepCost = $upkeepCost;

        // Auto-assign coordinates based on existing buildings in area
        $coords = self::getNextAvailableCoords($areaId);
        $building->xCoord = $coords['x'];
        $building->yCoord = $coords['y'];

        $building->id = $building->db->insert('buildings', [
            'name' => $name,
            'type' => $type,
            'area_id' => $areaId,
            'capacity' => $capacity,
            'condition' => 100,
            'construction_cost' => $constructionCost,
            'upkeep_cost' => $upkeepCost,
            'x_coord' => $building->xCoord,
            'y_coord' => $building->yCoord,
        ]);

        return $building;
    }

    public static function createFromTemplate(string $template, string $name, int $areaId): ?Building
    {
        if (!isset(self::TEMPLATES[$template])) {
            return null;
        }

        $config = self::TEMPLATES[$template];
        return self::create(
            $name,
            $config['type'],
            $areaId,
            $config['capacity'],
            $config['cost'],
            $config['upkeep']
        );
    }

    public static function loadById(int $id): ?Building
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM buildings WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Building
    {
        $building = new Building();
        $building->id = $data['id'];
        $building->name = $data['name'];
        $building->type = $data['type'];
        $building->areaId = $data['area_id'];
        $building->ownerCitizenId = $data['owner_citizen_id'];
        $building->capacity = $data['capacity'];
        $building->condition = $data['condition'];
        $building->constructionCost = $data['construction_cost'];
        $building->upkeepCost = $data['upkeep_cost'];
        $building->xCoord = $data['x_coord'];
        $building->yCoord = $data['y_coord'];

        return $building;
    }

    public static function getByArea(int $areaId): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM buildings WHERE area_id = ?", [$areaId]);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getByType(string $type): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM buildings WHERE type = ?", [$type]);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function countAll(): int
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT COUNT(*) as count FROM buildings");
        return $result['count'] ?? 0;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM buildings ORDER BY area_id, name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getNextAvailableCoords(int $areaId): array
    {
        $db = Database::getInstance();

        // Get all occupied coordinates in this area
        $occupied = $db->fetchAll(
            "SELECT x_coord, y_coord FROM buildings WHERE area_id = ? AND x_coord IS NOT NULL",
            [$areaId]
        );

        $occupiedSet = [];
        foreach ($occupied as $coord) {
            $key = $coord['x_coord'] . ',' . $coord['y_coord'];
            $occupiedSet[$key] = true;
        }

        // Find next available spot in a grid pattern (10 columns max)
        $gridWidth = 10;
        for ($y = 0; $y < 100; $y++) {
            for ($x = 0; $x < $gridWidth; $x++) {
                $key = $x . ',' . $y;
                if (!isset($occupiedSet[$key])) {
                    return ['x' => $x, 'y' => $y];
                }
            }
        }

        // Fallback
        return ['x' => 0, 'y' => count($occupied)];
    }

    /**
     * Check if a building template is unlocked for a user
     */
    public static function isTemplateUnlocked(string $templateKey, int $userId): bool
    {
        if (!isset(self::TEMPLATES[$templateKey])) {
            return false;
        }

        $template = self::TEMPLATES[$templateKey];
        $requiredAchievement = $template['unlock'] ?? null;

        // No unlock requirement
        if ($requiredAchievement === null) {
            return true;
        }

        // Check if user has the required achievement
        $achievement = new Achievement($userId);
        $unlockedAchievements = $achievement->getUnlockedAchievements();

        return in_array($requiredAchievement, $unlockedAchievements);
    }

    /**
     * Get all templates with unlock status for a user
     */
    public static function getTemplatesWithUnlockStatus(int $userId): array
    {
        $result = [];
        $achievement = new Achievement($userId);
        $unlockedAchievements = $achievement->getUnlockedAchievements();

        foreach (self::TEMPLATES as $key => $template) {
            $requiredAchievement = $template['unlock'] ?? null;
            $isUnlocked = $requiredAchievement === null || in_array($requiredAchievement, $unlockedAchievements);

            $result[$key] = array_merge($template, [
                'key' => $key,
                'name' => ucwords(str_replace('_', ' ', $key)),
                'unlocked' => $isUnlocked,
                'required_achievement' => $requiredAchievement,
                'required_achievement_name' => $requiredAchievement
                    ? (Achievement::ACHIEVEMENTS[$requiredAchievement]['name'] ?? ucwords(str_replace('_', ' ', $requiredAchievement)))
                    : null,
            ]);
        }

        return $result;
    }

    public static function getMapData(): array
    {
        $db = Database::getInstance();

        $buildings = $db->fetchAll(
            "SELECT b.*, a.name as area_name,
                    (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) as residents,
                    (SELECT bus.name FROM businesses bus WHERE bus.building_id = b.id LIMIT 1) as business_name
             FROM buildings b
             LEFT JOIN areas a ON b.area_id = a.id
             ORDER BY b.area_id, b.y_coord, b.x_coord"
        );

        return $buildings;
    }

    public function save(): void
    {
        if (!$this->id) {
            return;
        }

        $this->db->update('buildings', [
            'name' => $this->name,
            'type' => $this->type,
            'area_id' => $this->areaId,
            'owner_citizen_id' => $this->ownerCitizenId,
            'capacity' => $this->capacity,
            'condition' => $this->condition,
            'construction_cost' => $this->constructionCost,
            'upkeep_cost' => $this->upkeepCost,
            'x_coord' => $this->xCoord,
            'y_coord' => $this->yCoord,
        ], 'id = ?', [$this->id]);
    }

    public function getResidents(): array
    {
        if ($this->type !== 'house') {
            return [];
        }

        $rows = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE home_building_id = ? AND is_alive = 1",
            [$this->id]
        );
        return array_map(fn($row) => Citizen::fromArray($row), $rows);
    }

    public function getCurrentOccupancy(): int
    {
        if ($this->type === 'house') {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count FROM citizens WHERE home_building_id = ? AND is_alive = 1",
                [$this->id]
            );
        } else {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count FROM citizens WHERE work_business_id IN
                 (SELECT id FROM businesses WHERE building_id = ?) AND is_alive = 1",
                [$this->id]
            );
        }
        return $result['count'] ?? 0;
    }

    public function hasSpace(): bool
    {
        return $this->getCurrentOccupancy() < $this->capacity;
    }

    public function degrade(int $amount = 1): void
    {
        $this->condition = max(0, $this->condition - $amount);
    }

    public function repair(int $amount = 10): void
    {
        $this->condition = min(100, $this->condition + $amount);
    }

    public function isOperational(): bool
    {
        return $this->condition > 20;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getAreaId(): int { return $this->areaId; }
    public function getOwnerCitizenId(): ?int { return $this->ownerCitizenId; }
    public function getCapacity(): int { return $this->capacity; }
    public function getCondition(): int { return $this->condition; }
    public function getConstructionCost(): int { return $this->constructionCost; }
    public function getUpkeepCost(): int { return $this->upkeepCost; }
    public function getXCoord(): ?int { return $this->xCoord; }
    public function getYCoord(): ?int { return $this->yCoord; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setOwnerCitizenId(?int $id): void { $this->ownerCitizenId = $id; }
    public function setCoords(int $x, int $y): void { $this->xCoord = $x; $this->yCoord = $y; }
}
