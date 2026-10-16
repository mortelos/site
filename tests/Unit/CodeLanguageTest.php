<?php

namespace Tests\Unit;

use Mortelos\DevTools\Testing\CodeLanguageGuard;
use PHPUnit\Framework\TestCase;

class CodeLanguageTest extends TestCase
{
    public function test_code_is_in_english(): void
    {
        CodeLanguageGuard::assertClean(dirname(__DIR__, 2));
    }
}
