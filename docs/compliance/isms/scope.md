# ISMS scope statement

## The ISMS covers

The Aureum concierge platform: the application code, the AWS Lightsail instance
that runs it (application container and MySQL container), its backups and
snapshots, the domains and DNS that point at it, the Mailgun account that sends
its email, and the credentials that control all of the above.

## People

Citadel is currently operated by one person, who is the developer, the systems
administrator and the data protection contact. Every responsibility named in
these documents lands on that person. This is recorded as a risk (single point
of failure, no segregation of duties) rather than hidden behind role names that
do not exist.

## The ISMS excludes

- Hotel-side equipment and networks. Hotels access Aureum through a browser;
  their devices are their own responsibility, which the customer contract
  should state.
- The forumify platform's upstream development. Aureum consumes it as a
  dependency; the ISMS covers keeping it patched, not developing it.
- Development laptops are in scope only for what they hold: repository clones
  and credentials. No production guest data is held on them, and it must
  stay that way.

## Interfaces and dependencies

| Interface | Direction | What crosses it |
|---|---|---|
| Hotels (browser) | in/out | All operational and guest data, over HTTPS |
| AWS Lightsail | hosting | Everything |
| Mailgun | out | Staff account emails only |
| GitHub | out | Source code (no secrets, no data) |
