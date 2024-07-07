#Translator

Translator is a console command that uses the thesaurus to translate terms from one field (source), to one or many fields (destinations).

It will act on records matching conditions like "from this collection" or
"if this status-bit is 1".

Translator play __jobs__ one after one, each __job__ can define his own settings.
Jobs and settings are declared in a configuration file (yml):

```yaml

translator:
  jobs:
    keywords_EN_to_FR_DE:
      active: true
      databox: my_databox
      ...
    country_EN_to_FR_DE:
      active: false
      ...
```

##Job settings:

- `active` : (mandatory) boolean to activate the job.
- `databox`: (mandatory) The databox name|id to act on.
- `if_collection`: (optional) The unique collection name|id to act on; Default if not set: All collections.
- `if_status`: (optional) Act only on records matching this status-bits mask; Format 01x10xxxx; Default: All records.
- `source_field`: (mandatory) The name of the source field containing terms to be translated.
- `source_lng`: (optional) The language of the source terms to translate. If set, only terms matching this lng will be searched into thesaurus. Default if not set: Search term without language criteria.
- `destination_fields` (mandatory) A __list__ of destinations using format `{lng}:{field name}`;
Each translated term (from thesaurus) will be directed to the matching field, depending on his lng (see examples).
- `cleanup_source`: (optional) Whether to remove or keep the source term, depending on it was succesfully translated or not.
    - `never`: keep the term (default).
    - `if-translated`: remove if fully translated (all destination lngs).
    - `always`: remove the term even it was not translated.
- `cleanup_destination`: (optional) Empty the destination(s) field(s) before translation (default `false`)
- `set_collection`: (optional) collection where to move the record after translation.
- `set_status`: (optional) status-bit mask to apply on record after translation.

##Important:

#### After playing job(s), no more record must match the selection conditions `if_collection`, `if_status`.

- Because a job will act on __all__ records matching the `if_collection` and `if_status` conditions, 
one __should__ change the collection or sb after translation (`set_colllection` and `set_status` settings).
    

- Because each job declares his own conditions, playing multiple jobs must implement a _workflow_ mechanism:
    - job 1 selects records matching conditions A (coll/sb) __must__ change collection and/or status to match conditions (B) of job 2.
    - job 2 selects records matching conditions B and __must__ set new final values that matches neither A or B.


- Because jobs are played one after one, in case of many jobs acting on same records, workflow can be simplified:
    - __first__ job 1 selects records matching "work-on" conditions, and does not change anything after translation.
    - job 2 selects using the same conditions and does not change conditions either.
    - __last__ job 3 selects using the same conditions, and is responsible to change collection and/or status when done.

Those rules prevent the job(s) to run multiple times on the same records. Of course care must be taken if one part of a workflow is de-activated.

#### Cleanup with multiple jobs.

- Because job n+1 is played after job n is fully completed, care must be taken when using `cleanup` options:
   - If acting on same source, `cleanup_source: always` must only be applied on __last__ job, else job 1 will remove every term that job 2 should work on.
  (This case might not happen since - thanks to multiple destinations - there is no reason to act on same source twice).

   - Same care with multiples jobs writing on same destination(s): `cleanup_destination: true` should be set only on __first__ job, else job 2 will erase what job 1 has done.



##Example 1:
### translate new records (having default sb=0).
```yaml
translator:
  jobs:
    example:
      active: true
      databox: my_databox
      # condition: act on new records having "translated" sb[4]=0
      if_status: 0xxxx
      # original keywords are expected to be EN
      source_field: KeywordsEN
      source_lng: en
      # translate to 2 separate fields
      destination_fields:
        - fr:keywordsFR
        - de:keywordsDE
      # keep original EN keywords
      cleanup_source: never
      # remove existing terms on destinations before translating
      cleanup_destination: true
      # end: set "translated" sb to 1
      set_status: 1xxxx
```

##Example 2:
### manually select records to translate by setting sb[4].
```yaml
translator:
  jobs:
    example:
      # ...
      # condition: act on records having "to translate" sb[4]=1
      if_status: x1xxxx
      # end: mark the record as "translated"
      set_status: 10xxxx
```

##Example 3:
### translate new records from temporary collection.
```yaml
translator:
  jobs:
    example:
      # ...
      if_collection: 'upload'
      set_collection: 'online'
```

##Example 4:
### add translations to the same field

__Trick__:
If one cleans the destination field - the __same as the source__ -, the original source will be deleted.
If the intent is to preserve the original term (adding translations), it must be added again.

The program will detect that the same term is to be deleted then added, and will preserve the original one.

```yaml
translator:
  jobs:
    example:
      # ...
      source_field: Keywords
      source_lng: en
      # since source=destination, source will be cleaned of all not-translatable terms...
      cleanup_destination: true
      destination_fields:
        # ... this is why one must re-add the EN "translated" term (same as source)
        - en:Keywords
        - fr:Keywords
        - de:Keywords
```

##Example 4-bis:
### removing terms that are not in the thesaurus

```yaml
translator:
  jobs:
    example:
      # ...
      source_field: Keywords
      source_lng: en
      cleanup_source: always
      destination_fields:
        - en:Keywords
```

##Example 5:
### merge many sources to one "tote bag" 
```yaml
translator:
  jobs:
    keywords:
      active: true
      databox: my_databox
      # manually start condition: set sb[4]
      if_status: xxx1xxxx
      # original keywords are expected to be EN
      source_field: keywords
      source_lng: en
      # translate to a common field
      destination_fields:
        - fr:motscles
      # each job can clean his own distinct source
      cleanup_source: always
      # first job cleanups destination
      cleanup_destination: true
      # end: set ready for next job
      set_status: 0010xxxx
    country:
      active: true
      databox: my_databox
      # condition: set by previous job
      if_status: 0010xxxx
      # original country is expected to be EN
      source_field: country
      source_lng: en
      # translate to the same destination
      destination_fields:
        - fr:motscles
      # each job can clean his own distinct source
      cleanup_source: always
      # do NOT cleanup destination, first job did it
      cleanup_destination: false
      # end: set ready for next job
      set_status: 0100xxxx
    city:
      active: true
      databox: my_databox
      # condition: set by previous job
      if_status: 0010xxxx
      # original city is expected to be EN
      source_field: city
      source_lng: en
      # translate to the same field
      destination_fields:
        - fr:motscles
      # each job can clean his own distinct source
      cleanup_source: always
      # do NOT cleanup destination, first job did it
      cleanup_destination: false
      # end: set to "translated"
      set_status: 1000xxxx
```
