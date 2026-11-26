# Data dictionary

| Table | Column | Type | Notes |
|---|---|---|---|
| users | id, name, email (unique), password (hashed), role, company, timestamps | | role: `admin`, `operator`, `customer`; not mass-assignable |
| carriers | id, name (unique), code (unique, <=10), contact_email, is_active | | |
| locations | id, name, city, country, type, latitude(10,7), longitude(10,7) | | unique(name, city, country); type PORT/WAREHOUSE/HUB/CITY |
| shipments | id, shipment_number (unique) | string | format `SHP-YYYY-NNNNNN` |
| | customer_id, carrier_id, origin_id, destination_id | FK | restrict on delete |
| | created_by | FK users, nullable | |
| | cargo_type, weight_kg, volume_cbm | | |
| | expected_departure, expected_arrival | datetime | plan |
| | estimated_arrival (ETA), actual_arrival | datetime | ETA is operator-updated; actual set on DELIVERED |
| | status | string | CREATED, BOOKED, PICKED_UP, IN_TRANSIT, DELIVERED, CANCELLED |
| | is_delayed, delay_hours, delay_severity, delay_reason | | computed by `EtaService` |
| | deleted_at | | soft delete |
| shipment_items | shipment_id, description, quantity, weight_kg | | |
| tracking_events | shipment_id, event_type, status_after, location_id, location_name, latitude, longitude, occurred_at, description, is_exception, created_by | | append-only |
| shipment_exceptions | shipment_id, type, severity, status, description, detected_at, resolved_at, reported_by, resolved_by, resolution_notes | | type: CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE; status: OPEN, INVESTIGATING, RESOLVED |
| notifications | user_id, shipment_id, type, title, message, read_at | | in-app |
| audit_logs | user_id, action, entity_type, entity_id, old_values, new_values, ip_address, created_at | | actor, action, time, entity |
| personal_access_tokens | Laravel Sanctum | | API tokens (24h expiry) |
