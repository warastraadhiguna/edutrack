<?php

namespace Tests\Feature;

use App\Models\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_default_period_clears_the_default_flag_from_other_periods(): void
    {
        $firstPeriod = Period::query()->create([
            'name' => '2025 Ganjil',
            'default' => 1,
        ]);

        $secondPeriod = Period::query()->create([
            'name' => '2025 Genap',
            'default' => 1,
        ]);

        $this->assertFalse($firstPeriod->fresh()->default);
        $this->assertTrue($secondPeriod->fresh()->default);
    }

    public function test_updating_a_period_to_default_clears_the_default_flag_from_other_periods(): void
    {
        $firstPeriod = Period::query()->create([
            'name' => '2025 Ganjil',
            'default' => 1,
        ]);

        $secondPeriod = Period::query()->create([
            'name' => '2025 Genap',
            'default' => 0,
        ]);

        $secondPeriod->update(['default' => 1]);

        $this->assertFalse($firstPeriod->fresh()->default);
        $this->assertTrue($secondPeriod->fresh()->default);
    }
}
