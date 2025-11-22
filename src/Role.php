<?php

namespace FiefdomForge;

class Role
{
    private ?int $id = null;
    private string $name;
    private string $description;
    private int $baseIncome;
    private int $prestigeBonus;

    private Database $db;

    // Default roles to seed
    public const ROLES = [
        // Leadership roles
        ['name' => 'Lord', 'description' => 'The ruler of the fiefdom, responsible for governance and protection.', 'income' => 100, 'prestige' => 50],
        ['name' => 'Steward', 'description' => 'Manages the day-to-day affairs of the estate.', 'income' => 50, 'prestige' => 30],
        ['name' => 'Reeve', 'description' => 'Oversees agricultural workers and land management.', 'income' => 30, 'prestige' => 20],

        // Military roles
        ['name' => 'Knight', 'description' => 'A mounted warrior sworn to protect the realm.', 'income' => 40, 'prestige' => 35],
        ['name' => 'Guard', 'description' => 'Protects the settlement and maintains order.', 'income' => 15, 'prestige' => 10],
        ['name' => 'Archer', 'description' => 'A skilled bowman for defense and hunting.', 'income' => 12, 'prestige' => 8],

        // Religious roles
        ['name' => 'Priest', 'description' => 'Provides spiritual guidance and performs religious ceremonies.', 'income' => 25, 'prestige' => 25],
        ['name' => 'Monk', 'description' => 'A member of a religious order, often involved in education and healing.', 'income' => 10, 'prestige' => 15],

        // Skilled trades
        ['name' => 'Blacksmith', 'description' => 'Forges metal tools, weapons, and armor.', 'income' => 20, 'prestige' => 12],
        ['name' => 'Carpenter', 'description' => 'Works with wood to build structures and furniture.', 'income' => 18, 'prestige' => 10],
        ['name' => 'Mason', 'description' => 'Works with stone for construction.', 'income' => 18, 'prestige' => 10],
        ['name' => 'Miller', 'description' => 'Grinds grain into flour at the mill.', 'income' => 15, 'prestige' => 8],
        ['name' => 'Baker', 'description' => 'Bakes bread and other goods for the community.', 'income' => 12, 'prestige' => 6],
        ['name' => 'Brewer', 'description' => 'Produces ale and other beverages.', 'income' => 14, 'prestige' => 7],
        ['name' => 'Weaver', 'description' => 'Creates cloth and textiles from raw fibers.', 'income' => 12, 'prestige' => 6],
        ['name' => 'Tanner', 'description' => 'Processes animal hides into leather.', 'income' => 10, 'prestige' => 5],
        ['name' => 'Potter', 'description' => 'Creates pottery and ceramic goods.', 'income' => 10, 'prestige' => 5],

        // Common workers
        ['name' => 'Farmer', 'description' => 'Works the land to grow crops.', 'income' => 8, 'prestige' => 3],
        ['name' => 'Miner', 'description' => 'Extracts ore and minerals from the earth.', 'income' => 10, 'prestige' => 4],
        ['name' => 'Woodcutter', 'description' => 'Fells trees and processes timber.', 'income' => 8, 'prestige' => 3],
        ['name' => 'Fisherman', 'description' => 'Catches fish from rivers and seas.', 'income' => 8, 'prestige' => 3],
        ['name' => 'Shepherd', 'description' => 'Tends to sheep and other livestock.', 'income' => 7, 'prestige' => 2],
        ['name' => 'Servant', 'description' => 'Performs domestic duties in households.', 'income' => 5, 'prestige' => 1],

        // Merchants
        ['name' => 'Merchant', 'description' => 'Trades goods for profit.', 'income' => 25, 'prestige' => 15],
        ['name' => 'Innkeeper', 'description' => 'Runs an establishment providing food and lodging.', 'income' => 20, 'prestige' => 12],

        // Specialists
        ['name' => 'Healer', 'description' => 'Provides medical care and herbal remedies.', 'income' => 20, 'prestige' => 18],
        ['name' => 'Scribe', 'description' => 'Writes documents and keeps records.', 'income' => 15, 'prestige' => 15],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function create(string $name, string $description, int $baseIncome, int $prestigeBonus): Role
    {
        $role = new Role();
        $role->name = $name;
        $role->description = $description;
        $role->baseIncome = $baseIncome;
        $role->prestigeBonus = $prestigeBonus;

        $role->id = $role->db->insert('roles', [
            'name' => $name,
            'description' => $description,
            'base_income' => $baseIncome,
            'prestige_bonus' => $prestigeBonus,
        ]);

        return $role;
    }

    public static function loadById(int $id): ?Role
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM roles WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function loadByName(string $name): ?Role
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM roles WHERE name = ?", [$name]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Role
    {
        $role = new Role();
        $role->id = $data['id'];
        $role->name = $data['name'];
        $role->description = $data['description'] ?? '';
        $role->baseIncome = $data['base_income'] ?? 0;
        $role->prestigeBonus = $data['prestige_bonus'] ?? 0;

        return $role;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM roles ORDER BY prestige_bonus DESC, name");
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function seedRoles(): void
    {
        $db = Database::getInstance();

        // Check if roles already exist
        $existing = $db->fetch("SELECT COUNT(*) as count FROM roles");
        if ($existing['count'] > 0) {
            return;
        }

        // Create all roles
        foreach (self::ROLES as $roleData) {
            self::create(
                $roleData['name'],
                $roleData['description'],
                $roleData['income'],
                $roleData['prestige']
            );
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'base_income' => $this->baseIncome,
            'prestige_bonus' => $this->prestigeBonus,
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getBaseIncome(): int { return $this->baseIncome; }
    public function getPrestigeBonus(): int { return $this->prestigeBonus; }
}
