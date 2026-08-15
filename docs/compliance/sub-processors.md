# Sub-processor register

## Why this matters

Article 28(2) means Citadel may not engage another processor without the
hotel's authorisation, and Art. 28(4) means Citadel remains fully liable for what
those sub-processors do. In practice every hotel's compliance team will ask for
this list, and "we host on AWS" is not a sufficient answer — they want the
region, the purpose, and what data reaches it.

Anything that can see guest data belongs here, including services that only see
it incidentally, such as error tracking that captures request payloads.

## Register

**Status: incomplete.** The entries below are derived from configuration in the
repository. The ones marked _to confirm_ need the production values.

| Sub-processor | Purpose | Data reaching it | Location | Status |
|---|---|---|---|---|
| Amazon Web Services (Lightsail) | Application and database hosting | All platform data, including guest names, contact details and addresses | _to confirm — check the instance region_ | Active |
| _Mail provider_ | Transactional email: password resets and account verification | Employee names and email addresses. No guest data. | _to confirm_ | Active |

### Notes on each

**AWS Lightsail.** Region determines whether any international transfer is
happening. If the instance is outside the UK/EEA, an assessment and a transfer
mechanism are required before the arrangement is lawful. Confirm in the AWS
console and record the answer here.

**Mail provider.** Configured through `MAILER_DSN` in the host application's
environment. Whichever service is behind it is a sub-processor and must be
named. Only employee data passes through it; guests are never emailed by
Aureum.

## Not currently used

No error tracking (Sentry or equivalent), analytics, CDN, or backup service
beyond AWS was found in the configuration. If any is added later it belongs in
this table **before** it goes live, and hotels must be told.

## Change process

1. Add the sub-processor to this register with purpose, data and location.
2. Notify every hotel before it starts processing, giving them a chance to object — the DPA should say how much notice.
3. Confirm the contract with the sub-processor imposes terms no weaker than your own DPA obligations.
