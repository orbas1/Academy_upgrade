<?php

namespace Tests\Unit;

use App\Casts\EncryptedAttribute;
use App\Models\User;
use Tests\TestCase;

class EncryptedAttributeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    public function test_it_encrypts_and_decrypts_scalar_values(): void
    {
        $cast = new EncryptedAttribute();
        $model = new User();

        $encrypted = $cast->set($model, 'about', 'sensitive', []);

        $this->assertIsString($encrypted);
        $this->assertNotSame('sensitive', $encrypted);

        $decrypted = $cast->get($model, 'about', $encrypted, []);

        $this->assertSame('sensitive', $decrypted);
    }

    public function test_it_handles_array_payloads(): void
    {
        $cast = new EncryptedAttribute();
        $model = new User();
        $value = ['twitter' => '@academy'];

        $encrypted = $cast->set($model, 'social_links', $value, []);
        $decoded = $cast->get($model, 'social_links', $encrypted, []);

        $this->assertEquals($value, $decoded);
    }

    public function test_it_returns_plain_value_when_not_encrypted(): void
    {
        $cast = new EncryptedAttribute();
        $model = new User();

        $this->assertSame('plain', $cast->get($model, 'skills', 'plain', []));
    }
}
