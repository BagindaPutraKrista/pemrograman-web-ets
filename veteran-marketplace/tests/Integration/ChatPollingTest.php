<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for chat polling (id > last_id filter).
 *
 * Feature: veteran-marketplace, Property 4: Chat message ordering
 * Validates: Requirements 8.3, 8.4
 *
 * The polling endpoint must return only messages with id > last_id,
 * ordered by id ASC (ascending creation order).
 *
 * Uses SQLite in-memory database to simulate chat_rooms and chat_messages.
 */
class ChatPollingTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE chat_rooms (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                buyer_id   INTEGER NOT NULL,
                seller_id  INTEGER NOT NULL,
                item_id    INTEGER NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE chat_messages (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                room_id    INTEGER NOT NULL,
                sender_id  INTEGER NOT NULL,
                message    TEXT    NOT NULL,
                is_read    INTEGER DEFAULT 0,
                created_at TEXT    DEFAULT (datetime('now'))
            )
        ");

        // Seed one chat room: buyer=1, seller=2, item=100
        $this->pdo->exec(
            "INSERT INTO chat_rooms (buyer_id, seller_id, item_id) VALUES (1, 2, 100)"
        );
    }

    // -------------------------------------------------------------------------
    // Helper: insert a message and return its auto-increment id
    // -------------------------------------------------------------------------

    private function insertMessage(int $roomId, int $senderId, string $message): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)"
        );
        $stmt->execute([$roomId, $senderId, $message]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Replicate the core query from api/chat_poll.php.
     * Returns messages with id > $lastId for the given room, ordered ASC.
     */
    private function pollMessages(int $roomId, int $lastId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, sender_id, message, created_at
             FROM chat_messages
             WHERE room_id = ? AND id > ?
             ORDER BY id ASC"
        );
        $stmt->execute([$roomId, $lastId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Example-based tests
    // -------------------------------------------------------------------------

    /** @test */
    public function pollWithLastId0ReturnsAllMessages(): void
    {
        $this->insertMessage(1, 1, 'Halo');
        $this->insertMessage(1, 2, 'Hai, barangnya masih ada?');
        $this->insertMessage(1, 1, 'Masih ada!');

        $messages = $this->pollMessages(1, 0);

        $this->assertCount(3, $messages);
    }

    /** @test */
    public function pollWithLastIdReturnsOnlyNewerMessages(): void
    {
        $id1 = $this->insertMessage(1, 1, 'Pesan 1');
        $id2 = $this->insertMessage(1, 2, 'Pesan 2');
        $id3 = $this->insertMessage(1, 1, 'Pesan 3');

        // Poll after first message
        $messages = $this->pollMessages(1, $id1);

        $this->assertCount(2, $messages);
        $this->assertSame((string) $id2, (string) $messages[0]['id']);
        $this->assertSame((string) $id3, (string) $messages[1]['id']);
    }

    /** @test */
    public function pollWithLastIdEqualToLatestReturnsEmpty(): void
    {
        $id1 = $this->insertMessage(1, 1, 'Pesan terakhir');

        $messages = $this->pollMessages(1, $id1);

        $this->assertCount(0, $messages, 'No new messages after last_id');
    }

    /** @test */
    public function messagesAreReturnedInAscendingOrder(): void
    {
        $id1 = $this->insertMessage(1, 1, 'Pertama');
        $id2 = $this->insertMessage(1, 2, 'Kedua');
        $id3 = $this->insertMessage(1, 1, 'Ketiga');

        $messages = $this->pollMessages(1, 0);

        $ids = array_column($messages, 'id');
        $this->assertSame(
            [(string) $id1, (string) $id2, (string) $id3],
            array_map('strval', $ids),
            'Messages must be ordered by id ASC'
        );
    }

    /** @test */
    public function pollDoesNotReturnMessagesFromOtherRooms(): void
    {
        // Create a second room
        $this->pdo->exec(
            "INSERT INTO chat_rooms (buyer_id, seller_id, item_id) VALUES (3, 4, 200)"
        );

        $this->insertMessage(1, 1, 'Room 1 message');
        $this->insertMessage(2, 3, 'Room 2 message');

        $messages = $this->pollMessages(1, 0);

        $this->assertCount(1, $messages);
        $this->assertSame('Room 1 message', $messages[0]['message']);
    }

    /** @test */
    public function pollWithHighLastIdReturnsOnlySubsequentMessages(): void
    {
        $ids = [];
        for ($i = 1; $i <= 5; $i++) {
            $ids[] = $this->insertMessage(1, ($i % 2) + 1, "Pesan $i");
        }

        // Poll after message 3 (index 2)
        $messages = $this->pollMessages(1, $ids[2]);

        $this->assertCount(2, $messages);
        $this->assertSame((string) $ids[3], (string) $messages[0]['id']);
        $this->assertSame((string) $ids[4], (string) $messages[1]['id']);
    }

    // -------------------------------------------------------------------------
    // Property-based test: 100 iterations
    // Feature: veteran-marketplace, Property 4: Chat message ordering
    // -------------------------------------------------------------------------

    /** @test */
    public function propertyChatPollingReturnsOnlyMessagesAfterLastId(): void
    {
        // For 100 random last_id values, verify that all returned messages
        // have id strictly greater than last_id and are ordered ASC.

        // Insert 20 messages into room 1
        $insertedIds = [];
        for ($i = 1; $i <= 20; $i++) {
            $insertedIds[] = $this->insertMessage(1, ($i % 2) + 1, "Msg $i");
        }

        $seed = 77777;
        for ($iter = 0; $iter < 100; $iter++) {
            $seed   = ($seed * 1103515245 + 12345) & 0x7fffffff;
            // Pick a random last_id between 0 and max inserted id
            $maxId  = max($insertedIds);
            $lastId = $seed % ($maxId + 1); // 0 .. maxId

            $messages = $this->pollMessages(1, $lastId);

            // All returned messages must have id > lastId
            foreach ($messages as $msg) {
                $this->assertGreaterThan(
                    $lastId,
                    (int) $msg['id'],
                    "Iteration $iter: message id {$msg['id']} must be > last_id $lastId"
                );
            }

            // Messages must be in ascending order
            $ids = array_map(fn($m) => (int) $m['id'], $messages);
            $sorted = $ids;
            sort($sorted);
            $this->assertSame(
                $sorted,
                $ids,
                "Iteration $iter: messages must be ordered ASC"
            );
        }
    }
}
