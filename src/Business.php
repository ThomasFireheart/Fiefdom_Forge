<?php

namespace FiefdomForge;

class Business
{
    private ?int $id = null;
    private string $name;
    private int $buildingId;
    private ?int $ownerCitizenId = null;
    private string $type;
    private array $products = []; // array of good_ids
    private int $employeesCapacity = 1;
    private int $currentEmployees = 0;
    private int $treasury = 0;
    private int $reputation = 50;

    private Database $db;

    // Business types and their default products
    public const TYPES = [
        'farm' => ['produces' => ['Wheat'], 'capacity' => 5],
        'ranch' => ['produces' => ['Wool', 'Leather'], 'capacity' => 4],
        'lumber_mill' => ['produces' => ['Wood'], 'capacity' => 6],
        'mine' => ['produces' => ['Iron Ore', 'Stone'], 'capacity' => 8],
        'quarry' => ['produces' => ['Stone', 'Clay'], 'capacity' => 6],
        'bakery' => ['produces' => ['Bread'], 'capacity' => 3],
        'blacksmith' => ['produces' => ['Iron Ingot', 'Tools'], 'capacity' => 4],
        'tailor' => ['produces' => ['Cloth'], 'capacity' => 3],
        'carpenter' => ['produces' => ['Furniture'], 'capacity' => 4],
        'potter' => ['produces' => ['Pottery'], 'capacity' => 2],
        'brewery' => ['produces' => ['Ale'], 'capacity' => 4],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function create(string $name, int $buildingId, string $type): ?Business
    {
        if (!isset(self::TYPES[$type])) {
            return null;
        }

        $business = new Business();
        $business->name = $name;
        $business->buildingId = $buildingId;
        $business->type = $type;
        $business->employeesCapacity = self::TYPES[$type]['capacity'];

        // Get product IDs from names
        $products = [];
        foreach (self::TYPES[$type]['produces'] as $goodName) {
            $good = Good::loadByName($goodName);
            if ($good) {
                $products[] = $good->getId();
            }
        }
        $business->products = $products;

        $business->id = $business->db->insert('businesses', [
            'name' => $name,
            'building_id' => $buildingId,
            'type' => $type,
            'products' => json_encode($products),
            'employees_capacity' => $business->employeesCapacity,
            'current_employees' => 0,
            'treasury' => 0,
            'reputation' => 50,
        ]);

        return $business;
    }

    public static function loadById(int $id): ?Business
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM businesses WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Business
    {
        $business = new Business();
        $business->id = $data['id'];
        $business->name = $data['name'];
        $business->buildingId = $data['building_id'];
        $business->ownerCitizenId = $data['owner_citizen_id'];
        $business->type = $data['type'];
        $business->products = json_decode($data['products'] ?? '[]', true);
        $business->employeesCapacity = $data['employees_capacity'];
        $business->currentEmployees = $data['current_employees'];
        $business->treasury = $data['treasury'];
        $business->reputation = $data['reputation'];

        return $business;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM businesses ORDER BY name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getByType(string $type): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM businesses WHERE type = ?", [$type]);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public function save(): void
    {
        if (!$this->id) {
            return;
        }

        $this->db->update('businesses', [
            'name' => $this->name,
            'owner_citizen_id' => $this->ownerCitizenId,
            'type' => $this->type,
            'products' => json_encode($this->products),
            'employees_capacity' => $this->employeesCapacity,
            'current_employees' => $this->currentEmployees,
            'treasury' => $this->treasury,
            'reputation' => $this->reputation,
        ], 'id = ?', [$this->id]);
    }

    public function getEmployees(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM citizens WHERE work_business_id = ? AND is_alive = 1",
            [$this->id]
        );
        return array_map(fn($row) => Citizen::fromArray($row), $rows);
    }

    public function updateEmployeeCount(): void
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM citizens WHERE work_business_id = ? AND is_alive = 1",
            [$this->id]
        );
        $this->currentEmployees = $result['count'] ?? 0;
    }

    public function canHire(): bool
    {
        $this->updateEmployeeCount();
        return $this->currentEmployees < $this->employeesCapacity;
    }

    public function hire(Citizen $citizen): bool
    {
        if (!$this->canHire() || !$citizen->canWork()) {
            return false;
        }

        $citizen->setWorkBusinessId($this->id);
        $citizen->save();
        $this->currentEmployees++;
        $this->save();

        return true;
    }

    public function getProductionCapacity(): float
    {
        // Production based on employees and reputation
        $employeeRatio = $this->currentEmployees / max(1, $this->employeesCapacity);
        $reputationBonus = $this->reputation / 100;

        return $employeeRatio * (0.5 + ($reputationBonus * 0.5));
    }

    public function produce(): array
    {
        $produced = [];
        $capacity = $this->getProductionCapacity();

        foreach ($this->products as $goodId) {
            $good = Good::loadById($goodId);
            if (!$good) {
                continue;
            }

            // Calculate quantity produced
            $baseQuantity = $good->isResource() ? 5 : 2;
            $quantity = (int) floor($baseQuantity * $capacity);

            if ($quantity > 0) {
                $produced[] = [
                    'good_id' => $goodId,
                    'good_name' => $good->getName(),
                    'quantity' => $quantity,
                    'value' => $quantity * $good->getBasePrice(),
                ];
            }
        }

        return $produced;
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

    public function modifyReputation(int $amount): void
    {
        $this->reputation = max(0, min(100, $this->reputation + $amount));
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getBuildingId(): int { return $this->buildingId; }
    public function getOwnerCitizenId(): ?int { return $this->ownerCitizenId; }
    public function getType(): string { return $this->type; }
    public function getProducts(): array { return $this->products; }
    public function getEmployeesCapacity(): int { return $this->employeesCapacity; }
    public function getCurrentEmployees(): int { return $this->currentEmployees; }
    public function getTreasury(): int { return $this->treasury; }
    public function getReputation(): int { return $this->reputation; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setOwnerCitizenId(?int $id): void { $this->ownerCitizenId = $id; }
}
