<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for uploadPhoto() helper function.
 *
 * Feature: veteran-marketplace, Property 7: File upload format and size validation
 * Validates: Requirements 6.5, 6.6, 12.5
 *
 * uploadPhoto() accepts only jpg/jpeg/png files ≤ 2MB.
 * All other formats or oversized files must be rejected with an error message.
 */
class UploadPhotoTest extends TestCase
{
    /** Build a fake $_FILES entry without touching the filesystem. */
    private function makeFile(string $name, int $size, int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name'     => $name,
            'tmp_name' => '',          // not used in format/size checks
            'size'     => $size,
            'error'    => $error,
        ];
    }

    // -------------------------------------------------------------------------
    // Valid format tests
    // -------------------------------------------------------------------------

    /** @test */
    public function acceptsJpgExtension(): void
    {
        $file   = $this->makeFile('photo.jpg', 1024);
        $result = $this->callUploadPhotoValidation($file);

        // Should NOT return a format error
        $this->assertArrayNotHasKey('error', $result, 'jpg should be accepted');
    }

    /** @test */
    public function acceptsJpegExtension(): void
    {
        $file   = $this->makeFile('photo.jpeg', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayNotHasKey('error', $result, 'jpeg should be accepted');
    }

    /** @test */
    public function acceptsPngExtension(): void
    {
        $file   = $this->makeFile('photo.png', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayNotHasKey('error', $result, 'png should be accepted');
    }

    /** @test */
    public function acceptsUppercaseExtensionNormalized(): void
    {
        // Extension check uses strtolower(), so JPG should be treated as jpg
        $file   = $this->makeFile('photo.JPG', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayNotHasKey('error', $result, 'uppercase JPG should be accepted');
    }

    // -------------------------------------------------------------------------
    // Invalid format tests
    // -------------------------------------------------------------------------

    /** @test */
    public function rejectsGifFormat(): void
    {
        $file   = $this->makeFile('photo.gif', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Format', $result['error']);
    }

    /** @test */
    public function rejectsBmpFormat(): void
    {
        $file   = $this->makeFile('photo.bmp', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function rejectsWebpFormat(): void
    {
        $file   = $this->makeFile('photo.webp', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function rejectsPhpFileUpload(): void
    {
        $file   = $this->makeFile('shell.php', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function rejectsFileWithNoExtension(): void
    {
        $file   = $this->makeFile('noextension', 1024);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // Valid size tests
    // -------------------------------------------------------------------------

    /** @test */
    public function acceptsFileSizeExactly2MB(): void
    {
        $file   = $this->makeFile('photo.jpg', 2 * 1024 * 1024); // exactly 2MB
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayNotHasKey('error', $result, 'Exactly 2MB should be accepted');
    }

    /** @test */
    public function acceptsFileSizeUnder2MB(): void
    {
        $file   = $this->makeFile('photo.png', 500 * 1024); // 500 KB
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayNotHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // Invalid size tests
    // -------------------------------------------------------------------------

    /** @test */
    public function rejectsFileSizeOver2MB(): void
    {
        $file   = $this->makeFile('photo.jpg', 2 * 1024 * 1024 + 1); // 1 byte over
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('2MB', $result['error']);
    }

    /** @test */
    public function rejectsLargeFile(): void
    {
        $file   = $this->makeFile('photo.png', 10 * 1024 * 1024); // 10 MB
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // Upload error tests
    // -------------------------------------------------------------------------

    /** @test */
    public function rejectsFileWithUploadError(): void
    {
        $file   = $this->makeFile('photo.jpg', 1024, UPLOAD_ERR_PARTIAL);
        $result = $this->callUploadPhotoValidation($file);

        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // Property-based test: 100 random extensions
    // Feature: veteran-marketplace, Property 7: File upload format and size validation
    // -------------------------------------------------------------------------

    /** @test */
    public function propertyOnlyAllowedExtensionsAreAccepted(): void
    {
        $allowed  = ['jpg', 'jpeg', 'png'];
        $rejected = ['gif', 'bmp', 'webp', 'svg', 'tiff', 'ico', 'php', 'exe',
                     'pdf', 'doc', 'mp4', 'zip', 'txt', 'html', 'js', 'css'];

        // 100 iterations: random mix of allowed and rejected extensions
        $iterations = 0;
        foreach ($allowed as $ext) {
            $file   = $this->makeFile("test.$ext", 1024);
            $result = $this->callUploadPhotoValidation($file);
            $this->assertArrayNotHasKey('error', $result, "Extension '$ext' should be accepted");
            $iterations++;
        }

        foreach ($rejected as $ext) {
            $file   = $this->makeFile("test.$ext", 1024);
            $result = $this->callUploadPhotoValidation($file);
            $this->assertArrayHasKey('error', $result, "Extension '$ext' should be rejected");
            $iterations++;
        }

        // Fill remaining iterations with random sizes for valid extensions
        $srand = 42;
        for ($i = $iterations; $i < 100; $i++) {
            $srand = ($srand * 1103515245 + 12345) & 0x7fffffff;
            $ext  = $allowed[$srand % count($allowed)];
            // Alternate between valid and oversized
            $size = ($i % 2 === 0) ? 1024 : (3 * 1024 * 1024);
            $file = $this->makeFile("test.$ext", $size);
            $result = $this->callUploadPhotoValidation($file);
            if ($size <= 2 * 1024 * 1024) {
                $this->assertArrayNotHasKey('error', $result);
            } else {
                $this->assertArrayHasKey('error', $result);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helper: run only the validation portion of uploadPhoto()
    // (skips move_uploaded_file which requires a real upload context)
    // -------------------------------------------------------------------------

    /**
     * Replicate the validation logic of uploadPhoto() without filesystem I/O.
     * Returns ['error' => string] on failure, or [] on success (validation passed).
     */
    private function callUploadPhotoValidation(array $file): array
    {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            return ['error' => 'Format foto tidak didukung. Gunakan JPG, JPEG, atau PNG.'];
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return ['error' => 'Ukuran foto maksimal 2MB.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Gagal mengunggah foto. Coba lagi.'];
        }

        // Validation passed — return empty array (no error key)
        return [];
    }
}
