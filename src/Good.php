<?php

namespace FiefdomForge;

class Good
{
    private ?int $id = null;
    private string $name;
    private ?string $description = null;
    private int $basePrice = 10;
    private bool $isResource = false;
    private array $resourceNeeded = []; // ["good_id" => quantity]

    private Database $db;

    // Predefined goods
    public const GOODS = [
        // Raw resources
        ['name' => 'Wood', 'price' => 5, 'resource' => true],
        ['name' => 'Stone', 'price' => 8, 'resource' => true],
        ['name' => 'Iron Ore', 'price' => 15, 'resource' => true],
        ['name' => 'Wheat', 'price' => 3, 'resource' => true],
        ['name' => 'Wool', 'price' => 6, 'resource' => true],
        ['name' => 'Leather', 'price' => 10, 'resource' => true],
        ['name' => 'Clay', 'price' => 4, 'resource' => true],
        // Manufactured goods
        ['name' => 'Bread', 'price' => 8, 'resource' => false, 'needs' => ['Wheat' => 2]],
        ['name' => 'Iron Ingot', 'price' => 30, 'resource' => false, 'needs' => ['Iron Ore' => 2, 'Wood' => 1]],
        ['name' => 'Tools', 'price' => 50, 'resource' => false, 'needs' => ['Iron Ingot' => 1, 'Wood' => 2]],
        ['name' => 'Cloth', 'price' => 15, 'resource' => false, 'needs' => ['Wool' => 3]],
        ['name' => 'Furniture', 'price' => 40, 'resource' => false, 'needs' => ['Wood' => 4]],
        ['name' => 'Pottery', 'price' => 12, 'resource' => false, 'needs' => ['Clay' => 2]],
        ['name' => 'Ale', 'price' => 10, 'resource' => false, 'needs' => ['Wheat' => 3]],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function create(
        string $name,
        int $basePrice = 10,
        bool $isResource = false,
        array $resourceNeeded = [],
        string $description = ''
    ): Good {
        $good = new Good();
        $good->name = $name;
        $good->basePrice = $basePrice;
        $good->isResource = $isResource;
        $good->resourceNeeded = $resourceNeeded;
        $good->description = $description;

        $good->id = $good->db->insert('goods', [
            'name' => $name,
            'description' => $description,
            'base_price' => $basePrice,
            'is_resource' => $isResource ? 1 : 0,
            'resource_needed' => json_encode($resourceNeeded),
        ]);

        return $good;
    }

    public static function loadById(int $id): ?Good
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM goods WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function loadByName(string $name): ?Good
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM goods WHERE name = ?", [$name]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Good
    {
        $good = new Good();
        $good->id = $data['id'];
        $good->name = $data['name'];
        $good->description = $data['description'];
        $good->basePrice = $data['base_price'];
        $good->isResource = (bool) $data['is_resource'];
        $good->resourceNeeded = json_decode($data['resource_needed'] ?? '{}', true);

        return $good;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM goods ORDER BY name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getResources(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM goods WHERE is_resource = 1 ORDER BY name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getManufactured(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM goods WHERE is_resource = 0 ORDER BY name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function seedGoods(): void
    {
        $db = Database::getInstance();

        // Check if goods already exist
        $existing = $db->fetch("SELECT COUNT(*) as count FROM goods");
        if ($existing['count'] > 0) {
            return;
        }

        // First pass: create all goods without dependencies
        $goodIds = [];
        foreach (self::GOODS as $goodData) {
            $good = self::create(
                $goodData['name'],
                $goodData['price'],
                $goodData['resource'],
                [],
                ''
            );
            $goodIds[$goodData['name']] = $good->getId();
        }

        // Second pass: update manufactured goods with their resource requirements
        foreach (self::GOODS as $goodData) {
            if (!$goodData['resource'] && isset($goodData['needs'])) {
                $good = self::loadByName($goodData['name']);
                if ($good) {
                    $resourceNeeded = [];
                    foreach ($goodData['needs'] as $resourceName => $quantity) {
                        if (isset($goodIds[$resourceName])) {
                            $resourceNeeded[$goodIds[$resourceName]] = $quantity;
                        }
                    }
                    $good->setResourceNeeded($resourceNeeded);
                    $good->save();
                }
            }
        }
    }

    public function save(): void
    {
        if (!$this->id) {
            return;
        }

        $this->db->update('goods', [
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->basePrice,
            'is_resource' => $this->isResource ? 1 : 0,
            'resource_needed' => json_encode($this->resourceNeeded),
        ], 'id = ?', [$this->id]);
    }

    public function getProductionCost(): int
    {
        if ($this->isResource) {
            return 0;
        }

        $cost = 0;
        foreach ($this->resourceNeeded as $goodId => $quantity) {
            $resource = self::loadById((int)$goodId);
            if ($resource) {
                $cost += $resource->getBasePrice() * $quantity;
            }
        }

        return $cost;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getBasePrice(): int { return $this->basePrice; }
    public function isResource(): bool { return $this->isResource; }
    public function getResourceNeeded(): array { return $this->resourceNeeded; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(?string $desc): void { $this->description = $desc; }
    public function setBasePrice(int $price): void { $this->basePrice = max(1, $price); }
    public function setResourceNeeded(array $resources): void { $this->resourceNeeded = $resources; }
}
