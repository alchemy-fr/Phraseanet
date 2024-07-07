# feedback_report

`bin/console feedback_report`

Reports closed (expired) feedback result (votes) on every record.

### CLI options

`--min_date=yyy-mm-dd` will only act on feedback sessions opened (basket creation) __from__ this date.
This allows to __not__ report "antique" feedbacks. 

`--report=(all | condensed)` report per record or per feedback.

`--dry` list actions but do not apply.

### Run
For every record of a recently expired feedback, results are computed (number of voters, number of "yes", etc.).

Results can be used in `actions` to compute the value to set for status-bit or field.
The value to set is computed using a __twig__ formula, allowing for e.g. to set a sb to check that 
every voter has voted on the record, if at least 1/3 of voters voted "yes", etc...

Multiple actions allow to act on different sb / fields, using different value-formulas.

Because a feedback can contain records from different databoxes with different structures, a `databoxes` filter 
can be specified for an action. This action will be played only if the current record belongs to one of those.

### Participants _vs_ voters

Only users who can vote are taken in account to compute the results.

### Multiple feedbacks

Because a record can be part of multiple feedbacks, only the __most recently closed__ feedback is used to
report users votes.

Every record will preserve the reported status of its __last__ feedback session, until a most recent 
feedback session expires.

If a feedback expiration date is extended (even after the previous expiration has passed), the report will 
be updated afert the expiration on the new delay.


### Configuration example

e.g. for a status-bit value:
```yaml
# config/configuration.yaml
...
feedback-report:
  enabled: true
  actions:
    action_unvoted:
      # if any participant has not voted, set the "incomplete" icon
      status_bit: 8
      value: '{% if vote.votes_unvoted > 0 %} 1 {% else %} 0 {% endif %}'

    action_red:
      # if _any_ vote is "no", set the red flag
      status_bit: 9
      value: '{% if vote.votes_no > 0 %} 1 {% else %} 0 {% endif %}'

    action_log_1:
      databoxes:
        # only those 2 databoxes have a dedicated field for textual history
        dbMyDatabox  # one can use db name
        12           # sbas_id
      metadata: 'Feedbacks_history'
      value: 'Vote initated on {{ vote.created }} by {{ initiator ? initiator.getEmail() : "?" }} expired {{ vote.expired }} : {{ vote.voters_count }} participants, {{ vote.votes_unvoted }} unvoted, {{ vote.votes_no }} "no", {{ vote.votes_yes}} "yes".'

    action_log_2:
      databoxes:
        # same report, but on another field
        34
        56
      metadata: 'Comment'
      value: 'Vote initated on {{ vote.created }} by {{ initiator ? initiator.getEmail() : "?" }} expired {{ vote.expired }} : {{ vote.voters_count }} participants, {{ vote.votes_unvoted }} unvoted, {{ vote.votes_no }} "no", {{ vote.votes_yes}} "yes".'

```

### twig context

To compute the `value` of a status-bit or field, the twig formula can use:
- `vote.votes_unvoted`: the number of voters that has not voted on this record
- `vote.votes_yes`: the number of voters that has voted yes
- `vote.votes_no`: the number of voters that has voted no
- `vote.voters_count`: the number of voters (sum of yes, no, unvoted)
- `vote.basket_id`
- `vote.sbas_id`
- `vote.record_id`
- `vote.created`: the creation date of feedback request
- `vote.expired`: the expiration date 
- `initiator`: the initiator (__user__ object) 
