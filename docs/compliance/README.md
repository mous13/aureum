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
| [breach-response.md](breach-response.md) | Draft | Needs a named owner before it is real |
| [sub-processors.md](sub-processors.md) | **Incomplete** | Needs the production provider names filled in |

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

**The ISMS document set** — scope statement, risk register, information security
policy, Statement of Applicability. These are prerequisites for an ISO 27001
certification attempt and none of them exist yet. They describe how the
organisation is run, so they cannot be generated from the codebase.

## Standing warning

Everything here was drafted from what the code does. It describes the system
accurately as of the commit it was written at, and it is not legal advice.
Before any of it is shown to a customer or an auditor, someone with data
protection expertise should read it.
