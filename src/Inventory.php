<?php

namespace FiefdomForge;

class Inventory
{
    private int $userId;
    private Database $db;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->db = Database::getInstance();
    }

    public function getQuantity(int $goodId): int
    {
        $result = $this->db->fetch(
            "SELECT quantity FROM inventory WHERE user_id = ? AND good_id = ?",
            [$this->userId, $goodId]
        );

        return $result['quantity'] ?? 0;
    }

    public function addGood(int $goodId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $existing = $this->db->fetch(
            "SELECT id, quantity FROM inventory WHERE user_id = ? AND good_id = ?",
            [$this->userId, $goodId]
        );

        if ($existing) {
            $this->db->query(
                "UPDATE inventory SET quantity = quantity + ? WHERE user_id = ? AND good_id = ?",
                [$quantity, $this->userId, $goodId]
            );
        } else {
            $this->db->insert('inventory', [
                'user_id' => $this->userId,
                'good_id' => $goodId,
                'quantity' => $quantity,
            ]);
        }
    }

    public function removeGood(int $goodId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $current = $this->getQuantity($goodId);
        if ($current < $quantity) {
            return false;
        }

        $newQuantity = $current - $quantity;

        if ($newQuantity === 0) {
            $this->db->query(
                "DELETE FROM inventory WHERE user_id = ? AND good_id = ?",
                [$this->userId, $goodId]
            );
        } else {
            $this->db->query(
                "UPDATE inventory SET quantity = ? WHERE user_id = ? AND good_id = ?",
                [$newQuantity, $this->userId, $goodId]
            );
        }

        return true;
    }

    public function getAllGoods(): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, g.name as good_name, g.base_price, g.is_resource
             FROM inventory i
             JOIN goods g ON i.good_id = g.id
             WHERE i.user_id = ? AND i.quantity > 0
             ORDER BY g.name",
            [$this->userId]
        );
    }

    public function getTotalValue(): int
    {
        $result = $this->db->fetch(
            "SELECT SUM(i.quantity * g.base_price) as total
             FROM inventory i
             JOIN goods g ON i.good_id = g.id
             WHERE i.user_id = ?",
            [$this->userId]
        );

        return (int)($result['total'] ?? 0);
    }

    public static function seedStarterInventory(int $userId): void
    {
        $inventory = new self($userId);

        // Check if already has inventory
        $existing = $inventory->getAllGoods();
        if (!empty($existing)) {
            return;
        }

        // Give starter resources
        $starterGoods = [
            'Wood' => 20,
            'Stone' => 10,
            'Wheat' => 30,
            'Iron Ore' => 5,
        ];

        foreach ($starterGoods as $goodName => $quantity) {
            $good = Good::loadByName($goodName);
            if ($good) {
                $inventory->addGood($good->getId(), $quantity);
            }
        }
    }
}
