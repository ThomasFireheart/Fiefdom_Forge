<?php

namespace FiefdomForge;

class Citizen
{
    private ?int $id = null;
    private int $userId;
    private string $name;
    private int $age = 0;
    private string $gender;
    private ?int $roleId = null;
    private int $wealth = 0;
    private int $health = 100;
    private int $happiness = 100;
    private array $skillLevels = [];
    private ?int $homeBuildingId = null;
    private ?int $workBusinessId = null;
    private bool $isAlive = true;
    private ?int $spouseId = null;

    private Database $db;

    // Life stage constants
    public const AGE_CHILD = 14;
    public const AGE_ADULT = 18;
    public const AGE_ELDER = 60;
    public const AGE_MAX = 80;

    // Stat bounds
    public const MIN_STAT = 0;
    public const MAX_STAT = 100;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
    }

    public static function create(int $userId, string $name, int $age, string $gender): Citizen
    {
        $citizen = new Citizen($userId);
        $citizen->name = $name;
        $citizen->age = $age;
        $citizen->gender = $gender;
        $citizen->health = 100;
        $citizen->happiness = 100;
        $citizen->wealth = rand(10, 100);

        $citizen->id = $citizen->db->insert('citizens', [
            'user_id' => $userId,
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'wealth' => $citizen->wealth,
            'health' => $citizen->health,
            'happiness' => $citizen->happiness,
            'skill_levels' => json_encode([]),
            'is_alive' => true,
        ]);

        return $citizen;
    }

    public static function loadById(int $id): ?Citizen
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM citizens WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function fromArray(array $data): Citizen
    {
        $citizen = new Citizen($data['user_id']);
        $citizen->id = $data['id'];
        $citizen->name = $data['name'];
        $citizen->age = $data['age'];
        $citizen->gender = $data['gender'];
        $citizen->roleId = $data['role_id'];
        $citizen->wealth = $data['wealth'];
        $citizen->health = $data['health'];
        $citizen->happiness = $data['happiness'];
        $citizen->skillLevels = json_decode($data['skill_levels'] ?? '{}', true);
        $citizen->homeBuildingId = $data['home_building_id'];
        $citizen->workBusinessId = $data['work_business_id'];
        $citizen->isAlive = (bool) $data['is_alive'];
        $citizen->spouseId = $data['spouse_id'] ?? null;

        return $citizen;
    }

    public function save(): void
    {
        if (!$this->id) {
            return;
        }

        $this->db->update('citizens', [
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender,
            'role_id' => $this->roleId,
            'wealth' => $this->wealth,
            'health' => $this->health,
            'happiness' => $this->happiness,
            'skill_levels' => json_encode($this->skillLevels),
            'home_building_id' => $this->homeBuildingId,
            'work_business_id' => $this->workBusinessId,
            'is_alive' => $this->isAlive ? 1 : 0,
            'spouse_id' => $this->spouseId,
        ], 'id = ?', [$this->id]);
    }

    public function ageOneYear(): array
    {
        $events = [];
        $this->age++;

        // Life stage transitions
        if ($this->age === self::AGE_ADULT) {
            $events[] = [
                'type' => 'coming_of_age',
                'message' => "{$this->name} has come of age and can now work.",
            ];
        }

        if ($this->age === self::AGE_ELDER) {
            $events[] = [
                'type' => 'elder',
                'message' => "{$this->name} has become an elder.",
            ];
        }

        // Natural health decline for elders
        if ($this->age >= self::AGE_ELDER) {
            $decline = rand(1, 5);
            $this->modifyHealth(-$decline);
        }

        return $events;
    }

    public function checkDeath(): ?array
    {
        // Natural death from old age
        if ($this->age >= self::AGE_MAX) {
            $this->die();
            return [
                'type' => 'death_old_age',
                'message' => "{$this->name} has passed away peacefully of old age at {$this->age}.",
            ];
        }

        // Death from poor health
        if ($this->health <= 0) {
            $this->die();
            return [
                'type' => 'death_illness',
                'message' => "{$this->name} has succumbed to illness at age {$this->age}.",
            ];
        }

        // Random death chance increases with age and poor health
        $deathChance = 0;
        if ($this->age >= self::AGE_ELDER) {
            $deathChance += ($this->age - self::AGE_ELDER) * 0.5;
        }
        if ($this->health < 30) {
            $deathChance += (30 - $this->health) * 0.3;
        }

        if ($deathChance > 0 && rand(1, 100) <= $deathChance) {
            $this->die();
            return [
                'type' => 'death_natural',
                'message' => "{$this->name} has passed away at age {$this->age}.",
            ];
        }

        return null;
    }

    private function die(): void
    {
        $this->isAlive = false;

        // If married, update spouse
        if ($this->spouseId) {
            $spouse = self::loadById($this->spouseId);
            if ($spouse) {
                $spouse->setSpouseId(null);
                $spouse->modifyHappiness(-20);
                $spouse->save();
            }
        }
    }

    public function canMarry(): bool
    {
        return $this->isAlive
            && $this->age >= self::AGE_ADULT
            && $this->spouseId === null;
    }

    public function canWork(): bool
    {
        return $this->isAlive
            && $this->age >= self::AGE_ADULT
            && $this->age < self::AGE_ELDER;
    }

    public function canHaveChildren(): bool
    {
        return $this->isAlive
            && $this->gender === 'female'
            && $this->age >= self::AGE_ADULT
            && $this->age < 45
            && $this->spouseId !== null;
    }

    public function getLifeStage(): string
    {
        if ($this->age < self::AGE_CHILD) {
            return 'child';
        } elseif ($this->age < self::AGE_ADULT) {
            return 'youth';
        } elseif ($this->age < self::AGE_ELDER) {
            return 'adult';
        } else {
            return 'elder';
        }
    }

    public function modifyHealth(int $amount): void
    {
        $this->health = max(self::MIN_STAT, min(self::MAX_STAT, $this->health + $amount));
    }

    public function modifyHappiness(int $amount): void
    {
        $this->happiness = max(self::MIN_STAT, min(self::MAX_STAT, $this->happiness + $amount));
    }

    public function modifyWealth(int $amount): void
    {
        $this->wealth = max(0, $this->wealth + $amount);
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getName(): string { return $this->name; }
    public function getAge(): int { return $this->age; }
    public function getGender(): string { return $this->gender; }
    public function getRoleId(): ?int { return $this->roleId; }
    public function getWealth(): int { return $this->wealth; }
    public function getHealth(): int { return $this->health; }
    public function getHappiness(): int { return $this->happiness; }
    public function getSkillLevels(): array { return $this->skillLevels; }
    public function getHomeBuildingId(): ?int { return $this->homeBuildingId; }
    public function getWorkBusinessId(): ?int { return $this->workBusinessId; }
    public function isAlive(): bool { return $this->isAlive; }
    public function getSpouseId(): ?int { return $this->spouseId; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setRoleId(?int $roleId): void { $this->roleId = $roleId; }
    public function setHomeBuildingId(?int $id): void { $this->homeBuildingId = $id; }
    public function setWorkBusinessId(?int $id): void { $this->workBusinessId = $id; }
    public function setSpouseId(?int $id): void { $this->spouseId = $id; }

    public function setSkillLevel(int $skillId, int $level): void
    {
        $this->skillLevels[$skillId] = max(0, min(100, $level));
    }

    public function getSkillLevel(int $skillId): int
    {
        return $this->skillLevels[$skillId] ?? 0;
    }
}
