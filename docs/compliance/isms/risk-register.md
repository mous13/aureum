# Risk register

Scores are likelihood × impact, each 1–5. Review at least quarterly and after
any incident or infrastructure change. Risks below 6 are accepted without
further action.

| # | Risk | L | I | Score | Treatment | Status |
|---|---|---|---|---|---|---|
| 1 | Guest data hosted in the US with no transfer mechanism; a hotel's regulator or DPO challenges it | 4 | 4 | 16 | Migrate the instance to an EU region (planned); accept the AWS DPA in the console; record both in `international-transfers.md` | Open — migration planned |
| 2 | Single operator: illness or unavailability means nobody can respond to an incident, restore a backup, or honour a deletion request | 3 | 5 | 15 | Document runbooks so a competent outsider could operate from them; keep credentials in a password manager with a recovery arrangement; long-term, a second pair of hands | Open |
| 3 | MySQL runs in Docker on the instance: no managed encryption at rest, no managed backups | 3 | 4 | 12 | Enable disk/snapshot encryption on migration; schedule automated dumps to encrypted storage; rehearse restore | Open — fold into EU migration |
| 4 | Retention/anonymisation jobs exist but are not scheduled on the server, so retention promises silently fail | 3 | 4 | 12 | Add cron entries for `aureum:retention:run`, access-log pruning and `aureum:purge-orphaned-accounts`; check output monthly | Open — deployment task |
| 5 | Lightsail instance compromise via unpatched OS or exposed service | 2 | 5 | 10 | Minimise exposed ports (80/443 + SSH restricted), unattended security upgrades, keep Docker images current | Open — verify firewall state |
| 6 | A hotel admin account is phished; attacker reads that hotel's guest data | 3 | 3 | 9 | Login throttling and access logging exist; strong generated initial passwords; consider 2FA for hotel admins as a roadmap item | Partially treated |
| 7 | Staff record health details or allegations in free-text fields, creating special-category data the platform is not designed to hold | 3 | 3 | 9 | Fields capped at 255 chars with explicit instructions; periodic spot check by hotels is a contract point | Treated — monitor |
| 8 | Backup restore has never been rehearsed; a restore fails when actually needed | 3 | 4 | 12 | Rehearse restore twice a year; record date and outcome here | Open |
| 9 | Dependency vulnerability in forumify/Symfony reaches production before patching | 2 | 4 | 8 | Watch upstream security releases; composer audit in the update routine | Treated — routine |
| 10 | Laptop with repository clone and credentials is lost or stolen | 2 | 4 | 8 | Full-disk encryption on the development machine; no production data locally; credential rotation on loss | Verify FDE enabled |
| 11 | Mailgun processes staff emails in the US while customers assume EU handling | 3 | 2 | 6 | Confirm/create an EU sending domain; record in the sub-processor register | Open |
| 12 | The one welcome email containing a temporary password is intercepted | 1 | 3 | 3 | Password is single-use and must be changed at first sign-in; accepted | Accepted |

## Review log

| Date | Reviewer | Notes |
|---|---|---|
| [DATE] | [NAME] | Initial version from the platform audit |
