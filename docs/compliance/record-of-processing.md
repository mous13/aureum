# Record of Processing Activities

Article 30(2) requires processors to maintain a record of the processing carried
out on behalf of each controller. This is that record for the Aureum platform.

**Processor:** Citadel Software Solutions
**Controllers:** each hotel using Aureum
**Last reviewed:** 15 August 2026

## Categories of data subject

| Subject | Relationship |
|---|---|
| Hotel guests | Data recorded about them by hotel staff. They have no account and no direct relationship with Citadel. |
| Hotel employees | Hold accounts, create and read the records above. |

## Processing activities

### Package handling — `aureum_packages`

- **Purpose:** track parcels held at reception until collected.
- **Personal data:** recipient name, free-text collection note.
- **Source:** entered by hotel staff.
- **Retention:** per-hotel, configurable; guest details cleared on expiry.
- **Log:** `aureum_logs_packages` holds before/after copies of the above, cleared with the record.

### Guest transfers — `aureum_transfers`

- **Purpose:** arrange and record airport and onward travel.
- **Personal data:** guest name, phone number, email address, pickup address, dropoff address, driver name, free-text notes.
- **Source:** entered by hotel staff, usually from a guest request.
- **Retention:** per-hotel, configurable; measured from the transfer date.
- **Note:** the widest set of identifiers in the platform, including home addresses.

### Fines — `aureum_fines`

- **Purpose:** record charges raised against a guest for damage or breach of house rules.
- **Personal data:** guest name, email address, free-text note, fine reference and status.
- **Source:** entered by hotel staff.
- **Retention:** per-hotel, configurable. Consider dispute windows before setting a short period.
- **Note:** this is a record of alleged misconduct by an identified person. It carries a higher risk profile than the other modules and is the reason a DPIA question is open.

### Lost property — `aureum_lost_property`

- **Purpose:** log found items and reunite them with their owner.
- **Personal data:** guest name, contact details, free-text note.
- **Retention:** per-hotel, configurable.

### Room and floor commentary — `aureum_room_comments`, `aureum_floor_feature_comments`

- **Purpose:** operational notes attached to a room or floor feature.
- **Personal data:** the employee author; potentially guest information typed into the body.
- **Retention:** none defined. **Open gap** — these are not covered by the retention command.

### Employee accounts — `aureum_employees`, `user`

- **Purpose:** authentication, authorisation, and attribution of actions.
- **Personal data:** name, username, email, password hash, timezone, avatar, last activity.
- **Retention:** the account is deleted at offboarding. The employee row and name are kept so past actions remain attributable.
- **Controller:** Citadel, not the hotel.

### Access log — `aureum_logs_access`

- **Purpose:** record which employee opened the modules holding guest contact details, so a hotel can answer who saw a guest's data and an incident can be scoped.
- **Personal data:** employee name and identifier, route, timestamp.
- **Retention:** 12 months.

## Recipients

Guest data is not disclosed to any third party. The only external parties who
process it incidentally are the infrastructure and mail providers recorded in
[sub-processors.md](sub-processors.md).

## International transfers

Determined by where the AWS Lightsail instance is hosted. **Confirm the region.**
If it sits outside the UK/EEA, a transfer mechanism and assessment are required
before the arrangement is lawful.

## Security measures

- Role-based access control per module, enforced server-side on every route.
- A tenant boundary that prevents any employee reaching another hotel's records.
- Passwords hashed with Symfony's managed hasher; login throttling enabled.
- CSRF protection on forms and on every destructive action.
- Attributed audit log of every change, plus an access log of reads on guest modules.
- Automated anonymisation of guest details past their retention period.
- Baseline security response headers; session idle timeout.

**Not yet in place:** multi-factor authentication; encryption at rest
unconfirmed; backups weekly and never restore-tested.
