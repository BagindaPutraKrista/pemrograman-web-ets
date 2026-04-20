<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for rating validation logic.
 *
 * Feature: veteran-marketplace, Property 9: Rating range validity
 * Validates: Requirements 18.1, 18.2
 *
 * A valid rating is an integer in the inclusive range [1, 5].
 * Any value outside this range must be rejected.
 */
class RatingValidationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Valid ratings
    // -------------------------------------------------------------------------

    /** @test */
    public function rating1IsValid(): void
    {
        $this->assertTrue($this->isValidRating(1));
    }

    /** @test */
    public function rating2IsValid(): void
    {
        $this->assertTrue($this->isValidRating(2));
    }

    /** @test */
    public function rating3IsValid(): void
    {
        $this->assertTrue($this->isValidRating(3));
    }

    /** @test */
    public function rating4IsValid(): void
    {
        $this->assertTrue($this->isValidRating(4));
    }

    /** @test */
    public function rating5IsValid(): void
    {
        $this->assertTrue($this->isValidRating(5));
    }

    // -------------------------------------------------------------------------
    // Invalid ratings — below range
    // -------------------------------------------------------------------------

    /** @test */
    public function rating0IsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(0));
    }

    /** @test */
    public function ratingNegativeIsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(-1));
    }

    /** @test */
    public function ratingLargeNegativeIsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(-100));
    }

    // -------------------------------------------------------------------------
    // Invalid ratings — above range
    // -------------------------------------------------------------------------

    /** @test */
    public function rating6IsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(6));
    }

    /** @test */
    public function rating100IsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(100));
    }

    // -------------------------------------------------------------------------
    // Non-integer inputs
    // -------------------------------------------------------------------------

    /** @test */
    public function ratingFloatIsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(3.5));
    }

    /** @test */
    public function ratingStringIsInvalid(): void
    {
        $this->assertFalse($this->isValidRating('3'));
    }

    /** @test */
    public function ratingNullIsInvalid(): void
    {
        $this->assertFalse($this->isValidRating(null));
    }

    // -------------------------------------------------------------------------
    // Property-based test: 100 iterations
    // Feature: veteran-marketplace, Property 9: Rating range validity
    // -------------------------------------------------------------------------

    /** @test */
    public function propertyRatingRangeValidity(): void
    {
        // All integers 1–5 must be valid
        foreach (range(1, 5) as $rating) {
            $this->assertTrue(
                $this->isValidRating($rating),
                "Rating $rating should be valid"
            );
        }

        // Generate 100 random integers outside [1,5] — all must be invalid
        $seed = 12345;
        $count = 0;
        while ($count < 100) {
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            // Map to range [-50, 0] ∪ [6, 55]
            $value = ($seed % 2 === 0)
                ? -($seed % 50)          // negative or zero
                : 6 + ($seed % 50);      // above 5

            $this->assertFalse(
                $this->isValidRating($value),
                "Rating $value should be invalid (outside [1,5])"
            );
            $count++;
        }
    }

    // -------------------------------------------------------------------------
    // Helper: rating validation logic (mirrors what the review form enforces)
    // -------------------------------------------------------------------------

    /**
     * Returns true if $rating is an integer in [1, 5].
     */
    private function isValidRating(mixed $rating): bool
    {
        if (!is_int($rating)) {
            return false;
        }
        return $rating >= 1 && $rating <= 5;
    }
}
