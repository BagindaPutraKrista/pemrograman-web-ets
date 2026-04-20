<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for password hash round-trip.
 *
 * Feature: veteran-marketplace, Property 1: Password hashing round-trip
 * Validates: Requirements 1.5
 *
 * For any plaintext password submitted during registration, the stored hash
 * must satisfy password_verify(plaintext, hash) === true.
 *
 * Uses SQLite in-memory database to simulate the users table without
 * requiring a live MySQL connection.
 */
class PasswordHashTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id       INTEGER PRIMARY KEY AUTOINCREMENT,
                name     TEXT    NOT NULL,
                email    TEXT    UNIQUE NOT NULL,
                password TEXT    NOT NULL
            )
        ");
    }

    // -------------------------------------------------------------------------
    // Example-based tests
    // -------------------------------------------------------------------------

    /** @test */
    public function hashAndVerifySimplePassword(): void
    {
        $plain = 'password123';
        $hash  = password_hash($plain, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($plain, $hash));
    }

    /** @test */
    public function hashAndVerifyPasswordWithSpecialChars(): void
    {
        $plain = 'P@$$w0rd!#%^&*()';
        $hash  = password_hash($plain, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($plain, $hash));
    }

    /** @test */
    public function wrongPasswordDoesNotVerify(): void
    {
        $plain = 'correctPassword';
        $hash  = password_hash($plain, PASSWORD_BCRYPT);

        $this->assertFalse(password_verify('wrongPassword', $hash));
    }

    /** @test */
    public function registerAndVerifyRoundTripViaDatabase(): void
    {
        $plain = 'mahasiswaUPN2024!';
        $hash  = password_hash($plain, PASSWORD_BCRYPT);

        // Simulate register: store hashed password
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
        );
        $stmt->execute(['Budi Santoso', 'budi@upnjatim.ac.id', $hash]);

        // Simulate login: retrieve hash and verify
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute(['budi@upnjatim.ac.id']);
        $storedHash = $stmt->fetchColumn();

        $this->assertNotFalse($storedHash, 'User should exist in DB');
        $this->assertTrue(password_verify($plain, $storedHash));
    }

    /** @test */
    public function hashesAreDifferentForSamePassword(): void
    {
        // bcrypt generates a new salt each time — two hashes of the same
        // plaintext must be different strings but both must verify correctly.
        $plain = 'samePassword';
        $hash1 = password_hash($plain, PASSWORD_BCRYPT);
        $hash2 = password_hash($plain, PASSWORD_BCRYPT);

        $this->assertNotSame($hash1, $hash2, 'Each hash should use a unique salt');
        $this->assertTrue(password_verify($plain, $hash1));
        $this->assertTrue(password_verify($plain, $hash2));
    }

    // -------------------------------------------------------------------------
    // Property-based test: 100 random passwords
    // Feature: veteran-marketplace, Property 1: Password hashing round-trip
    // -------------------------------------------------------------------------

    /** @test */
    public function propertyPasswordHashRoundTrip(): void
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $len   = strlen($chars);

        for ($i = 0; $i < 100; $i++) {
            // Generate a random password of length 8–20
            $pwLen = 8 + ($i % 13);
            $plain = '';
            for ($j = 0; $j < $pwLen; $j++) {
                $plain .= $chars[($i * 31 + $j * 17 + 7) % $len];
            }

            $hash = password_hash($plain, PASSWORD_BCRYPT);

            $this->assertTrue(
                password_verify($plain, $hash),
                "Round-trip failed for password at iteration $i"
            );

            // A different string must NOT verify
            $this->assertFalse(
                password_verify($plain . '_wrong', $hash),
                "Wrong password should not verify at iteration $i"
            );
        }
    }
}
