# Statement of Applicability

Against ISO/IEC 27001:2022 Annex A. Grouped by theme; controls not listed
individually are aggregated where a single justification covers them honestly.
"Implemented" means implemented for a one-person operation — several controls
that assume an organisation (segregation of duties, HR screening of employees)
are recorded as not applicable with the reason, which an auditor will test.

## A.5 Organisational controls

| Control | Applicable | Position |
|---|---|---|
| 5.1 Policies for information security | Yes | `information-security-policy.md`; reviewed annually |
| 5.2 Roles and responsibilities | Yes | All roles held by the operator; recorded in `scope.md` and risk #2 |
| 5.3 Segregation of duties | Yes | Cannot be achieved with one person; compensating controls are version control and audit logs; risk #2 |
| 5.7 Threat intelligence | Yes | Upstream security announcements (Symfony, forumify, Ubuntu, Docker) |
| 5.9 Inventory of assets | Yes | `scope.md` interfaces table plus the sub-processor register |
| 5.10–5.14 Acceptable use, returns, classification, transfer | Yes | Single operator; guest data is the highest class and never leaves the platform; email carries no guest data |
| 5.15–5.18 Access control, identity, authentication, access rights | Yes | Per-hotel tenancy enforced in code; RBAC per module; joiner/leaver flows implemented in the product |
| 5.19–5.23 Supplier relationships and cloud | Yes | Sub-processor register; AWS DPA to accept (risk #1); Mailgun region to confirm (risk #11) |
| 5.24–5.28 Incident management | Yes | `breach-response.md` |
| 5.29–5.30 Continuity / ICT readiness | Yes | Backups + rehearsed restore (risks #3, #8) |
| 5.31–5.34 Legal, IP, records, privacy/PII | Yes | GDPR document set in `docs/compliance/` |
| 5.35–5.36 Independent review, compliance with policies | Yes | Annual self-review recorded in the risk register review log; no independent function exists — noted for any certification attempt |
| 5.4–5.6, 5.8 (management direction, authorities, project security) | Yes | Trivially satisfied at this scale; authorities contact list lives in `breach-response.md` |

## A.6 People controls

| Control | Applicable | Position |
|---|---|---|
| 6.1–6.2 Screening, terms of employment | No | No employees. Revisit before anyone is hired |
| 6.3 Awareness and training | Yes | Operator maintains competence; hotel-staff guidance is embedded in the product (field help texts) |
| 6.4 Disciplinary process | No | No employees |
| 6.5–6.6 Post-termination, confidentiality (NDA) | Yes | Confidentiality obligations go in hotel contracts |
| 6.7 Remote working | Yes | Development is remote by nature; FDE on the dev machine (risk #10) |
| 6.8 Event reporting | Yes | Hotels can report to the operator; contact on the platform |

## A.7 Physical controls

| Control | Applicable | Position |
|---|---|---|
| 7.1–7.14 | Mostly No | No premises; physical security of servers is inherited from AWS (SOC 2/ISO 27001 certified). Applicable remainder: the development machine — FDE and screen lock (risk #10) |

## A.8 Technological controls

| Control | Applicable | Position |
|---|---|---|
| 8.1 User endpoint devices | Yes | Dev machine hardening (risk #10) |
| 8.2 Privileged access rights | Yes | Super admin held by operator only; hotel admin scoped per hotel |
| 8.3–8.4 Information/source-code access restriction | Yes | Tenancy enforcement; private repository |
| 8.5 Secure authentication | Yes | Hashed passwords, one-time initial credentials, forced first-login change, login throttling. 2FA is a roadmap item (risk #6) |
| 8.6 Capacity management | Yes | Single instance; monitored informally — adequate at current scale |
| 8.7 Malware protection | Yes | Server runs no user-supplied executables; images/uploads stored, not executed |
| 8.8 Technical vulnerability management | Yes | Dependency and OS patching routine (risk #9) |
| 8.9 Configuration management | Yes | Infrastructure config documented; application config in version control |
| 8.10–8.11 Deletion and data masking | Yes | Retention anonymisation implemented per module; deletion controls for erasure requests |
| 8.12 Data leakage prevention | Yes | At this scale: no guest data in email, logs scrubbed on anonymisation |
| 8.13 Backups | Yes | Risks #3/#8 — being remediated with the EU migration |
| 8.14 Redundancy | No | Single instance accepted; hotels tolerate short outages; revisit with growth |
| 8.15–8.16 Logging and monitoring | Yes | Access log for guest-data modules, per-record audit logs, 12-month retention |
| 8.17 Clock synchronisation | Yes | NTP on the host |
| 8.18 Privileged utilities | Yes | Shell access restricted to the operator |
| 8.19 Software installation | Yes | Only through the deployment routine |
| 8.20–8.23 Network security, web filtering | Yes | Lightsail firewall minimal ports; TLS everywhere; no outbound browsing from the server |
| 8.24 Cryptography | Yes | TLS in transit; at-rest encryption lands with the migration (risk #3) |
| 8.25–8.31 Secure development lifecycle | Yes | Version control, static analysis, coding standards, tests, separation of test data from production |
| 8.32 Change management | Yes | Changes ship through git with quality gates |
| 8.33 Test information | Yes | Tests use fixtures, never production guest data |
| 8.34 Audit testing protection | Yes | Trivial at this scale |
