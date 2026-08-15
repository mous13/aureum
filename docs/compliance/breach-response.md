# Personal data breach response

## Obligation

As processor, Article 33(2) requires Citadel to notify the affected hotel
**without undue delay** after becoming aware of a breach. There is no 72-hour
grace period for processors — that clock belongs to the controller, and it
starts when you tell them. Delay in telling them eats their deadline.

A breach is not only an attacker. Accidental disclosure, loss of access, and
accidental destruction all count.

## Owner

**Named owner:** Oscar Ellis, Citadel Software Solutions.

**There is no deputy.** Oscar is the only person with production access, so if
he is unavailable — on holiday, ill, or simply asleep during a US-hours
incident — nothing in this procedure happens and the notification clock keeps
running for every affected hotel.

This is the single largest operational risk on the platform and it cannot be
fixed in code. Two partial mitigations worth having before a second hotel signs:

- A break-glass credential held somewhere trustworthy and sealed, so that
  someone else can at least take the platform offline to contain a breach.
- A written statement to hotels of the hours during which you can realistically
  respond, so their expectations match reality and they are not relying on
  cover that does not exist.

An auditor will ask about continuity. "One person, no cover" is a finding, but
an acknowledged and documented one is treated very differently from one
discovered during the audit.

## What counts

| Kind | Example in Aureum |
|---|---|
| Confidentiality | Guest transfer addresses exposed to another hotel or an outsider; database snapshot leaked; an account left active after offboarding. |
| Integrity | Records altered without authorisation. |
| Availability | Database lost with no working restore. Losing data permanently is a breach, not merely an outage. |

## Steps

### 1. Contain

Stop the exposure before investigating. Revoke credentials, take the instance
offline, or disable the affected module. A short outage is cheaper than
continued disclosure.

### 2. Establish the facts

Record, with times:

- When it started and when it was noticed.
- Which hotels are affected.
- Which categories of data, and roughly how many people.
- Whether it is contained.

The **access log** (Manager → Access Log) shows which employees opened the
guest-data modules. The per-record audit logs show what changed and who changed
it. Between them you can usually scope who saw what. Do this before anything is
deleted — cleaning up first destroys the evidence you need.

### 3. Notify the hotels

Without undue delay, and do not wait for a complete picture. A first message
saying what is known and that more will follow is correct; silence while you
investigate is not.

Tell them:

- What happened and when.
- Categories and approximate number of guests affected.
- Likely consequences.
- What has been done to contain it, and what they should do.
- A named contact.

The hotel then decides whether to notify the ICO and the affected guests. That
is the controller's call, not yours — but give them what they need to make it
quickly.

### 4. Record it

Every breach is logged, including ones not notified onward and the reasoning for
that decision. Auditors ask to see this register, and an empty one from a live
platform reads as "not looking" rather than "nothing happened".

Keep: dates, facts, decisions and who made them, what was communicated, and what
changed afterwards.

### 5. Fix the cause

Address the root cause, not the instance. Where it is a code defect, add a test
that fails without the fix — the tenant boundary tests in `tests/Tests/Unit/`
exist for exactly this reason.

## Breach register

| Date | Summary | Hotels affected | Notified | Outcome |
|---|---|---|---|---|
| _(none recorded)_ | | | | |
