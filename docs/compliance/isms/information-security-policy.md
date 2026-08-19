# Information security policy

## Purpose

Aureum stores personal data about hotel guests who never chose to use it. The
purpose of this policy is to keep that data, and the hotels' operational data,
confidential, intact and available. It applies to everyone operating the
platform — presently one person — and is reviewed at least annually or after
any security incident.

## Access control

- Every user authenticates individually; there are no shared accounts.
- Hotel staff see only their own hotel's data, enforced in code for every
  hotel-owned record.
- Within a hotel, access follows roles: staff see only the modules their role
  grants, hotel admins manage their own hotel, and only they manage staff.
- Platform (super admin) access is held by the operator alone, protected by a
  strong unique password.
- Staff joiners receive a generated one-time password and must set their own at
  first sign-in. Leavers are offboarded the day they leave: their login is
  deleted while their name stays on past records for the audit trail.
- Access to guest-data modules is logged, and the log is kept for 12 months.

## Credentials and secrets

- Passwords are stored hashed by the framework; no credential is ever stored or
  transmitted in plain text by the platform.
- Infrastructure secrets (AWS, database, Mailgun, application secrets) live in
  environment configuration on the server and in a password manager — never in
  the repository.
- Any credential suspected of exposure is rotated immediately.

## Operations

- The platform runs on a hardened baseline: TLS for all traffic, security
  headers on every response, login throttling, CSRF protection on every
  state-changing request.
- Dependencies are kept current; security releases of the framework, platform
  and OS packages are applied promptly.
- Changes reach production only through version control, after static analysis,
  coding-standard checks and the test suite pass.
- Backups: database snapshots are taken on a schedule, held encrypted, and a
  restore is rehearsed at least twice a year and after any change to backup
  tooling. An untested backup is treated as no backup.
- Retention jobs (guest-record anonymisation, access-log pruning, orphaned
  account purging) run on a schedule and their output is checked.

## Data protection

- Guest data is processed only on hotel instructions; the GDPR documents in
  `docs/compliance/` are part of this policy.
- Data minimisation is enforced in the product: free-text fields are capped and
  carry instructions not to record health data or allegations.
- Retention is configured per hotel per module and enforced automatically.

## Incidents

- Anything unexpected touching guest data, credentials or availability is an
  incident. The response procedure is `docs/compliance/breach-response.md`,
  including the 72-hour assessment obligations toward hotels and regulators.

## Enforcement and exceptions

With a single operator, enforcement is self-discipline made auditable: the
controls above are implemented in code or in checked configuration wherever
possible, so drift is visible in version control. Exceptions to this policy are
recorded in the risk register with a reason and a review date.
