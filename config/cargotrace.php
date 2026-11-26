<?php

return [
    'group' => [
        'name' => env('GROUP_NAME', 'Group A'),
        'kpi' => env('GROUP_KPI', 'On-time delivery rate'),
    ],

    // Group-specific parameter: a shipment is flagged DELAYED when (ETA - expected arrival) >= threshold.
    'delay_threshold_hours' => (float) env('DELAY_THRESHOLD_HOURS', 12),

    // Severity bands applied to any positive delay: <=low_max LOW, <=medium_max MEDIUM, otherwise HIGH.
    'severity' => [
        'low_max_hours' => (float) env('DELAY_LOW_MAX_HOURS', 6),
        'medium_max_hours' => (float) env('DELAY_MEDIUM_MAX_HOURS', 24),
    ],
];
