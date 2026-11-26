# REST API

Base URL: `/api` · JSON only · Auth: `Authorization: Bearer <token>` (Laravel Sanctum, 24h expiry).
A ready-to-import Postman collection is in `postman/CargoTrace.postman_collection.json`.

| Method | Endpoint | Roles | Description |
|---|---|---|---|
| POST | /register | public | Create **customer** account, returns token (201) |
| POST | /login | public | Returns `token` + `user` |
| POST | /logout | any | Revoke current token |
| GET | /user | any | Current user |
| GET | /dashboard | any | Dashboard metrics (scoped to the user) |
| GET | /shipments | any | List. Filters: `q,status,carrier_id,origin_id,destination_id,from,to,delayed`; `sort,dir,per_page,page` |
| POST | /shipments | admin, operator | Create (status = CREATED) |
| GET | /shipments/{id} | admin, operator, owner customer | Detail |
| PUT/PATCH | /shipments/{id} | admin, operator (non-terminal) | Update master data (status not editable) |
| DELETE | /shipments/{id} | admin | Soft delete |
| GET | /shipments/{id}/tracking | as detail | Timeline + `current_location` |
| POST | /shipments/{id}/tracking | admin, operator | Append event (`event_type`, `occurred_at`, `location_id`/`latitude`+`longitude`, `description`, `estimated_arrival`) |
| GET | /shipments/{id}/exceptions | as detail | List exceptions |
| POST | /shipments/{id}/exceptions | admin, operator | `type`, `severity`, `description` |
| PATCH | /exceptions/{id} | admin, operator | `status` (OPEN->INVESTIGATING->RESOLVED), `severity`, `resolution_notes` |
| GET/POST | /carriers | read: any, create: admin | |
| GET/POST | /locations | read: any, create: admin | |

Status codes: `200/201` success · `401` unauthenticated · `403` forbidden · `404` not found · `422` validation or invalid
status transition · `429` rate limited. Error body: `{"message": "...", "errors": {"field": ["..."]}}` (never a stack trace).

## Transport mode fields (shipments)
`transport_mode` = `sea` (default) | `air` | `land`. Mode fields: `vessel_name`, `container_number` (sea); `flight_number`, `airline` (air); `vehicle_number`, `driver_name` (land). Fields of other modes are discarded. Filter list with `?mode=air`.
Extra tracking event types: `DEPARTED_ORIGIN`, `ARRIVED_AT_HUB`, `CUSTOMS_CLEARANCE`, `OUT_FOR_DELIVERY` (in addition to status events and `LOCATION_UPDATE`, `DEPARTED_HUB`, `CUSTOMS_CLEARED`, `NOTE`).
