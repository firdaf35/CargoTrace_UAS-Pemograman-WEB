# ERD

```mermaid
erDiagram
    users ||--o{ shipments : "customer_id (1..N)"
    users ||--o{ shipments : "created_by (1..N)"
    carriers ||--o{ shipments : "carrier_id"
    locations ||--o{ shipments : "origin_id"
    locations ||--o{ shipments : "destination_id"
    shipments ||--|{ shipment_items : "has many"
    shipments ||--|{ tracking_events : "has many (append-only)"
    shipments ||--o{ shipment_exceptions : "has many"
    locations |o--o{ tracking_events : "optional"
    users ||--o{ notifications : "receives"
    shipments |o--o{ notifications : "about"
    users |o--o{ audit_logs : "actor"

    users { bigint id PK
            string email UK
            string role "admin|operator|customer" }
    carriers { bigint id PK
               string name UK
               string code UK }
    locations { bigint id PK
                decimal latitude
                decimal longitude }
    shipments { bigint id PK
                string shipment_number UK
                string status
                bool is_delayed
                decimal delay_hours
                datetime deleted_at "soft delete" }
    shipment_items { bigint id PK
                     bigint shipment_id FK }
    tracking_events { bigint id PK
                      bigint shipment_id FK
                      string event_type
                      datetime occurred_at
                      bool is_exception }
    shipment_exceptions { bigint id PK
                          string type
                          string severity
                          string status
                          datetime detected_at
                          datetime resolved_at }
    notifications { bigint id PK
                    bigint user_id FK
                    timestamp read_at }
    audit_logs { bigint id PK
                 string action
                 string entity_type
                 json old_values
                 json new_values }
```

## Design decisions

| Decision | Reason |
|---|---|
| `shipments.customer_id -> users` (N:1) | A customer owns many shipments; ownership drives authorization (customers only see their own). |
| `shipments` has two FKs to `locations` (origin/destination) | A location can be origin of many shipments and destination of many others. |
| `tracking_events` is **hasMany** and append-only | Tracking is historical: new status never overwrites old events; timeline = ordered events. |
| `shipment_items` hasMany, cascade delete | Item lines have no meaning without their shipment. |
| Soft delete only on `shipments` | Audit/history must survive deletion; other tables are reference/log data. |
| `ON DELETE RESTRICT` for customer/carrier/locations | Prevents orphaning shipments; the UI blocks deletion and suggests deactivating. |
| Indexes: `status`, `is_delayed`, `expected_arrival`, `(carrier_id,status)`, `(shipment_id,occurred_at)` | Dashboard counts, filters, sorting and timeline queries. |
| Unique: `shipment_number`, `carriers.name/code`, `users.email`, `locations(name,city,country)` | Business identifiers must not duplicate. |
| `status`/`type`/`severity` stored as strings, validated by PHP backed enums | Portable (MySQL + SQLite tests) and the state machine lives in code (`ShipmentStatus`). |
