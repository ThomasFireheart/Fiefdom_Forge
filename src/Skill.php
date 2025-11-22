<?php

namespace FiefdomForge;

class Skill
{
    private ?int $id = null;
    private string $name;
    private string $description;
    private string $type;

    private Database $db;

    // Skill types
    public const TYPES = ['crafting', 'gathering', 'combat', 'social'];

    // Default skills to seed
    public const SKILLS = [
        // Crafting skills
        ['name' => 'Smithing', 'description' => 'The art of forging metal into weapons, tools, and armor.', 'type' => 'crafting'],
        ['name' => 'Carpentry', 'description' => 'Woodworking and construction of furniture and buildings.', 'type' => 'crafting'],
        ['name' => 'Weaving', 'description' => 'Creating cloth and textiles from raw materials.', 'type' => 'crafting'],
        ['name' => 'Brewing', 'description' => 'The production of ale, mead, and other beverages.', 'type' => 'crafting'],
        ['name' => 'Baking', 'description' => 'Creating bread and other baked goods.', 'type' => 'crafting'],

        // Gathering skills
        ['name' => 'Farming', 'description' => 'Cultivating crops and managing agricultural land.', 'type' => 'gathering'],
        ['name' => 'Mining', 'description' => 'Extracting ore, stone, and minerals from the earth.', 'type' => 'gathering'],
        ['name' => 'Logging', 'description' => 'Felling trees and processing timber.', 'type' => 'gathering'],
        ['name' => 'Fishing', 'description' => 'Catching fish and other aquatic creatures.', 'type' => 'gathering'],
        ['name' => 'Hunting', 'description' => 'Tracking and hunting wild game.', 'type' => 'gathering'],

        // Combat skills
        ['name' => 'Swordsmanship', 'description' => 'Proficiency with bladed weapons.', 'type' => 'combat'],
        ['name' => 'Archery', 'description' => 'Skill with bows and ranged weapons.', 'type' => 'combat'],
        ['name' => 'Defense', 'description' => 'The ability to protect oneself and others.', 'type' => 'combat'],

        // Social skills
        ['name' => 'Trading', 'description' => 'The art of negotiation and commerce.', 'type' => 'social'],
        ['name' => 'Leadership', 'description' => 'The ability to inspire and direct others.', 'type' => 'social'],
        ['name' => 'Medicine', 'description' => 'Healing arts and herbal remedies.', 'type' => 'social'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function create(string $name, string $description, string $type): Skill
    {
        $skill = new Skill();
        $skill->name = $name;
        $skill->description = $description;
        $skill->type = $type;

        $skill->id = $skill->db->insert('skills', [
            'name' => $name,
            'description' => $description,
            'type' => $type,
        ]);

        return $skill;
    }

    public static function loadById(int $id): ?Skill
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM skills WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function loadByName(string $name): ?Skill
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM skills WHERE name = ?", [$name]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Skill
    {
        $skill = new Skill();
        $skill->id = $data['id'];
        $skill->name = $data['name'];
        $skill->description = $data['description'] ?? '';
        $skill->type = $data['type'] ?? 'crafting';

        return $skill;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM skills ORDER BY type, name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function getByType(string $type): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM skills WHERE type = ? ORDER BY name", [$type]);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function seedSkills(): void
    {
        $db = Database::getInstance();

        // Check if skills already exist
        $existing = $db->fetch("SELECT COUNT(*) as count FROM skills");
        if ($existing['count'] > 0) {
            return;
        }

        // Create all skills
        foreach (self::SKILLS as $skillData) {
            self::create(
                $skillData['name'],
                $skillData['description'],
                $skillData['type']
            );
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getType(): string { return $this->type; }
}
