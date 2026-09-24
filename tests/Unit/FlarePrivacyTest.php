<?php

namespace Tests\Unit;

use Spatie\FlareClient\Enums\CollectType;
use Tests\TestCase;

class FlarePrivacyTest extends TestCase
{
    public function test_error_reports_exclude_customer_context(): void
    {
        foreach ([
            CollectType::Requests,
            CollectType::Queries,
            CollectType::Jobs,
            CollectType::LogsWithErrors,
            CollectType::StackFrameArguments,
        ] as $type) {
            $this->assertArrayNotHasKey($type->value, config('flare.collects'));
        }

        $this->assertTrue(config('flare.censor.client_ips'));
        $this->assertTrue(config('flare.censor.cookies'));
        $this->assertTrue(config('flare.censor.session'));
    }
}
