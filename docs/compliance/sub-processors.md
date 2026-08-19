# Sub-processor register

## Why this matters

Article 28(2) means Citadel may not engage another processor without the
hotel's authorisation, and Art. 28(4) means Citadel remains fully liable for what
those sub-processors do. In practice every hotel's compliance team will ask for
this list, and "we host on AWS" is not a sufficient answer — they want the
region, the purpose, and what data reaches it.

Anything that can see guest data belongs here, including services that only see
it incidentally, such as error tracking that captures request payloads.

**Last reviewed:** 15 August 2026

## Register

| Sub-processor | Purpose | Data reaching it | Location | Status |
|---|---|---|---|---|
| Amazon Web Services (Lightsail) | Application and database hosting | All platform data, including guest names, contact details and home addresses | **US — N. Virginia (us-east-1)** | Active |
| Mailgun | Transactional email: password resets and account verification | Employee names and email addresses. No guest data. | _to confirm — US or EU region_ | Active |

### Amazon Web Services

The instance runs in **N. Virginia**, so all guest personal data is stored in the
United States. MySQL runs in Docker on the instance itself rather than as a
Lightsail managed database.

Two consequences, both covered in detail in
[international-transfers.md](international-transfers.md) and the audit report:

1. This is a restricted transfer under UK/EU GDPR and needs a documented
   transfer mechanism.
2. Running the database on the instance means encryption at rest is not
   something AWS is providing for you at the managed-database level. Confirm
   what applies to your instance disk and snapshots.

You need the AWS Data Processing Addendum in place — it is accepted through the
AWS console and incorporates the Standard Contractual Clauses. Check whether
you have already accepted it; if not, that is the first thing to fix.

### Mailgun

Only employee data passes through it; guests are never emailed by Aureum, so
the risk is materially lower than the hosting transfer.

Mailgun operates both US and EU infrastructure and the choice is made per
account. **Confirm which region your account uses.** If it is US, the same
transfer analysis applies, though to a much narrower dataset. Mailgun publishes
a DPA — confirm it is accepted.

## Not currently used

No error tracking (Sentry or equivalent), analytics, CDN, or backup service
beyond AWS was found in the configuration. If any is added later it belongs in
this table **before** it goes live, and hotels must be told.

## Change process

1. Add the sub-processor to this register with purpose, data and location.
2. Notify every hotel before it starts processing, giving them a chance to object — the DPA should say how much notice.
3. Confirm the contract with the sub-processor imposes terms no weaker than your own DPA obligations.
