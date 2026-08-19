# Compliance documentation

Aureum stores personal data about hotel guests — people who are not its users and
who never agreed to anything with Citadel. That makes the paperwork in this
directory part of the product, not an afterthought.

## Who is who

Hotels decide what happens to guest data, so each hotel is the **controller**.
Citadel stores and processes it on their instruction, so Citadel is the
**processor**. This split determines most of what follows: as processor you do
not need your own lawful basis for guest data, but you do need a written
contract with every hotel and you must be able to act on their instructions,
including deletion.

For employee accounts Citadel is the controller directly, because Citadel
creates the accounts and holds the credentials.

## What is here

| Document | Status | Notes |
|---|---|---|
| [record-of-processing.md](record-of-processing.md) | Draft, derived from the schema | Required of processors by Art. 30(2) |
| [retention-schedule.md](retention-schedule.md) | Draft | Backed by working code |
| [breach-response.md](breach-response.md) | Draft | Owner named; no deputy exists |
| [sub-processors.md](sub-processors.md) | Draft | Mailgun region still to confirm |
| [international-transfers.md](international-transfers.md) | **Action required** | Hosting is in the US with no transfer mechanism in place |
| [isms/](isms/) | Draft | Scope, security policy, risk register, Statement of Applicability |
| [pages/](pages/) | Ready to paste | Public page mockups: privacy notice, sub-processors, cookies, guest notice template |

## Read this first

Hosting is AWS Lightsail in **N. Virginia**. If your hotels or their guests are
in the UK or EEA, guest data is crossing to the US with nothing documented to
make that lawful. Nothing else in this directory matters as much.
See [international-transfers.md](international-transfers.md) — the recommended
fix is to move the instance to London, which also resolves the
encryption-at-rest gap in the same migration.

## What is not here, and why

**The Data Processing Agreement.** This is a contract, and drafting contracts is
legal work rather than engineering work. The ICO publishes a template covering
the Art. 28(3) requirements, which is the sensible starting point. Do not sign
one until you have read [retention-schedule.md](retention-schedule.md) and are
satisfied the product can actually honour the deletion obligation it commits you
to — it can now, but only once retention periods are configured per hotel.

**A DPIA for the fines module.** Fines record identified guests together with
allegations about their conduct, across every hotel on the platform. That is the
shape of processing Art. 35 is aimed at. Whether one is formally required is a
judgement for a data protection practitioner, not something to assume away.

**A signed DPA, a DPIA, and certification itself.** The ISMS document set now
exists in [isms/](isms/) — scope statement, information security policy, risk
register and Statement of Applicability — drafted honestly for a one-person
operation. Certification additionally requires the management-system routine
(reviews, internal audit) to have actually run, which only time provides.

## Standing warning

Everything here was drafted from what the code does. It describes the system
accurately as of the commit it was written at, and it is not legal advice.
Before any of it is shown to a customer or an auditor, someone with data
protection expertise should read it.
