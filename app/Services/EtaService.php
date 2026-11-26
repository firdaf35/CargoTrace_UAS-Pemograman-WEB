<?php

namespace App\Services;

use App\Enums\Severity;
use App\Models\Shipment;

/**
 * Deterministic ETA / delay rules (no ML):
 *   delay_hours  = (actual arrival, else estimated arrival) - expected arrival
 *   is_delayed   = delay_hours > 0 AND delay_hours >= group threshold (config cargotrace.delay_threshold_hours)
 *   severity     = LOW (<= low_max), MEDIUM (<= medium_max), HIGH (> medium_max) for any positive delay
 */
class EtaService
{
    public function thresholdHours(): float
    {
        return (float) config('cargotrace.delay_threshold_hours');
    }

    public function severityFor(float $hours): ?Severity
    {
        if ($hours <= 0) {
            return null;
        }

        return match (true) {
            $hours <= (float) config('cargotrace.severity.low_max_hours') => Severity::LOW,
            $hours <= (float) config('cargotrace.severity.medium_max_hours') => Severity::MEDIUM,
            default => Severity::HIGH,
        };
    }

    /**
     * @return array{hours: float, delay_hours: float, is_delayed: bool, severity: ?Severity, reason: string}
     */
    public function evaluate(Shipment $shipment): array
    {
        $reference = $shipment->actual_arrival ?? $shipment->estimated_arrival;

        if (! $reference || ! $shipment->expected_arrival) {
            return ['hours' => 0.0, 'delay_hours' => 0.0, 'is_delayed' => false, 'severity' => null, 'reason' => 'No ETA available yet.'];
        }

        $hours = round(($reference->getTimestamp() - $shipment->expected_arrival->getTimestamp()) / 3600, 2);
        $label = $shipment->actual_arrival ? 'Actual arrival' : 'ETA';
        $expected = $shipment->expected_arrival->format('Y-m-d H:i');
        $ref = $reference->format('Y-m-d H:i');

        if ($hours <= 0) {
            return [
                'hours' => $hours, 'delay_hours' => 0.0, 'is_delayed' => false, 'severity' => null,
                'reason' => sprintf('%s %s is on or before expected arrival %s (%+.2fh). On time.', $label, $ref, $expected, $hours),
            ];
        }

        $threshold = $this->thresholdHours();
        $isDelayed = $hours >= $threshold;
        $severity = $this->severityFor($hours);
        $low = config('cargotrace.severity.low_max_hours');
        $med = config('cargotrace.severity.medium_max_hours');

        return [
            'hours' => $hours,
            'delay_hours' => $hours,
            'is_delayed' => $isDelayed,
            'severity' => $severity,
            'reason' => sprintf(
                '%s %s is %.2fh after expected arrival %s. Threshold %gh => %s. Severity %s (LOW <= %gh, MEDIUM <= %gh, HIGH > %gh).',
                $label, $ref, $hours, $expected, $threshold,
                $isDelayed ? 'DELAYED' : 'within tolerance',
                $severity->value, $low, $med, $med
            ),
        ];
    }

    /** Recalculate and persist the delay fields of the shipment. */
    public function apply(Shipment $shipment): Shipment
    {
        $r = $this->evaluate($shipment);

        $shipment->forceFill([
            'is_delayed' => $r['is_delayed'],
            'delay_hours' => $r['delay_hours'],
            'delay_severity' => $r['severity']?->value,
            'delay_reason' => $r['reason'],
        ])->save();

        return $shipment;
    }
}
