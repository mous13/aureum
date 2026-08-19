# International transfers

## The position

Aureum runs on AWS Lightsail in **N. Virginia (us-east-1)**. Guest names, email
addresses, phone numbers and home addresses are therefore stored in the United
States.

If the hotels using Aureum are in the UK or EEA, or their guests are, this is a
**restricted transfer** under Chapter V of UK/EU GDPR. It is not unlawful in
itself — plenty of UK businesses host in the US — but it is only lawful with a
documented mechanism behind it, and there is none today.

If every hotel and every guest is outside the UK/EEA, this section does not
apply. Confirm which before spending time on it.

## Who is transferring what

The hotel is the controller and Citadel is its processor, so Citadel is the one
making the onward transfer to AWS as a sub-processor. That means two obligations
land on Citadel rather than the hotel:

1. A transfer mechanism covering Citadel → AWS.
2. Telling the hotel that their guests' data goes to the US, so that the hotel
   can describe it in its own privacy notice. A hotel that discovers this from
   someone else has a problem with its own regulator, and will take it out on
   the vendor that did not mention it.

## Options

### Move the region

The cleanest answer. Hosting in **eu-west-2 (London)** removes the transfer
question entirely rather than papering over it, and removes a recurring item
from every future security questionnaire.

The cost is a migration: new instance, restore the database, repoint DNS. For a
platform with one live hotel this is as cheap as it will ever be. It gets more
expensive with every hotel added.

Doing this alongside a move to a **Lightsail managed database** would also
resolve the encryption-at-rest question in the same piece of work, since managed
databases are encrypted at rest whereas a database you run yourself in Docker on
the instance disk is your own responsibility.

**This is the recommended option.** One migration now closes two findings
permanently.

### Keep Virginia and document it

Legitimate, but more paperwork, and it recurs.

1. **Accept the AWS Data Processing Addendum** through the AWS console if you
   have not. It incorporates the Standard Contractual Clauses. This is the
   contractual backbone and takes minutes.
2. **Check whether AWS's Data Privacy Framework certification covers you.** AWS
   self-certifies under the EU–US Data Privacy Framework and its UK extension.
   Verify current status on the official DPF list rather than taking it on
   trust — certifications lapse, and the framework itself has been challenged
   before.
3. **Complete a Transfer Risk Assessment.** The ICO publishes a TRA tool. It
   asks whether the destination country's laws would undermine the protections
   you are relying on. This is the part people skip and auditors ask for.
4. **Disclose it to every hotel**, in the DPA and the sub-processor register.

## Mailgun

Mailgun offers US and EU infrastructure, chosen per account. Confirm which yours
uses. If US, the same analysis applies — but only to employee names and email
addresses, never guest data, so the exposure is far narrower and an EU-region
account is a simple switch if you want it gone.

## Status

| Item | State |
|---|---|
| Hosting region | US (N. Virginia) — confirmed |
| AWS DPA accepted | **Unknown — check the AWS console** |
| DPF certification verified | **Not done** |
| Transfer Risk Assessment | **Not done** |
| Disclosed to hotels | **Not done** |
| Mailgun region | **Unknown** |
