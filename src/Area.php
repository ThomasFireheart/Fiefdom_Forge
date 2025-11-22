<?php

namespace FiefdomForge;

class Area
{
    private ?int $id = null;
    private string $name;
    private ?string $description = null;
    private float $taxRate = 0.05;
    private int $prestige = 0;
    private int $capacity = 0;

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function create(string $name, string $description = '', int $capacity = 100): Area
    {
        $area = new Area();
        $area->name = $name;
        $area->description = $description;
        $area->capacity = $capacity;

        $area->id = $area->db->insert('areas', [
            'name' => $name,
            'description' => $description,
            'tax_rate' => $area->taxRate,
            'prestige' => $area->prestige,
            'capacity' => $capacity,
        ]);

        return $area;
    }

    public static function loadById(int $id): ?Area
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM areas WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Area
    {
        $area = new Area();
        $area->id = $data['id'];
        $area->name = $data['name'];
        $area->description = $data['description'];
        $area->taxRate = (float) $data['tax_rate'];
        $area->prestige = $data['prestige'];
        $area->capacity = $data['capacity'];

        return $area;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM areas ORDER BY name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public function save(): void
    {
        if (!$this->id) {
            return;
        }

        $this->db->update('areas', [
            'name' => $this->name,
            'description' => $this->description,
            'tax_rate' => $this->taxRate,
            'prestige' => $this->prestige,
            'capacity' => $this->capacity,
        ], 'id = ?', [$this->id]);
    }

    public function getCurrentPopulation(): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM citizens c
             JOIN buildings b ON c.home_building_id = b.id
             WHERE b.area_id = ? AND c.is_alive = 1",
            [$this->id]
        );
        return $result['count'] ?? 0;
    }

    public function getBuildings(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM buildings WHERE area_id = ?",
            [$this->id]
        );
        return array_map(fn($row) => Building::fromArray($row), $rows);
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getTaxRate(): float { return $this->taxRate; }
    public function getPrestige(): int { return $this->prestige; }
    public function getCapacity(): int { return $this->capacity; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function setTaxRate(float $rate): void { $this->taxRate = max(0, min(1, $rate)); }
    public function setPrestige(int $prestige): void { $this->prestige = $prestige; }
    public function setCapacity(int $capacity): void { $this->capacity = max(0, $capacity); }
}
