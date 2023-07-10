Act on records matching a list of criteria.

The worker will play __tasks__, each task must specify the databox to act on 
and the action to do on selected records, e.g.:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <tasks>
        <!-- first task -->
        <task active="1" name="Go offline" action="update" databoxId="db_databox1">
            <from>
                ...
            </from>
            <to>
                ...
            </to>
        </task> 
        <!-- second task -->
        <task ...>
            ...
        </task>    
    </task>
</tasksettings>
```

A task can be marked as `active="0"` to be tested.

The `databoxId` to act on can be specified by ID or name.

The "Test the rules" button will display the select sql and the number or record selected for action.

`action` can be one of:

#### update

update action can move record to another collection and / or change status-bits.

#### delete

delete the records

#### trash

move record to the trash collection

## - list of criteria to select records (__from__ clauses)

__All__ criteria must match for the record to be selected.

Some criteria (eg. coll ids) can be a list, allowing to define sort of "or" clauses.

To set "or" criteria that can't be expressed with the rules syntax, one must define 
many tasks.

### select on type of record.

```xml
<type type="record" />
```

```xml
<type type="story" />
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

### select on text values.

```xml
<text field="author" compare="=" value="bob" />
```

```xml
<text field="author" compare="!=" value="joe" />
```

_warning:_ comparison is made using __alphabetic__ value.
Using `< > <= >=` compare operator
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

## - parameters of "update" action ("to" clauses)

### change collection ; change status-bits
```xml
<to>
    <!-- move to collection "Private" -->
    <coll id="Private"/>
    <!-- reset sb4, set sb5 -->
    <status maksk="10xxxx" />
</to>
```
