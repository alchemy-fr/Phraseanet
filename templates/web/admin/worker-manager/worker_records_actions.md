Act on records matching a list of criteria.

# Changelog / bc break:

`from` group is renamed `if`

`to` group is renamed `then`

`trash` action is removed ; use `update` with `then` `<coll id="_TRASH_" />`

`type` clause (used for record|story) is renamed `record_type`

# Doc:

The worker will play __tasks__, each task must specify the databox to act on 
and the action to do on selected records, e.g.:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings version="2">
    <tasks>
        <!-- first task -->
        <task active="1" name="Go offline" action="update" databoxId="db_databox1">
            <if>
                ...
            </if>
            <then>
                ...
            </then>
        </task> 
        <!-- second task -->
        <task ...>
            ...
        </task>    
    </task>
</tasksettings>
```

A task marked as `active="0"` is ignored.

A task marked as `dry="1"` is executed, but the actions on record are not executed.
This allows to check sql and actions (log) whithout altering data.

The databox to act on is `databoxId` can be specified by __Id__ or __name__.

The "Test the rules" button will display the select sql and the number or record selected for action.

`action` can be one of:

#### update

update action can move record to another collection and / or change status-bits.

#### delete

delete the records

## `if` clauses to select records to act on:

__All__ clauses must match for the record to be selected.

Some clauses (eg. coll ids) can be a list, allowing to define sort of "or" clauses.

To set "or" clauses that can't be expressed with the rules syntax, one must define 
many tasks.

### select on type of record.

```xml
<record_type type="record" />
```

```xml
<record_type type="story" />
```

### select on collection
`id` is a list of collection id ("base_id" API side) or collection name.

```xml
<!-- act on records of collection id=1 or name=Online -->
<coll compare="=" id="1,Online" />  
```

```xml
<!-- act on records of any collection except name=Offline or name=_TRASH_-->
<coll compare="!=" id="Offline,_TRASH_" />
```  
_nb:_ Since a record belongs to only one collection, specifiying many `coll` clauses has no sense.

### select on set / unset field.

```xml
<is_set field="author" />
```

```xml
<is_unset field="author" />
```

### select on text values.

```xml
<text field="author" compare="=" value="bob" />
```

```xml
<text field="author" compare="!=" value="joe" />
```

_warning:_ comparison is made using __alphabetic__ value.
Using `< > <= >=` compare operators
__is possible__ but may have unexpected result, depending on case, accents, signs etc.

### select on numeric values.

```xml
<number field="note" compare=">=" value="10" />
```

possible compare oerators are `= != < > <= >=`

pseudo-field `#filesize` can be used to test document file size.
```xml
<!-- select records having document file size >= 10Mo -->
<number field="#filesize" compare=">=" value="10485760" />
```

### select on date values

```xml
<date direction="after" field="expire" delta="-2 days" />
<!-- means "now is after value of field expire minus 2 days" -->
```

`direction`: "after" or "before"

`delta`: +/- N ("hour" or "day" or "week" or "month" or "year")

pseudo-fields `#credate` and `#moddate` can be used to test creation date
and last modification date of records.


### select on status-bits

```xml
<!-- select records having sb4=0 and sb6=1 -->
<status maksk="1x0xxxx" />
```

## `then` actions (for task with "update" action)

### change collection ; change status-bits ; set a field value
```xml
<then>
    <!-- move to collection "Private" -->
    <coll id="Private"/>
    <!-- reset sb4, set sb5 -->
    <status maksk="10xxxx" />
    <!-- set the field Author -->
    <set_field field="Author" value="Bob" />
</then>
```

### set a field to a computed value

The `compute_date` parameters are the same as `date` clause. The result is then referenced 
by the `computed` reference.

The computed value can then be used as a value for a `set_field` action.

```xml
<then>
    <compute_date direction="after" field="#credate" delta="1 year" computed="exp" />
    <set_field field="ExpireDate" value="$exp" />
</then>
```

_nb_: For now this only allow to compute from / to a __datetime__ value. Computing from a "non-date" value
has unpredictable result.



