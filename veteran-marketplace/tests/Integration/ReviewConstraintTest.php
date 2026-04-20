<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for one-review-per-transaction constraint.
 *
 * Feature: veteran-marketplace, Property 5: One review per transaction
 * Validates: Requirements 18.5, 18.6
 *
 * For any transaction, there must be at most one review.
 * Attempting to submit a second review for the same transaction must be rejected.
 *
 * Uses SQLite in-memory database. The UNIQUE constraint on transaction_id
 * enforces the one-review-per-transaction rule at the database level.
 */
class ReviewConstraintTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE reviews (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                transaction_id INTEGER NOT NULL UNIQUE,
                buyer_id       INTEGER NOT NULL,
                seller_id      INTEGER NOT NULL,
                rating         INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
                comment        TEXT,
                created_at     TEXT DEFAULT (datetime('now'))
            )
        ");
    }

    // -------------------------------------------------------------------------
    // Helper: submit a review (mirrors review form submission logic)
    // Returns true on success, false if rejected (duplicate transaction_id).
    // -------------------------------------------------------------------------

    private function submitReview(
        int $transactionId,
        int $buyerId,
        int $sellerId,
        int $rating,
        string $comment = ''
    ): bool {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO reviews (transaction_id, buyer_id, seller_id, rating, comment)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$transactionId, $buyerId, $sellerId, $rating, $comment]);
            return true;
        } catch (\PDOException $e) {
            // UNIQUE constraint violation → duplicate review
            return false;
        }
    }

    private function countReviewsForTransaction(int $transactionId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reviews WHERE transaction_id = ?"
        );
        $stmt->execute([$transactionId]);
        return (int) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Example-based tests
    // -------------------------------------------------------------------------

    /** @test */
    public function firstReviewIsAccepted(): void
    {
        $result = $this->submitReview(1, 10, 20, 5, 'Barang bagus!');

        $this->assertTrue($result);
        $this->assertSame(1, $this->countReviewsForTransaction(1));
    }

    /** @test */
    public function secondReviewForSameTransactionIsRejected(): void
    {
        $this->submitReview(1, 10, 20, 5, 'Barang bagus!');
        $result = $this->submitReview(1, 10, 20, 3, 'Sebenarnya biasa saja');

        $this->assertFalse($result, 'Second review for same transaction must be rejected');
        $this->assertSame(1, $this->countReviewsForTransaction(1));
    }

    /** @test */
    public function differentTransactionsCanEachHaveOneReview(): void
    {
        $r1 = $this->submitReview(1, 10, 20, 5, 'Transaksi 1 bagus');
        $r2 = $this->submitReview(2, 11, 20, 4, 'Transaksi 2 oke');

        $this->assertTrue($r1);
        $this->assertTrue($r2);
        $this->assertSame(1, $this->countReviewsForTransaction(1));
        $this->assertSame(1, $this->countReviewsForTransaction(2));
    }

    /** @test */
    public function firstReviewDataIsPreservedAfterRejectedDuplicate(): void
    {
        $this->submitReview(1, 10, 20, 5, 'Review pertama');
        $this->submitReview(1, 10, 20, 1, 'Review kedua (harus ditolak)');

        $stmt = $this->pdo->prepare(
            "SELECT rating, comment FROM reviews WHERE transaction_id = ?"
        );
        $stmt->execute([1]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame(5, (int) $row['rating'], 'Original rating must be preserved');
        $this->assertSame('Review pertama', $row['comment']);
    }

    /** @test */
    public function multipleAttemptsStillYieldOneReview(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->submitReview(1, 10, 20, ($i % 5) + 1, "Attempt $i");
        }

        $this->assertSame(1, $this->countReviewsForTransaction(1));
    }

    // -------------------------------------------------------------------------
    // Property-based test: 100 iterations
    // Feature: veteran-marketplace, Property 5: One review per transaction
    // -------------------------------------------------------------------------

    /** @test */
    public function propertyOneReviewPerTransaction(): void
    {
        // For 100 different transactions, submitting multiple reviews must
        // always result in exactly one review per transaction.
        for ($txId = 1; $txId <= 100; $txId++) {
            $buyerId  = 1000 + $txId;
            $sellerId = 2000 + $txId;

            // First submission must succeed
            $first = $this->submitReview($txId, $buyerId, $sellerId, ($txId % 5) + 1, "Review $txId");
            $this->assertTrue($first, "First review for transaction $txId should succeed");

            // Second submission must fail
            $second = $this->submitReview($txId, $buyerId, $sellerId, 1, "Duplicate $txId");
            $this->assertFalse($second, "Second review for transaction $txId should be rejected");

            // Exactly one review must exist
            $this->assertSame(
                1,
                $this->countReviewsForTransaction($txId),
                "Transaction $txId must have exactly one review"
            );
        }
    }
}
