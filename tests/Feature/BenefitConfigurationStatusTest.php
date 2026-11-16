<?php

namespace Tests\Feature;

use App\Models\BenefitConfiguration;
use Tests\TestCase;

class BenefitConfigurationStatusTest extends TestCase
{
    public function test_active_configuration_status(): void
    {
        $configuration = new BenefitConfiguration([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        $this->assertSame('Active', $configuration->status);
    }

    public function test_upcoming_configuration_status(): void
    {
        $configuration = new BenefitConfiguration([
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(10),
        ]);

        $this->assertSame('Upcoming', $configuration->status);
    }

    public function test_expired_configuration_status(): void
    {
        $configuration = new BenefitConfiguration([
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDay(),
        ]);

        $this->assertSame('Expired', $configuration->status);
    }
}