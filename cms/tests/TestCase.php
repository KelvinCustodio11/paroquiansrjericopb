<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Garante que content:export/build-php nunca escrevam no site real durante testes.
        // Usa sempre /tmp/paroquia-test-site independente de SITE_ROOT no .env.
        config(['site.root' => '/tmp/paroquia-test-site']);
    }
}
