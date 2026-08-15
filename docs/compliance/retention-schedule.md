# Retention schedule

## The rule that applies

GDPR sets **no maximum retention period**. Article 5(1)(e) requires only that
personal data is kept no longer than necessary for the purpose it was collected
for, and leaves the controller to define and justify that period.

So there is no deadline being breached by holding data. The failure mode is
having no defined period at all — which was the position before this schedule
existed.

Other law pulls the other way by setting *minimums*: financial records in the UK
are generally kept for six years. Where a fine feeds an invoice, the accounting
obligation may outlast the operational one.

## How it works in Aureum

Each hotel sets its own period per module, because what counts as necessary
genuinely differs — one hotel audits held packages at six months, another at
three, and both are defensible.

Configure at **Manager → Data Retention**. Leaving a module blank means guest
details are kept indefinitely, which is a deliberate choice a hotel has to make
rather than a default that quietly applies.

### What happens on expiry

Records are **anonymised, not deleted**. Guest name, contact details, addresses
and free-text notes are cleared; the record, its dates and its status survive.

This keeps the operational history a hotel needs — how many transfers were
handled last year — while removing the personal data. What remains is no longer
personal data, which is what satisfies storage limitation.

The audit log's `changes` payload holds before-and-after copies of the same
fields, so it is cleared at the same time. Without that the data would survive
the anonymisation in the log table.

### Running it

```bash
php bin/console aureum:retention:run --dry-run
```

Reports what would be affected, changing nothing. Drop `--dry-run` to apply.
Intended to run nightly from cron:

```
0 3 * * * cd /path/to/aureum-app && php bin/console aureum:retention:run
```

Anonymisation cannot be undone. Run the dry run first the first time.

## Suggested starting periods

These are starting points for a conversation with each hotel, not
recommendations to apply blindly. The hotel is the controller and the decision
is theirs.

| Module | Suggested | Reasoning |
|---|---|---|
| Packages | 3–6 months | Operationally dead once collected. Hotels commonly audit and dispose on this cycle. |
| Transfers | 6–12 months | Holds home addresses, the most sensitive set. Billing queries are the main reason to keep it. |
| Lost property | 3–6 months | Tied to how long unclaimed items are physically held. |
| Fines | 12–24 months | Longest, because disputes and chargebacks surface late. Check against the hotel's accounting retention. |

## Fixed periods

| Data | Period | Why |
|---|---|---|
| Access log | 12 months | Long enough to investigate an incident and answer a guest asking who saw their details. Not configurable, because it is Citadel's own accountability record. |
| Employee accounts | Deleted at offboarding | The employee row and name persist so past actions stay attributable. |

## Known gap

**Room and floor feature comments are not covered.** They are operational
commentary — "radiator needs replacing" — and blanket expiry would destroy
maintenance history that has nothing to do with guests. But nothing stops a
staff member typing guest information into one.

This is an open decision rather than an oversight: either accept the risk with
guidance at the point of entry, or agree a long period after which comments are
cleared. It needs a call before the platform takes on a second hotel.
