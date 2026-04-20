<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for wishlist idempotence.
 *
 * Feature: veteran-marketplace, Property 2: Wishlist uniqueness
 * Validates: Requirements 9.5
 *
 * For any user and item pair, adding the item to the wishlist multiple times
 * must result in exactly one wishlist entry (idempotent insert).
 *
 * Uses SQLite in-memory database with INSERT OR IGNORE semantics
 * (equivalent to MySQL's INSERT IGNORE).
 */
class WishlistIdempotenceTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE wishlists (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                item_id    INTEGER NOT NULL,
                created_at TEXT    DEFAULT (datetime('now')),
                UNIQUE (user_id, item_id)
            )
        ");
    }

    // -------------------------------------------------------------------------
    // Helper: add item to wishlist (mirrors wishlist_toggle.php add logic)
    // -------------------------------------------------------------------------

    private function addToWishlist(int $userId, int $itemId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT OR IGNORE INTO wishlists (user_id, item_id) VALUES (?, ?)"
        );
        $stmt->execute([$userId, $itemId]);
    }

    private function countWishlistEntries(int $userId, int $itemId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM wishlists WHERE user_id = ? AND item_id = ?"
        );
        $stmt->execute([$userId, $itemId]);
        return (int) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Example-based tests
    // -------------------------------------------------------------------------

    /** @test */
    public function addingItemOnceCreatesOneEntry(): void
    {
        $this->addToWishlist(1, 10);

        $this->assertSame(1, $this->countWishlistEntries(1, 10));
    }

    /** @test */
    public function addingItemTwiceStillCreatesOneEntry(): void
    {
        $this->addToWishlist(1, 10);
        $this->addToWishlist(1, 10); // duplicate

        $this->assertSame(1, $this->countWishlistEntries(1, 10));
    }

    /** @test */
    public function addingItemTenTimesStillCreatesOneEntry(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->addToWishlist(1, 10);
        }

        $this->assertSame(1, $this->countWishlistEntries(1, 10));
    }

    /** @test */
    public function differentUsersCanWishlistSameItem(): void
    {
        $this->addToWishlist(1, 10);
        $this->addToWishlist(2, 10);

        $this->assertSame(1, $this->countWishlistEntries(1, 10));
        $this->assertSame(1, $this->countWishlistEntries(2, 10));
    }

    /** @test */
    public function sameUserCanWishlistDifferentItems(): void
    {
        $this->addToWishlist(1, 10);
        $this->addToWishlist(1, 20);

        $this->assertSame(1, $this->countWishlistEntries(1, 10));
        $this->assertSame(1, $this->countWishlistEntries(1, 20));
    }

    /** @test */
    public function totalRowCountIsCorrectAfterDuplicates(): void
    {
        // Add 3 unique pairs + duplicates
        $this->addToWishlist(1, 10);
        $this->addToWishlist(1, 20);
        $this->addToWishlist(2, 10);
        // Duplicates
        $this->addToWishlist(1, 10);
        $this->addToWishlist(2, 10);

        $stmt  = $this->pdo->query("SELECT COUNT(*) FROM wishlists");
        $total = (int) $stmt->fetchColumn();

        $this->assertSame(3, $total, 'Only 3 unique (user_id, item_id) pairs should exist');
    }

    // -------------------------------------------------------------------------
    // Property-based test: 100 iterations
    // Feature: veteran-marketplace, Property 2: Wishlist uniqueness
    // -------------------------------------------------------------------------

    /** @test */
    public function propertyWishlistIdempotence(): void
    {
        // For 100 random (user_id, item_id) pairs, adding the same pair
        // multiple times must always result in exactly one entry.
        $seed = 99991;
        for ($i = 0; $i < 100; $i++) {
            $seed   = ($seed * 1103515245 + 12345) & 0x7fffffff;
            $userId = 100 + ($seed % 50);
            $itemId = 200 + ($seed % 50);

            // Add between 2 and 5 times
            $times = 2 + ($i % 4);
            for ($t = 0; $t < $times; $t++) {
                $this->addToWishlist($userId, $itemId);
            }

            $this->assertSame(
                1,
                $this->countWishlistEntries($userId, $itemId),
                "Iteration $i: user=$userId item=$itemId added $times times should yield 1 entry"
            );
        }
    }
}
