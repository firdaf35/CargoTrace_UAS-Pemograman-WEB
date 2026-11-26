<?php

namespace Tests\Unit;

use App\Enums\Severity;
use App\Models\Shipment;
use App\Services\EtaService;
use Tests\TestCase;

class EtaServiceTest extends TestCase
{
    private function shipment(int $delayHours, ?int $actualOffset = null): Shipment
    {
        $expected = now()->addDays(10)->startOfMinute();
        $s = new Shipment;
        $s->expected_arrival = $expected;
        $s->estimated_arrival = $expected->copy()->addHours($delayHours);
        if ($actualOffset !== null) {
            $s->actual_arrival = $expected->copy()->addHours($actualOffset);
        }

        return $s;
    }

    public function test_on_time_is_not_delayed(): void
    {
        $r = (new EtaService)->evaluate($this->shipment(-3));

        $this->assertFalse($r['is_delayed']);
        $this->assertNull($r['severity']);
        $this->assertSame(0.0, $r['delay_hours']);
    }

    public function test_delay_below_threshold_is_tolerated_but_has_severity(): void
    {
        config(['cargotrace.delay_threshold_hours' => 12]);
        $r = (new EtaService)->evaluate($this->shipment(5));

        $this->assertFalse($r['is_delayed']);
        $this->assertSame(Severity::LOW, $r['severity']);
        $this->assertSame(5.0, $r['delay_hours']);
    }

    public function test_delay_at_or_above_threshold_is_flagged(): void
    {
        config(['cargotrace.delay_threshold_hours' => 12]);
        $this->assertTrue((new EtaService)->evaluate($this->shipment(12))['is_delayed']);
        $this->assertTrue((new EtaService)->evaluate($this->shipment(20))['is_delayed']);
    }

    public function test_threshold_is_configurable_per_group(): void
    {
        config(['cargotrace.delay_threshold_hours' => 24]);
        $this->assertFalse((new EtaService)->evaluate($this->shipment(20))['is_delayed']);

        config(['cargotrace.delay_threshold_hours' => 18]);
        $this->assertTrue((new EtaService)->evaluate($this->shipment(20))['is_delayed']);
    }

    public function test_severity_bands(): void
    {
        $svc = new EtaService;

        $this->assertNull($svc->severityFor(0));
        $this->assertSame(Severity::LOW, $svc->severityFor(6));
        $this->assertSame(Severity::MEDIUM, $svc->severityFor(6.01));
        $this->assertSame(Severity::MEDIUM, $svc->severityFor(24));
        $this->assertSame(Severity::HIGH, $svc->severityFor(24.01));
    }

    public function test_actual_arrival_takes_precedence_over_eta(): void
    {
        $r = (new EtaService)->evaluate($this->shipment(30, actualOffset: 1));

        $this->assertSame(1.0, $r['delay_hours']);
        $this->assertFalse($r['is_delayed']);
    }

    public function test_reason_explains_values_used(): void
    {
        config(['cargotrace.delay_threshold_hours' => 12]);
        $r = (new EtaService)->evaluate($this->shipment(30));

        $this->assertStringContainsString('30.00h', $r['reason']);
        $this->assertStringContainsString('Threshold 12h', $r['reason']);
        $this->assertStringContainsString('HIGH', $r['reason']);
    }
}
