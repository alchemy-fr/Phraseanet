# CHANGELOG

# CHANGELOG

## 4.1.7

### Update instructions : 

    - If you come from a 4.1.6 version : Nothing special except for the primary datastore backup before performing an update.

    - If you come from an earlier version : you need to manually perform a "drop", "create", "populate" of elasticsearch index.

### Version summary :
  - General improvement for the report and introducing bin/report for report export automation
  - Authentication improvement in supporting multiple IDPs. 
  - Adding log cleaning feature


# Release notes - Phraseanet - 4.1.7
  

### Stack (docker compose, helm)

    we have bumped minimal version for docker and docker compose.
    the dc helper functionality is now compliant with docker compose (see readme).

### New Features & Improvements

```
PHRAS-3297 Send an email to users once a year to get approval for accounts
PHRAS-2872 Phraseanet Authentication uses phrasea auth service as idp
PHRAS-2995 Add phraseanet-service as Oauth service provider for Phraseanet 4.1 
PHRAS-3694 bin/maintenance clean:users, add more parameters
PHRAS-3779 Prod  - expose-cli - IDP integration
PHRAS-3728 Multiple SAML Idp support
PHRAS-3812 Docker-compose RabbitMQ define hostname and database name 
PHRAS-3783 Prod - Order manager - Adding Download Expiration date 
PHRAS-3804 Home - Acceptio Cookies consent provider integration - GDPR
PHRAS-3823 Report - bin/report a command line report generator
PHRAS-3828 bin/report and GUI Export databox content
PHRAS-1718 Bump recaptcha version 
PHRAS-1974 Autodelete inactive users after a given period
PHRAS-3813 bin/maintenance clean databox's logs table
PHRAS-3819 Bin/maintenance clean webhook's log - WebhookEvents 
PHRAS-3152 Bin/maintenance Cleaner for table "LazaretSessions" 
PHRAS-3462 Bin/maintenance  clean:apilog  and clean:WorkerRunningJob - applicationbox
PHRAS-473 Prod - Advanced search - display fields labels and other improvements 
PHRAS-2759 Report - Adding a feature in Report to display all documents sent by email 
PHRAS-3318 Authentication : Saml Authentication in docker and kubernetes context
PHRAS-3596 Prod - Expose client - Filtering Expose publications and other GUI improvements 
PHRAS-3598 Webhook - Emit improvement  - Count and Log errors - stop to notify endpoint in error. 
PHRAS-3683 Docker Compose - dc functionality improvement - support space in env value
PHRAS-3794 Install - elasticsearch configuration - \`populate\_order\`  became \`MODIFICATION\_DATE\` by default 
PHRAS-3798 bin/setup - system:config set - filtering sensitive credentials on stdout
PHRAS-3738 Admin - worker - job tab - Adding filter on databox , date, record_id
PHRAS-3754 Admin - user detail - more tab Record ACL, publications and baskets 
PHRAS-3761 Admin - worker manager - jobs - running process - computation of current duration
PHRAS-3793 Shared baskets - Update record_right when a share expires
PHRAS-3802 Home - forgotten password - change end user message 
PHRAS-3807 Border manager - lazaret - more default file extension lib/conf.d/configuration.yml  
PHRAS-3816 Improvement of db index on WorkerRunningJob table
PHRAS-3821 Docker - Faster start for fpm and worker container
PHRAS-3822 Prod : Improve Image Watermarking
PHRAS-3825 Admin - worker manager - queue - add filter "Hide empty queues"
PHRAS-3826 ci - ci refactoring - migrate to github action
PHRAS-3827 Admin - users list - keep selection and page  when validate or back in users edit
PHRAS-3739 Add JQ (php extension) to the stack
PHRAS-3764 Missing string - Check spelling for the pop up "empty collection"
PHRAS-3778 Docker - logs output optim for fpm, workers, scheduler, gateway 
PHRAS-3830 Prod - advanced search - operator for field type number
PHRAS-3832 Report - download record list - improvement
PHRAS-3833 Report - export databox action  and others fixes
PHRAS-3838 Prod - Expose - v2 - flatten assets break
PHRAS-3765 Oauth2 : allow to pass client custom parameters into session
```

### Bugs


```
PHRAS-3702 Migration patch error 4.1.6-rc3 to 4.1.6-rc4 bin/setup system:upgrade always fail on basket table
PHRAS-2948 Quarantine checker - fix checker deactivation
PHRAS-3273 Prod - mapbox user pref - map\_zoom generates an error when is memorized by mapboxgl and read by mapboxJS
PHRAS-3569 Prod - video tools - sometimes an ERROR occurs about a record file type 
PHRAS-3664 Prod - record moving between collections action : no retry on indexation failure
PHRAS-3757 Phraseanet - Phrasea Uploader - multivalued fields are merge in one value
PHRAS-3758 Prod - Baskets - Unable to start a feedback from the Action menu
PHRAS-3782 Preview "train" keyboard navigation crashes on page-1
PHRAS-3785 Stamp is KO, resulting err500 in download
PHRAS-3791 SAML AUTHENTICATION : Log out generates an 500 error
PHRAS-3795 Prod - fix 404 on GET assets/common/css/fonts/icomoon.woff
PHRAS-3800 Prod - xss injection with filename
PHRAS-3806 ps-auth : Flushing rights when changing group is not working
PHRAS-3808 Prod - video tools - Hiding "video tools" menu item when user doesn't have this right
PHRAS-3811 docker-compose fix typo in writeMetadatas service profiles
PHRAS-3814 Share basket fix migration patch
PHRAS-3815 Prod - share basket - wrong object in sent email 
PHRAS-3818 Prod- Expose cli - several fix 
PHRAS-3824 xss on preview
PHRAS-3829 Prod - basket content - Design is broken for screen "set order"
PHRAS-3831 Admin - submit xml setting return an 500 error
PHRAS-3836 Admin - status bits name - fix character encoding
PHRAS-3837 Docker - fix image build
PHRAS-3820 Migration - fix migration patch 4.0 to 4.1.7
PHRAS-3817 Webhook - created subdef - permalink is empty
PHRAS-3314 Prod - Expose - Publication with huge amount of assets - loading assets is very long - wrong UX
```


## 4.1.6

### Update instructions


 - docker docker-compose : add profile "setup" and "redis-session" to your ```COMPOSE_PROFILES```

    - Change in methode for defining the `servername` key in `configuration.yml` 

      -  `PHRASEANET_SERVER_NAME` env is removed and content of it have need to be splited in 2 env `PHRASEANET_SCHEME` `PHRASEANET_HOSTNAME`
      -  The env `PHRASEANET_SCHEME + PHRASEANET_HOSTNAME + PHRASEANET_APP_PORT` define a new env named `PHRASEANET_BASE_URL`
      -  `PHRASEANET_BASE_URL` is used for set `servername` key in `configuration.yml` 

    - "setup" profile launch the setup container for performing an app installation 
       or report ```PHRASEANET_*``` env var values to Phraseanet ```configuration.yml``` file

    - "redis-session" profile launch a  ```redis session``` container for storing the user's php session
       and permit the scaling of Phraseanet container 
      - when you migrate, it can be useful to empty the application cache by ```rm -Rf cache/*```


 - Migration instructions: After a backup of all dabases and file ```config/configuation.yml```
   Run upgrade for bump version ```bin/setup system:upgrade```
   The "shared basket" feature introduces a major change in the database schema.

 - Elasticsearch index action : Requires a drop, create, populate if you come from 4.1.6-rc1 or lower, 
   not required if you update from 4.1.6-rc2.

### Version summary :
 
  This changelog include also 4.1.6-rc3, 4.1.6-rc4 and 4.1.6-rc5

  - Shared Baskets : 
    - the Phraseanet basket can now be shared between several users and the feedback becomes now an option on this shared basket.

      - keys features : 
        - It's possible to define an expiration date for a shared basket.
        - It's possible to set a contributor right for basket's participants.
        - A feedback request can be added to the shared basket.

  - Printed PDF 
    - Completing options in printed PDF that we introduced in 4.1.6-rc2. 
        
        - Font size can be set for record indexation and record's information block.

        - Color for field Label can be defined.

        - Print record's information block under preview is now an option.

  - Refactoring Phraseanet installation and setup process in docker-compose and HELM.

  - It is now possible to not write the databox field on file's metadatas for the record's original document.
  
  - It is now possible to set a subdefinition not built by Phraseanet.
    
    - A file can be added using API on this subdefinition  

  - Deployement.

      - Move user session in new dedicated redis container.
      - Adding an container for "saml-service" 
      - Helm chart improvement : add missing values 
      - Dedicate a container for Phraseanet installation and Setup  

### New Features

```
PHRAS-3380 Shared Basket features
PHRAS-3712 Admin - Sudefinition - building a subdefinition becomes an option
PHRAS-3713 Admin - Writing metadatas into record's original document becomes an option
PHRAS-3564 Phraseanet - subdefinition service - API for building a subdefinition file from a source file (alpha)
PHRAS-3704 worker - Build Phrasea rendition with the subdefinition worker (beta)
```

### Improvements

```
PHRAS-3700 Bump switfmailer version - Microsoft dropped support for TLS 1.0 und 1.1
PHRAS-3697 Printed PDF user choice improvement, font size, color , block information.
PHRAS-3695 Prod - basket and feedback displayed informations improvement
PHRAS-3692 Prod - Default user's setting - in configuration.yml :  add face order display settings
PHRAS-3686 Prod - caption : characters \(#,!\) into a clickable url link can lead to cut the link
PHRAS-3684 Prod - Workzone - basket tab - visually separate basket/stories in 3 blocks. "Shared with me" , " My baskets" , "Stories" 
PHRAS-3678 LightBox - Improvements for Basket Share
PHRAS-3675 Worker -  fix heartbeat sent to RabbitMQ channel by worker
PHRAS-3674 Prod - Record Information - Add Databox name in information
PHRAS-3665 Check - Prod : Validation reminder can be disabled on feedback
PHRAS-3663 Prod - workzone - basket tab - filter refactoring - css issue
PHRAS-3662 Prod - shared basket - fix design  - icon in detailed view and action bar etc
PHRAS-3657 Docker | helm - ready for scale the fpm container - refactoring install and setup and php session store.
PHRAS-3525 Admin - worker service - job tab - add purge on all running job - warn user with js alert
PHRAS-3121 Prod - Tools - Tab subdefinition rebuild -  option for choosing which subdefintion  will be rebuilt \(thumbnail, preview etc ...\)
PHRAS-1545 Prod - order manager  - several fix and improvements back and front
PHRAS-3720 Webhooks - option for SSL validity and webhook "record.subdef.created " add permalink, size and mime in json
PHRAS-3729 Uploader PUll mode now compatible with multi destination
PHRAS-3719 Admin - Worker - Job tab - Adding filter on job kind
PHRAS-3235 Admin - Collection - Emptying a collection is now made by "delete worker" 
```

### Bug fix

```
PHRAS-3717 API - Wrong extension on subdefinition upload/substitute with parameter adapt=0
PHRAS-3711 Admin - Users - Modify, edit multiple users rights
PHRAS-3685 Prod - create story - don't propose to set a name for a story
PHRAS-3679 Prod reload when editing multi-databox records
PHRAS-3672 Prod - Wording issue - share overlay - deleting a list of users
PHRAS-3664 Prod - record moving between collections action : no retry on indexation failure.
PHRAS-3650 Worker - broken Pipe on RabbitMQ  connection due to "consumer\_timeout"
PHRAS-3649 Prod - sharing a basket : loading 1000 users list fails in share
PHRAS-3645 Thesaurus - Candidates are not generated for fields with special character
PHRAS-3639 Prod - Video tools - Subtitle editing - error when try to edit the last item
PHRAS-3612 Prod - thesaurus used for classement - Gui string html missmatch
PHRAS-3591 Admin - Databases - Subdefinition setting - lenght of subdef name is limit to 16 characters but 64 in database column
PHRAS-3698 Docker - Dockerfiles - FPM images - Fix the Imagemagick download path
PHRAS-2646 Error in 4.1 a feedback with null or empty in Name
PHRAS-3666 Prod - Print - PDF - Generated pdf can't be printed even if no password is defined
 ```


## 4.1.6-rc2

### Update instructions

 - docker docker-compose : add profile "gateway-classic" to your ```COMPOSE_PROFILES```
 - Migration instructions: just run upgrade for bump version 
 - Elasticsearch index action : Requires a drop, create, populate

### Version summary :
 
  - A new facet named "Thumbnail_orientation" is available in replacement/addition of "_orientation" (based on exif orientation)
    This facet is based on orientation of generated subdef named "thumbnail". 
    see section searchengine setting/ aggregate in Admin to activate it.
    the features require an Elasticsearch index drop, create, populate  
  - Adding a separate docker-compose profile to nginx container for a better stack compositing
  - Admin Gui users, more search options for users, improved user export, and more information in user details.

### New Features

```
PHRAS-3215 Prod - facets -  use image orientation from subdefinition and make a facet of it
```

### Improvements

```
PHRAS-3643 Bin/console records:build-subdef add option --publish to emit build message to Rabbitmq
PHRAS-3653 Worker queue message : publish messages as persistent into rabbitmq queues
PHRAS-3560 Admin - Users list and search improvement and export users as .csv, add "last connection"
PHRAS-3223 Admin - user details - Display AuthFailure and UsrAuthProvider info
```

### Bug fix

```
PHRAS-3651 prod-facets : tech facets "no value" wrongly translated, some always return 0 answers
PHRAS-3655 Integrity constraint violation when deleting a user with an entry inside table ApiOauthCodes
 ```

## 4.1.6-rc1

### Update instructions

 - Migration patch: no patch to play, just run upgrade for bump version 
 - Elasticsearch index action : none 

### Version summary :
 
  - Big improvement of generated PDF,
    - Password protection
    - Download link 
  - Prod - Publications editing, user experience improvements

### New Features

```
PHRAS-3642 API - Return databox subdefs on new endpoint /api/v3/databoxes/\{databox\_id\}/subdefs/ and  /api/v3/databoxes/subdefs/
PHRAS-3636 docker - docker-compose - Add container for execute primary datastore \(mysql\) backup
```

### Improvement

```
PHRAS-3633 Prod - Printed PDF  Improvement - add a Title , Password, download link to the PDF
PHRAS-3595 Prod - New publication - features and UX improvement
PHRAS-3631 Prod : Notifications : Add a fonction to mark all notification as read
PHRAS-3229 Thesaurus GUI improvement - import - refresh  candidat terms
```


### Bug fix

```
PHRAS-3637 API - Upload Url | Prod GUI - Let's Encrypt ssl certificate verification fail. use the correct guzzle version
PHRAS-3635 user list : general toggles change selection
PHRAS-3626 Prod - detailed view -  Print, Export windows appear behind the detailed view \(z-index\)
PHRAS-3620 Admin - subviews : a bad path can lead to creating file at the roots of the Phraseanet sources.
PHRAS-3619 After record removal, we have an HTTP status 200 on the /records route of the API on the deleted record
PHRAS-3285 Thesaurus - candidat panel - The Stock is not available
PHRAS-3628 API - create record - 500 error if No file
 ```

## 4.1.5 

### Version summary :

- Search Engine 
  - It is possible to search for records where fields are filled with a given value, eg : search record where field is fullfilled ```Title=_set_```
  - It is also possible to search record using documentary field if empty,  eg : search record where "Title" field is empty ```Title=_unset_```  .
  - Display an "Unset" facet  to quick filter results with no value in field.
    This is an option that the user can activate in "Prod", "workzone",  "facets setting", useful to detect and fix an incomplete indexing.

- Record classification by drag and drop on thesaurus terms
  - This now is possible to add terms to record or story by drag and drop them to a thesaurus term.   
 
- Feedback improvement: 
  -  Change the feedback's deadline or reopen it.
  -  Add manually new user during the feedback. 
  -  Send manually a feedback reminder by email to selected users including a new connection link (token).
 
 - Including a CGU files (as pdf) in the downloaded ZIP.
   - the attached PDF include thumbnails and description of downloaded files. 

- Phrasea Expose in Phraseanet Production
    -  Better integration between Prod and Phrasea Expose service.
    - Add mapping for fields and subdefinitions when adding records to a publication.
  
- Databox subdefs - Create watermarked subdefs.

- Generate sub definition for HEIC file.

- Worker Record Actions for replacing legacy task "record mover".     

- Webhook Improvement 
   - It is now possible to subscribe only on some events.
   - More events are emitted on record/story actions.
     - record/story creation / deletion
     - record/story editing 
     - record/story status changed 
     - record/story collection change
     - record/story file substitution
     - Change the webhook json content, including "before" and "after" state for 
         - Collection change 
         - StatusBits change
         - Indexation change 

- docker and docker-compose
  - Add docker-compose ```profiles``` for a better stack compositing  
  - Add container for legacy schedulers 

- API improvement on story and search endpoint
     - Story search mode improvement of ```include```.  

- Implement HTTP proxy support server side for request made by : 
    - Webhook Emit 
    - Geonames request
    - Communication with Phrasea Expose 
    - Communication with Phrasea Uploader
   

### Bug Fix :

```
PHRAS-3566 Prod - upload - It is possible to apply status on upload even if the user does not have "Change status" right
PHRAS-3565 Prod - Editing - fields using Geonames service - the fields are not filled  anymore
PHRAS-3544 Prod - tool - file with is not invalidate when made rotation or recreate subdefinition.
PHRAS-3541 Prod - Image rotation NOK - ETAG is not renew -  File is correctly rotated but not invalidate in browser cache.
PHRAS-3528 Prod - export - web browser loops download zip file 
PHRAS-3509 Prod - tools - document substitution - the generation message for sub-definition is published twice.
PHRAS-3460 Prod - Detailed view - Timeline tab (History) -  events sorting is wrong and some events do not appear
PHRAS-3386 Prod - Baskets zone is blank after re-opening the workzone
PHRAS-3356 Prod - detailed view -  related story - broken when no right to Access report
PHRAS-3348 Prod - Feedback (AKA Validation) : Update validation expiration date do not update the validation token expiration Date
PHRAS-3126 Prod - search bar - background blue coloration is missing when search filter is active.
PHRAS-3032 Prod - Multi Stories editing - Editing need to be applied only on stories (not on included records).
PHRAS-3374 Prod - upload overlay - rendering issue on Upload Overlays loading
PHRAS-3421 Prod - expose - authentication for multi expose with password is NOK
PHRAS-3443 Prod - Maintenance message is not displayed to the end user
PHRAS-3490 Admin - databases - collection setting- A duplication of value occurs when deleting suggested values
PHRAS-2832 Lightbox Error 500 when a basket contains recordid not anymore in the DB
PHRAS-3285 Thesaurus - the Stock is not available on the candidates section of the Thesaurus
PHRAS-3583 Search Engine - Sort records results on customer's Fields date or number return 500 error.
PHRAS-3360 Configuration.yml , Missing "Worker" section introduce in version 4.1.1
PHRAS-2441 Lifetimes for session in configuration.yml not taken into account , make clean between "TTL" and "lifetimes"
```

### New Features :

```
PHRAS-3417 Search on field with no value and generate facets
PHRAS-3381 Prod - Thesaurus as tx - use Thesaurus for classification plan - Drag and Drop record on a terms
PHRAS-3216 Prod - Feedback- Workzone - feedback improvement , renew user , manual
PHRAS-3288 Prod - Workzone - Add more users in existing feedback.
PHRAS-3287 Prod - Workzone - feedback -  send a reminder email with link to feedback, 
PHRAS-3080 When a document is exported , add a PDF File with Databox's CGU
PHRAS-2896 Generate subdef for HEIC file - HEIF (High Efficiency Image Format)
PHRAS-3535 Prod - Feedback (AKA Validation) - Add features , send a new access token to user in message windows, reload basket after expiration date change
PHRAS-3580 Admin - databoxs - databox subdefs - Create watermarked subdefs
```

### Improvements :

```
PHRAS-3584 Story - maintain a link between the story cover and the record used to define it.
PHRAS-3536 Admin - Users list - add "last connection " colon in user list - mapped on colon "Users.last_connection" of application box
PHRAS-3456 Admin - User registration  - Send the email unlock account in first, Before e-mail for  password definition
PHRAS-3366 Admin - base base setting -  Button "Re-index database now" change behavior, send a populate
PHRAS-3524 Prod - windows Notification -  Notification for a "received basket"  require a double click.
PHRAS-3522 Prod - Notification - notifications windows contain uninterpreted HTML (URL)
PHRAS-3516 Prod - Advance search -Sort results by field type string
PHRAS-3256 Email notification - Take the recipient language (locale) in account
PHRAS-3519 Use "move" method after each Copy (upload, worker, API)
PHRAS-3469 optimisation of slow request  “get notifications”  due to "MySQL Baskets select"
PHRAS-3499 Worker -  Stamp process - Stamp on file is made by worker export by email
PHRAS-3447 Worker - add flock (file lock) and Get Mutex in WorkerRunningJob table
PHRAS-3445 Worker - editrecord - explode editing mds to small message for each records - add retry and error queues
PHRAS-3427 Worker - configuration  - rabbitmq support the AMQPS SSL connection.
PHRAS-3454 Worker - write metadata « undefined index count »
PHRAS-3494 Docker - launch a container with worker images in legacy scheduler context "bin/console scheduler: start"
PHRAS-3484 Docker - Check rabbitmq and Mariadb version and fix
PHRAS-3551 Docker-compose - refacto - worker - use profile - launch one worker for each Job
PHRAS-3463 Docker - worker container - add (again) Supervisor into it and launch "Phraseanet worker" with  (env based)
PHRAS-3372 Docker -  entrypoint.sh refacto add env: for Playing upgrade,  for no setup if need
PHRAS-3364 Docker-compose - Add COMPOSE_FILE  in .env - put mailhog in other docker-compose file
PHRAS-3361 Docker-compose - Declaring a network for Phraseanet stack - stop using "default" network
PHRAS-3346 Docker - add Healthcheck for gateway container option
PHRAS-3324 Docker-compose.yml upgrade from 3.4 to 3.9 version and add profiles for stack compositing.
PHRAS-3102 Docker-compose - MariaDb container-  Add env for set slow query - max_connection etc ...
PHRAS-3475 Prod - expose - expose setting , define and store mapping - which subdefinition is uploaded to an publication
PHRAS-3474 Prod - expose - expose setting  , define and store mapping for field send to expose asset description
PHRAS-3507 Prod - Expose - Set null when user select "No parent publication" and other fix
PHRAS-3442 Optimise List_notifications in 4.1
PHRAS-3438 conf/configuration.yml - Set an http and ftp proxy (squid in dev mode) and use serveur Side eg worker ; geocoding request, ftp, uploader etc...
PHRAS-3413 Webhook emit improvement , define an emit Timeout - default 30 sec - this timeout can be override in configuration.yml
PHRAS-3399 Prod - feedback - Right issue and others improvements
PHRAS-3394 Prod - CSS - Rewriting "black-dialog-wrap" classe
PHRAS-3393 API V3 patch use record adapter
PHRAS-3391 Prod - Detailed view - feedback context - add confirmation when user try to delete a Record
PHRAS-3390 Prod - Workzone - basket tab - local menu - "Delete" action, add "Archive" and "Cancel" choice in confirmation windows
PHRAS-3389 Search - thesaurus - Concept Path - Stop to use Thesaurus from other databox.
PHRAS-3388 Prod - Baskets - Validation Basket - Improve validation UX - show feedback result in Detailed view
PHRAS-3378 Prod -Detailed view - Apply number formatting to result count " Result 1 900 / 902 723"
PHRAS-3375 Export by email - add  download-link-validity: 24 , Email - download link TTL
PHRAS-3371 Prod - Detailed View - Check navigation between records  with keyboards.
PHRAS-3353 Prod - Avoid purging the browser's local cache for JS - versioned file for commons.min.js production.mn.js
PHRAS-3352 Prod- Workzone - keep sort (order) and filter define by the user - date or alpha
PHRAS-3350 Password renewal and creation - link send by email - token TTL  - token table of applicationBox
PHRAS-3341 Prod - Detailed view - title bar  - refactoring UX
PHRAS-3237 Worker - Port "record mover" task as Worker And rename It "RecordsActions"
PHRAS-3166 Worker webhook - clean old webhook table (maintains value)
PHRAS-3146 Worker - Consuming Dead Letters - Add TTL to msg in error for auto purge
PHRAS-3457 Notifications cleanup
```



### Others (change on external lib, documentation update )

```
PHRAS-3534 Embed-bundle bump PDFJS version.
PHRAS-3250 Prod - answer grid - GUI freeze  at end of search query execution (after loading data)
PHRAS-3245 Install - scheduler - stop to create (default) tasks - subview and write metadata.
PHRAS-3266 Documentation Phraseanet - How to migrate Phraseanet data under docker
PHRAS-3153 API V3 - Add documentation to swaggehub and serve by it - sync swaggerhub with Phraseanet github repository
PHRAS-3335 Admin - Dashboard - requirement - fix warning
PHRAS-3050 Documentation update for install storage option added in 4.1
PHRAS-2487 Documentation of Add - upload asset, as record in Phraseanet, by URL
PHRAS-3610 Documentation update search option in  Elasticsearch 
PHRAS-3411 Prod - String for Thesaurus as tx windows
```

Note : For technical reasons, no Docker image and packaged version have been generated for the 4.1.4 version. Therefore, the release notes below concern both versions 4.1.4 and 4.1.5.



# 4.1.4


### Version summary : see upper 4.1.5
   


## 4.1.3

Release notes - Phraseanet - Version 4.1.3

### Release summary

   - API V3 first iteration of new API 
     - Search endpoint with story 
     - Searchraw endpoint, a new faster search method
     - Record CRUD

   - Phraseanet-service Expose is now included in Production  

   - Worker: More processes are now made by worker 
     - Export by ftp 
     - Sending of a feedback reminder

### New Feature

    * [PHRAS-3188] - PS Expose in Prod - Design of Expose front in Phraseanet
    * [PHRAS-3189] - PS Expose in Phraseanet core -  Prod, Admin and worker - MVP
    * [PHRAS-3253] - PS Expose in Phraseanet core - User authentication via Auth service
    * [PHRAS-3315] - PS Expose uploader worker - Use of a new Upload method - PS-276 - Direct upload to Minio or S3
    * [PHRAS-3262] - PS Expose - Prod - Set user/group right on a publication
    * [PHRAS-3124] - PS Expose - Insert record description and geopoint into Expose assets description
    * [PHRAS-3124] - API V3 MVP 
    * [PHRAS-3174] - API V3 new actions on records endpoint (get, post, delete...)
    * [PHRAS-3124] - API V3 - /searchraw endpoint, serve result from Elasticsearch index.
    * [PHRAS-3279] - API V3 /searchraw : Add record permalinks to es
    * [PHRAS-2443] - API V3 - Stories content count and paginate /story


### Improvement
    

    * [PHRAS-3300] - Prod / Worker-delete-queue : Deletion is long when having a huge amount of files into a database. 
    * [PHRAS-3326] - Prod - Workzone - Basket items - Behavior change, default action is "copy" now, not "move" anymore
    * [PHRAS-2003] - Prod - Detailed view - mapbox web gl - Change "star" icon to other method to display asset position.
    * [PHRAS-3214] - Feedback Reminders - Refacto and readiness for worker
    * [PHRAS-3190] - Phraseanet-production-client in Phraseanet github repository AKA all dependencies in one repository
    * [PHRAS-3218] - Prod - editing - Field date format - Reduce input errors on date fields, check "yyyy/mm/dd" and "yyyy/mm/dd hh:mm:ss"
    * [PHRAS-3219] - Thesaurus - alert js - Error generated by string (in translation file) with carriage return
    * [PHRAS-3220] - Docker - Phraseanet entrypoint - chown optimisation
    * [PHRAS-3231] - Docker - Multi value for PHRASEANET_TRUSTED_PROXIES  
    * [PHRAS-3321] - Docker - container worker : Manage ImageMagick policies (/etc/Imagemagick/policy.xml)
    * [PHRAS-3230] - Docker - Create env variable for trusted-proxies in docker-compose.yml    
    * [PHRAS-3129] - Docker - Worker -  If rabbitMQ is not up, worker needs to exit with 1 
    * [PHRAS-3336] - Docker - Docker-compose - improvement  - Set Mailhog, Set application-name, cache clearing, chmod on configuration.yml 
    * [PHRAS-3236] - Worker - Port Ftp send task as Worker 
    * [PHRAS-3239] - Worker - Queue: how to purge a queue in admin worker queue tab
    * [PHRAS-3240] - Worker - Send feedback reminder with a worker 
    * [PHRAS-3282] - Worker - Refacto some code on queue (queue naming, retry delay) 
    * [PHRAS-3325] - Docker - Worker - If  DB is not up, worker need to exit with code 1
    * [PHRAS-3330] - Docker - Worker - Change launch method for "bin/console worker:execute" (Phase 1)
    * [PHRAS-3251] - Permalink generation - build permalink when  generate subdef and  Make a new cli cmd for build missing permalink
    * [PHRAS-3261] - Prod - MapboxGL MapboxJS improvement - Add search zone on map and add position as we have in mapboxJS.
    * [PHRAS-3265] - Port to 4.1 - Take into account max_result_window when creating elastisearch index
    * [PHRAS-3270] - Prod - Editing - MapboxGL - editing assets position form several record improvement
    * [PHRAS-3274] - Prod - Editing - Request is too long when editing a large amount of records  
    * [PHRAS-3276] - Prod - Detailed view - Timeline Tab - Feedback send action appears as Push send 
    * [PHRAS-3277] - Change validation-reminder-days: 2 for a percent of time before expiration default value is 20 %
    * [PHRAS-3301] - Documentation - API V1, search with truncation - truncation=1/0
    * [PHRAS-3306] - prod - Expose - Several fixes 
    * [PHRAS-3309] - Prod - Baskets - Feedback Basket - Warn user when trying to delete item in a "feedback"
    * [PHRAS-3323] - Prod - Workzone - Basket menu - Remove "export" item 
    * [PHRAS-3338] - Prod - Detailed view - title bar - Add icon for basket or feedback, change place of record count (after the basket title) 
    * [PHRAS-3210] - Stop execute query on Tokens during user connection (send feedback reminder) 
    * [PHRAS-3221] - Prod - Admin about url to store.alchemy.fr switch scheme from https to http
    * [PHRAS-3228] - Uploader - Pull mode by Phraseanet - Report refactoring Oauth made in April 2020
    * [PHRAS-3311] - Admin - Worker manager - Tab Job - Hide payload column  

### Bug fix 

    * [PHRAS-2060] - Prod - Editing - Impossible to define Geoloc of an asset, if it does not contain geoloc when archived
    * [PHRAS-2752] - Prod - Geolocation - Editing - When selecting one asset, geolocation tab shows locations of all selected pictures
    * [PHRAS-3211] - Prod - Editing- Date field - The editor's date picker is always displayed on the French language
    * [PHRAS-3212] - Upload JS Error message during upload: "img.toDataURL is not a function" 
    * [PHRAS-3227] - Unable to load a thesaurus file, due to localization of thesaurus module
    * [PHRAS-3244] - Worker - admin - populate - Populate again a database after manual interruption is not possible 
    * [PHRAS-3255] - Prod - editing - Set assets position with mapboxWebGL is NOK
    * [PHRAS-3257] - Prod - Detailed view - Unproper content display when clicking on "also in basket/feedback"
    * [PHRAS-3258] - Prod - Query - Editing and Detailed view  MapboxJS doesn't load map named  Streets and Outdoor
    * [PHRAS-3267] - API V3 - Bad "total" story children count AND 500:"permalink already exists"
    * [PHRAS-3268] - Prod - Detailed View - Basket context - Deleting a record from a basket is NOK 
    * [PHRAS-3281] - Prod - Editing - The preview tab is not working
    * [PHRAS-3284] - Plugins : "ClassNotFoundException" on bin/console after plugin install
    * [PHRAS-3310] - admin - worker manager - Tab "Job" - 500 Error when table "WorkerRunningJob" contains a huge number of lines 
    * [PHRAS-3331] - Prod - Story - Ordering elements of a story is not working on a Docker environment
    * [PHRAS-3322] - Prod - Detailed view -  Basket context - Removing record from (current) Basket is NOK


### Task

    * [PHRAS-3278] - Release 4.1.3 and Migration patch for 4.1.3
    * [PHRAS-2873] - Phraseanet repositories reorganization
    * [PHRAS-3295] - Change method for Playing test (old fashion) on CircleCI with php7


## 4.1.2

Release notes - Phraseanet - Version 4.1.2

### Release summary
  
  - Gui for subtitling video and 
  - Integration of auto subtitling and translation service for subtitling by Gingalab
  

### New Feature
    * [PHRAS-3116] - Prod - video-tools - GUI to generate and edit subtitles for videos
    * [PHRAS-2504] - Integration of auto subtitling and translation service for subtitling by Gingalab.


### Improvement
    * [PHRAS-2541] - Dev-Design-Prod/Publish window
    * [PHRAS-2542] - Refacto design "move" record from collection overlay.
    * [PHRAS-2615] - API and configuration.yml Change syntax and behavior of api_token_header
    * [PHRAS-2843] - Allow any parameter settings during installation
    * [PHRAS-3117] - Prod: move video tools from action bar to local thumbnail edit menu 
    * [PHRAS-3122] - Cancel editing dialog : not very clear...
    * [PHRAS-3128] - Worker check queue monitor for display in admin worker-manager section 
    * [PHRAS-3135] - Docker - container Fpm entrypoint - SMTP test if env is null or not defined 
    * [PHRAS-3158] - Docker-compose: Use new install option for download, lazaret, etc..
    * [PHRAS-3139] - Change on databases models for subview sizes and geolocalisation fields configuration
    * [PHRAS-3143] - Clarifai - the substitution file is submitted when record is created 
    * [PHRAS-3145] - Plugin Clarifai stop submit at each build of subdef 
    * [PHRAS-3147] - Thesaurus GUI - launch a populate directly from GUI 
    * [PHRAS-3148] - Thesaurus GUI - redirect all popup in modal on main windows 
    * [PHRAS-3151] - Fix Mapbox deprecation Mapbox Studio Classic
    * [PHRAS-3156] - Prod - request map - memorize zoom level between 2 search sessions 
    * [PHRAS-3157] - Prod - Video Tools - Thumbnail tab - some fix, add spinner during image capture, color of selection etc ...
    * [PHRAS-3167] - Docker -  parameters  for php and nginx for timeout and upload size max_execution_time, max_input_time etc ...
    * [PHRAS-3169] - Prod - video tools - Clip numbers taken into account in vtt when they exist.  
    * [PHRAS-3175] - Prod and Lightbox - export by email - email as widget 
    * [PHRAS-3177] - Prod - video tools - auto subtitling - add info about running autosubtitling request
    * [PHRAS-3178] - Docker Trusted_Proxy from env and set in entrypoint of fpm container 
    * [PHRAS-3184] - API api_token_header refacto change behavior, name and place in configuration.yml  
    * [PHRAS-3192] - Docker Install Inkscape to allow svg subview generation with imagick
    * [PHRAS-3196] - Phraseanet worker uploader - report change made in Phraseanet-service uploader
    * [PHRAS-3202] - Admin - worker manager - job tab -  mark a job ligne "interrupted"
    * [PHRAS-3203] - Admin - worker manager - job tab -  Display job payload as Json
    * [PHRAS-3170] - Use WorkerRunningJob table for populate etc .. and Test faster events, faster queuing

### Bugfix
    * [PHRAS-3110] - Docker-compose - sometimes the file in cache/ repo returns permission denial for user app
    * [PHRAS-3125] - Prod - basket list - basket detail design is Nok 
    * [PHRAS-3159] - Lazaret (quarantine) storage file path is not taken in account during installation
    * [PHRAS-3162] - Indexing - generates un error when last character of extracted text from PDF is an UTF8 character
    * [PHRAS-3163] - Prod - Publication - Create publication - error in console when create 
    * [PHRAS-3164] - Lightbox - download action is NOK
    * [PHRAS-3193] - SVG file is broken by meta data writing job
    * [PHRAS-3138] - Lightbox - Lightbox detail - caption is not present and design improvement 
    * [PHRAS-3137] - Help menu link to doc 4.1
    * [PHRAS-3173] - Docker - PDF extraction is NOK on Docker 
    * [PHRAS-3204] - Worker - Add reconnect to db when populate ended
    * [PHRAS-3181] - Worker video build subdefinition  ends with error and tmp file is not copied to destination


## 4.1.1

###  Change summary

- Phraseanet now using Docker. Retrieve all official images on DockerHub 
- Worker manager, a new way for all operations on assets. In the near future, this will replace the current task manager.
- Geolocation based on Mapbox (requires an account on Mapbox https://www.mapbox.com).
- Video chaptering and subtitling support.
- GUI redesign for Push, Feedback, List manager, Lightbox on mobile.


 this version is finale version of 4.1.0 published in preview at start of year, a lot of improvement, bugfixes on several elements see summary here 

### New Feature summary
 
    * [PHRAS-2023] - Refacto Lightbox mobile in 4.1
    * [PHRAS-2219] - Refacto design Push screen
    * [PHRAS-2220] - Refacto design Feedback screen
    * [PHRAS-2221] - Refacto design List manager general screen
    * [PHRAS-2222] - Refacto design ListManager Advance Mode screen
    * [PHRAS-2223] - Refacto dev list manager Advance Mode screen
    * [PHRAS-2541] - Dev-Design-Prod/Publish Screen
    * [PHRAS-2548] - Phraseanet Docker and Docker Compose
    * [PHRAS-1226] - Geolocalisation In Phraseanet
    * [PHRAS-1626] - bin/console databox:mount mount an existing databox
    * [PHRAS-1628] - bin/console collection:publish
    * [PHRAS-1630] - bin/console database:unmout 
    * [PHRAS-1631] - bin/console collection:unpublish
    * [PHRAS-1648] - bin/console user:password
    * [PHRAS-1659] - bin/console user:create
    * [PHRAS-1771] - bin/console collection:unpublish
    * [PHRAS-1773] - bin/console collection:publish
    * [PHRAS-2518] - Phraseanet worker Read/Write metadata
    * [PHRAS-2520] - Phraseanet worker send webhook
    * [PHRAS-2738] - Phraseanet worker populate database
    * [PHRAS-2435] - Phraseanet Worker Build subdefinition
    * [PHRAS-2436] - Phraseanet Worker build zip export and send mail
    * [PHRAS-2636] - Phraseanet Worker fetch assets from external uploader (pull mode)
    * [PHRAS-2904] - Fullfill field define in geoloc - position field with information return by Geonames
    * [PHRAS-161]  - PROD Add a maps for geolocalisation of media in detailed view
    * [PHRAS-1935] - View prod/ Video chapter editor
    * [PHRAS-2997] - Matomo analytic service in Phraseanet
    * [PHRAS-1890] - Add GS1 databases model to Phraseanet


### Improvement and fix summary

    * [PHRAS-1561] - Prod | Print - Use the label of field when print, use the GUI user language
    * [PHRAS-2067] - Prod : Introduce thumbnail & preview generic images for Fonts records
    * [PHRAS-2473] - Populate Optimisation, sometime populate databox (database) is very long
    * [PHRAS-2524] - Put worker log in ELK 
    * [PHRAS-2739] - incorporate Phraseanet-plugin-SubdefWebhook into Phraseanet
    * [PHRAS-2157] - Prod / Share : Iframe sizes are set to 0 for audio documents
    * [PHRAS-2538] - Some MP4 file is not correctly detected by Phraseanet.
    * [PHRAS-2825] - Prod : Add a reset button to initialize searches filters
    * [PHRAS-1872] - prod/export by email / subject are NOK
    * [PHRAS-2342] - Report : collections not selected
    * [PHRAS-2343] - report : all fields of all databases
    * [PHRAS-2350] - Report : url is too long
    * [PHRAS-2476] - Bad header in generated video preview file  
    * [PHRAS-2196] - API - Stories records pagination on search answer and Stories fetch info 
    * [PHRAS-2880] - extend admin GUI  for define facets ordering. 
    * [PHRAS-2967] - Lightbox - dev of send email report - warn windows
    * [PHRAS-1752] - update facebook sdk dependency
    * [PHRAS-2678] - add `webhook monitor`
    * [PHRAS-2915] - Lightbox (desktop version) Change sort order for basket and Feedback in landing page ( most recent in first)
    * [PHRAS-2082] - Bump design of windows create user , create template user, create new subdef
    * [PHRAS-2676] - Weaked download behaviour for large amount of data
    * [PHRAS-2671] - Change behavior of preview display in audio file case
    * [PHRAS-2879] - Define facets order in GUI and query result

## 4.0.12 

Release notes - Phraseanet - Version 4.0.12

### Improvement
    * [PHRAS-2955] - Cache doctrine entity metadata for performance
    * [PHRAS-2964] - Application-box - set host colon of table sbas set to char 255
    * [PHRAS-3012] - [PHRAS-2977] - Docker compose optimisation, refacto volumes, build image  
        optimisation, add Phraseanet plugin in build image, bump ffmpeg version in worker, 
       fix error un redis configuration.
       more option for define volumes during installation process. 
    * [PHRAS-3027] - Backport To 4.0 - Populate  - Slow query - due to  LIMIT in sql query.
    * [PHRAS-3027] - Translation improvement in EN and DE.

### Bugfix
    * [PHRAS-2979] - The content of a story is not displayed even for users with appropriate on the collection


## 4.1.0 

Pre release of 4.1 


## 4.0.11

Release notes - Phraseanet - Version 4.0.11

### New Feature and Improvement
    * [PHRAS-2878] - Print feedback report in PDF
    * [PHRAS-2757] - Exclude some collections from quarantine checkers sha256, UUID, filename (AKA exclude Trash from quarantine)
    * [PHRAS-2766] - Add status change capabilities to quarantine lazaret in substitute and add action
    * [PHRAS-2674] - Prod grey skin Improvement
    * [PHRAS-2775] - Prod - plugin - Publish item in diapo local menu - plugin skeleton improvement.
    * [PHRAS-925]  - Search Engine improvement for word with dot and hyphen characters
    * [PHRAS-2496] - Pre-build vagrant image for Phraseanet and implement it in Phraseanet vagrant  file.
    * [PHRAS-2637] - Sub definition Task init : select all databases when databases property is not set
    * [PHRAS-2670] - Fix notifications slow sql and basket select 
    * [PHRAS-2672] - Bump videojs version to 7.5
    * [PHRAS-2691] - Prod - delete from trash , send deletion by bulk of 3 records
    * [PHRAS-2700] - Prod - number of results  - Formating the results number
    * [PHRAS-2742] - Enhance plugin-skeleton in 4.0
    * [PHRAS-2750] - PHPExiftool to handle DJI XMP Tags, Bump exiftool version and switch to original exiftool/exiftool  github repository
    * [PHRAS-835] -  ES - date format timestamp unix,  store and search datetime
    * [PHRAS-2791] - Embed-bundle - Videojs player serve poster-image property with sub definition permalink
    * [PHRAS-2842] - Databases Models - now default audio encodeur is mp3lame
    * [PHRAS-2857] - Exclude some collections from quarantine checkers sha256, UUID, filename (AKA exclude Trash)   
    * [PHRAS-2899] - Quarantine: allow to substitute without selecting target record, (when match only one record).
    * [PHRAS-2765] - Translation in Plugin menu locale is now available
    * [PHRAS-2929] - bump sinonjs dependency to  1.7.1
    * [PHRAS-2728] - Landing page take browser language in account
    * [PHRAS-2693] - Collection Sort Sorter is now presented by column
    * [PHRAS-2817] - Deploy and Dev with docker is OK


### Bugfix
    * [PHRAS-1069] - Dates seems not extracted from iptc
    * [PHRAS-1428] - Phraseanet Binaries in configuration  not used in some alchemy-fr libraries (AKA text extraction of pdf is NOK)
    * [PHRAS-2567] - Registration Form - Term of use link is broken
    * [PHRAS-2644] - Searching for stories after applying a document filtering choice gives no results
    * [PHRAS-2652] - Fields "Phraseanet::no-source" are pushed to exiftool
    * [PHRAS-2682] - Prod - facets display is NOK when switch from basket or thesaurus Tab.
    * [PHRAS-2695] - Prod - Grey and White Skins - Browse Baskets: Unable to read the titles
    * [PHRAS-2702] - Lightbox - scroller thumbnail Nok
    * [PHRAS-2714] - Adding record from the API leaves a copy of the file into the system temporary directory
    * [PHRAS-2715] - Embed bundle, border issue on firefox.
    * [PHRAS-2716] - Records SetStatus HTTP API malfunction
    * [PHRAS-2723] - None information (name, last name etc...) is keep from the Push or a FeedBack user creation form
    * [PHRAS-2748] - Some characters into cterms (candidats) leeds to 500 error 
    * [PHRAS-2754] - Permalink is not (re) activated when record is move from _TRASH_ collection
    * [PHRAS-2860] - Generated Subdefs for video Portait are not correctly Oriented 
    * [PHRAS-2877] - User manipulator does not allow to set a null email
    * [PHRAS-2912] - When updating a user informations the wrong field are populated (job and activity inverted)
    * [PHRAS-2811] - Cleanning of bad chars in candidats terms


## 4.0.10
  
  Not publish

## 4.0.9

### Adds

  - PHRAS-2535 - Back / Front - Unsubscription: It's now possible to request a validation by email to delete a Phraseanet user account.
  - PHRAS-2480 - Back / Front - It's now possible to add a user model as order manager on a collection:All users with this model applied can manage orders on this collection. This features fixes an issue when users is provided by SAML and the orders manager is lost when user logs in. 
  - PHRAS-2474 - Back / front. - Searched terms are now found even if the searched terms are split in Business Field and regular Field.
  - PHRAS-2462 - Front - Share media on LinkedIn as you can do on Facebook, Twitter.
  - PHRAS-2417 - Front - Skin: grey and white, graphic enhancements.
  - PHRAS-2067 - Front - Introducing thumbnail & preview generic images for Fonts

### Fixes

  - PHRAS-2491 - Front - Click on facets title (expand/collapse) launched a bad query, due to jquery error.
  - PHRAS-2510 - Front - Facets values appear Truncated after 15th character.
  - PHRAS-2153 - Front - No user search possible with the field "Company" and field "Country".
  - PHRAS-2154 - Front - Bug on Chrome only - selected 1 document instead of all for the feedback.
  - PHRAS-2538 - Back - Some MP4 files were not correctly detected by Phraseanet.

## 4.0.8

### Adds:

  - Upload: Distant files can be added via their URL in GUI and by API. Phraseanet downloads the file before archiving it.
  - Search optimisation when searching in full text, there was a problem when the query mixed different types of fields.
  - Search optimisation, it’s now possible to search a partial date in full text.
  - Populate optimisation, now populating time: 3 times faster.
  - It is now possible to migrate from 3.1 3.0 version to 4.X, without an intermediate step in 3.8.Fix:

### Fixes
 
  - Search filter were not taken into account due to a bug in JS.
  - Overlay title: In this field, text was repeated twice if : one or several words were highlighted in the field, and if the title contained more than 102 characters.
  - List Manager: it was impossible to add users in the list manager after page 3.
  - List of fields was not refreshed in the exported fields section.
  - Push and Feedback fix error when adding a user when Geonames was not set (null value in Geonames).

## 4.0.7

### Adds:

  - Advanced search refacto
  - Thesaurus search is now in strict mode
  - Refactoring of report module
  - Refactoring query storage and changing strategy for field search restriction
  - It is now possible to search for terms in thesaurus and candidates in all languages, not only on the login language
  - Enhancements on archive task
  - Graphic enhancements for menu and icons
  - Video file enhancement, support of MXF container
  - Extraction of a video soundtrack (MP3, MP4, WAVE, etc.)
  - For Office Documents, all generated subviews will be PDF assets by default. The flexpaper preview still exists but will be optional.
  - In Prod Gui, there will be 5 facets but the possibility to view more.

### Fixes:

  - Quarantine: Fix for the “Substitute” action: alert when selection is empty
  - Quarantine: File name with a special character can’t be added
  - Fix for the Adobe CC default token
  - XSS vulnerabilities in Prod, Admin & Lightbox. Many thanks to Kris (@HV_hat_)
  - PDF containing (XMP-xmp:PageImage) fails generating subview
  - MIME types are trucated
  -Vagrant dev environment fix
  - Feedback: Sort assets “Order by best choice” has no effect

## 4.0.3

### Adds:

  - Prod: For a record, show the current day in the statistics section of the detailed view.
  - Prod: Store state (open or closed) of facet answer. eg: Database or collection, store in session.
  - Admin: Access to scheduler and task local menu when parameter is set to false in .yml configuration.
  - Prod: Database, collection and document type facets are fixed on top
  - Prod: Better rendering for values of exposure, shutter speed and flash status in facets. eg for shutter speed: 1/30 instead of 0,0333333.
  - Versions 4 are now compliant with the Phraseanet plugins for Adobe CC Suite.
  - White list mode: extending autoregistration and adding wildcard access condition by mail domain. Automatically grant access to a user according to the email entered in the request.
  - Find your documents from the colors in the facets (AI plugin)
  - Generate a PDF from a Word document or a picture, it’s now possible to define a pdf subview type
  - Specify a temporary work repository for building video subdefs, to accelerate video generation.

### Fixes:

  - Prod: In Upload, correct status are not loaded
  - Prod:Arrow keys navigation adds last selected facet as filter
  - Admin:Subdef presets, sizes and bitrates (bits/s) not OK
  - Admin: App error on loading in French due to a simple quote
  - Prod: Deletion message is not fully readable when deleting a story
  - Fixing highlight with Elasticsearch for full text only, not for the thesaurus
  - 500 error at the first authentication for a user with the SAML Phraseanet pluginDev
  - Dev: Fix API version returned in answer
  - Dev: Fix vagrant provisioning for Windows

## 4.0.2

### Adds:

  - Prod: Message Improv, when selected records are in Trash and another one.
  - Prod: alt-click on active facets (filter) to invert it.
  - Prod: do not erase facets in filter when returning 0 answers.
  - Core: Add preference to authorize user connection without an email
  - Core: Add preference to set default validity period of download link

### Fixes:

  - Thesaurus: 0 character terms are blocked
  - Admin: fix action create and drop index from elasticsearch
  - Prod: Fix advanced sarch: no filters possible on fields using IE
  - Prod: 500 error in publication reader when record is missing (deleted from db)Unit test: fix error in Json serialization for custom link
  - Prod: fix field list in advanced search with Edge browser
  - Upload: fix 500 error when missing collection
  - Install wizard: fix error in graphical installer
 
## 4.0.0

### Adds:

#### Phraseanet gets a new search engine: Elasticsearch
  - Faceted navigation enables to create a “mapping” of the response. Browse in a very intuitive way by creating several associations of filters. Facets can be used on the databases, collections, documentary fields and technical data.
  - Speed of processing search and results display has been improved
  - Possibility to use Kibana (open source visualization plugin for Elasticsearch)

#### API enhancement
  - New API routes are available (orders, facets, quarantine)
  - Enhancement of new, faster routes

 #### Redesign of the Prod interface
  - Enhanced, redesigned ergonomics:  the detailed view windows; redesign of the workzone (baskets and stories, facets, webgalleries)  
  - New white and grey skins are now available
  - New order manager

 #### Other
  - Permalinks sharing: activate/deactivate sharing links for the document and sub resolutions
  - New: the applicative trash: you can now define a collection named _TRASH_. Then, all deleted records from collections (except from Trash) go to the Trash collection. Permalinks on subdefs are deactivated. When you delete a record from the Trash collection, it is permanently deleted. When you move a record from the Trash collection to another, the permalinks are reactivated.
  - Rewriting of the task scheduler based on the web sockets
  - Quarantine enhancement
  - Drag and drop upload

## 3.8.8 (2015-12-02)

  - BugFix: Wrong BaseController used when no plugin installed.
  - BugFix: Mismatch in CORS configuration
  - BugFix: all subdefs are shown when permalink is available in prod imagetools
  - BugFix: Empty labels are considered as valid
  - BugFix: Error 500 on prod imagetools when insufficient rights

## 3.8.7 (2015-11-09)

  - NewFeature: Adding public, temporary links (link generation based on JSON Web Token)
  - NewFeature: Modification of a video snapshot (extract picture from a video)
  - NewFeature: Adding alternative route for the subdefinitions via the API
  - NewFeature: Adding a rebuild command for the subdefinitions with a filter by database, type of document (name of subdefs)
  - NewFeature: Adding verification of INNODB storage engine when creating a Phraseanet database
  - NewFeature: The user can set the mime type of a record in the HMI
  - NewFeature: Adding a route for the creation of a story in the API (management of the video screenshot, management of the description)
  - NewFeature: Adding a route for an additional document to a story
  - NewFeature: Adding the possibility to upload a document without creating its subdefinitions
  - Enhancement: Deactivation of a permalink for a subdef
  - Enhancement: Improvement of performance when deleting items in the quarantine
  - Enhancement: Change of the basic documentary structures
  - Enhancement: Display of the collection in which the media file can be found, in the detailed view
  - Enhancement: Deleting the desired type of documents searched (stories mode)
  - Enhancement: The API returns json by default if the "accept" attribute is not specified
  - BugFix: The search route via the API ne longer returns a 404 error if a collection is not known
  - BugFix: The upload module doesn't work on IE 10 & IE 11
  - BugFix: Adding wma files doesn't work
  - BugFix: Third party applications of a user is deleted when it is itself deleted
  - BugFix: The test button for the FTP export does not work
  - BugFix: Apply a template to a template does not work
  - BugFix: The names of the stories in which media can be found are truncated
  - BugFix: The interface of the suggested values in the Admin does not work
  - BugFix: The report tab:activity does not work on Chrome
  - BugFix: The time of validity is not displayed for the password renewal email
  - BugFix: The focus on the documentary fields labels systematically shows french label
  - BugFix: The "delay" parameter to make gifs is not taken into account
  - BugFix: When adding a term in the thesurus, previous value entered appears at the opening of the modal
  - BugFix: Error when generating SWF subdefinitions
  - BugFix: The "flatten" parameter when generating PDF thumbnails is not taken into account
  - Deprecation: Classic application is now obsolete

## 3.8.6 (2015-01-20)

  - BugFix : Fixes the stories editing. When opening an editing form, the style applied to the notice doesn't match its selection
  - BugFix : Fixes the sending of a return receipt (attributed in the headers of the email) at the export
  - BugFix : Fixes the SMTP field in the Administration panel which is pre filled with a wrong information
  - BugFix : Fixes a bad mapping of the registration fields on the homepage and the displayed fields in the registration requests in the Administration
  - BugFix : In the detailed view, fixes the list of the stories titles which is truncated.
  - BugFix : Fixes Oauth 2.0, the authorization of the client applications is not systematically requested when logging in.
  - BugFix : When uploading documents, the first status is not taken into account
  - BugFix : Fixes the cache invalidation of the status bits icons when changed in Admin section
  - BugFix : Fixes the reordering of the media in a basket
  - BugFix : Fixes the control of field "name" when creating a push or a feedback
  - BugFix : Fixes Oauth 2.0 message when the connection fails
  - BugFix : Fixes the suppression of diffusion lists on IE9
  - BugFix : Fixes the anonymous download when a user is logged off
  - BugFix : Fixes the setup of the default display mode of the collections (stamp/watermark) on a non authenticated mode
  - BugFix : Fixes the printing of the thumbnails of documents for the videos or PDFs
  - BugFix : Fixes the reordering of the basket when the documents come from n different collections
  - BugFix : Fixes the application of the "status bits" when the status bit is defined by the task "RecordMover"
  - BugFix : Fixes the detection of duplicates for PDF files
  - BugFix : Fixes the rewriting of metadata of a document, when the name space is empty
  - BugFix : Fixes the injection of the rights of a user for a connection via Oauth2
  - BugFix : Fixes the invalidation of the cache when disassembling a databox
  - BugFix : Fixes the sorting criteria by date and by field, according to users rights
  - BugFix : Fixes the right to download for the guest access
  - BugFix : Fixes the report generation for the number of downloads and connections
  - BugFix : Fixes the memory use of the task for the the sub-definitions creation
  - BugFix : Fixes the generation of sub-definitions when editing the sub-definitions task
  - BugFix : Fixes the display of multivalued fields in the editing window
  - BugFix : Fixes the adding of a term in the candidates which was is not detected as present in the candidates
  - BugFix : Fixes the users' rights when using the API
  - BugFix : When being redirected, fixes the add of parameters after login.
  - BugFix : Fixes the thumbnails' size of EPS files.
  - BugFix : The "Delete" action of a task ("Record Mover" type) is now taken into consideration.
  - BugFix : The edition dates of a record sent back by the API are now fixed
  - BuxFix : Writing of IPTC fields is fixed, when setting up a stamp on a media (image type). 
  - Enhancement : Possibility to adapt a task "creation of subdefinition", by database and type of document 
  - Enhancement : Reporting modifications of Flickr & Dailymotion APIs (Bridge feature).
  - Enhancement : Adding the possibility to overload the name space reserved for the cache
  - Enhancement : Adding the possibility to deactivate the use of the TaskManager by instance
  - Enhancement : Adding an extended format for the API replies. Get more information about Phraseanet records in one API request.
  - Enhancement : Adding a block for the help text of Production when no result is displayed to authorize the modification of this text via a plugin
  - Enhancement : Adding the possibility to deactivate the notifications to the users for a new publication
  - Enhancement : Adding the possibility to modify the rotation of pictures representing the videos and PDF files
  - Enhancement : Adding the possibility to serve the thumbnails of the application in a static way for improved performances
  - Enhancement : Adding the possibility to deactivate the lazy load for the thumbnails
  - Enhancement : The tasks can now reconnect automatically to MySQL
  - Enhancement : The sorting on the fields "Number" is now possible
  - Enhancement : The sub-definition creation task now displays the remaining number of sub-definitions to create
  - Enhancement : Adding the date of edition of the media
  - Enhancement : Use of http cache for the display of documents
  - Enhancement : Adding the possibility to deactivate the CSRF for the authentication form
  - NewFeature : Adding a Vagrant VM (for developers and testers). The setup is quicker: development environments made easy.
  - NewFeature : Adding a command for the file generation crossdomain.xml depending on the configuration.

## 3.8.5 (2014-07-08)

  - BugFix : Fix Flickr connexion throught Bridge Application
  - BugFix : Fix broken Report Application
  - BugFix : Fix "force authentication" option for push validation
  - BugFix : Fix display of "edit" button for a validation accordint to user rights
  - BugFix : Fix highlight of record title in detailed view
  - BugFix : Fix thumbnail generation for PDF with transparency
  - BugFix : Fix reorder of stories & basket when record titles are too long
  - BugFix : Fix display of separators for multivalued fields in caption
  - Enhancement : Add the possibility to choose a document or a video as a representative image of a story
  - Enhancement : Titles are truncated but still visible by hovering them

## 3.8.4 (2014-06-25)

  - BC Break : Drop sphinx search engine highlight support
  - BC Break : Notify user checkbox is now setted to false when publishing a new publication
  - BugFix : Fix database mapping in report
  - BugFix : Fix homepage feed url
  - BugFix : Fix CSV user import
  - BugFix : Fix status icon filename
  - BugFix : Fix highlight in caption display
  - BugFix : Fix bound in caption display
  - BugFix : Fix thumbnail display in feed view
  - BugFix : Fix thesaurus terms order
  - BugFix : Fix metadata filename attibute
  - BugFix : Fix https calls to googlechart API
  - BugFix : Fix API feed pagination
  - BugFix : Fix thumbnail etags generation
  - BugFix : Fix therausus search in workzone
  - BugFix : Fix context menu in main bar in account view
  - BugFix : Fix CSV download for filename with accent
  - BugFix : Fix CSV generation from report
  - BugFix : Fix old password migration
  - BugFix : Fix migration from 3.1 version
  - BugFix : Fix status calculation from XML indexation card for stories
  - BugFix : Fix homepage issue when a feed is deleted
  - BugFix : Fix phraseanet bridge connexion to dailymotion
  - BugFix : Fix unoconv and GPAC detection on debian system
  - BugFix : Fix oauth developer application form submission
  - BugFix : Fix anamorphosis problems for some videos
  - Enhancement : Set password fields as password input
  - Enhancement : Add extra information in user list popup in Push view
  - Enhancement : Force the use of latest IE engine
  - Enhancement : Add feed restriction when requesting aggregated feed in API
  - Enhancement : Add feed title property in feed entry JSON schema
  - Enhancement : Dashboard report is now lazy loaded
  - Enhancement : Update flowplayer version
  - Enhancement : Improve XsendFile command line tools
  - Enhancement : Remove disk IO on media_subdef::get_size function
  - Enhancement : User city is now setted through geonames server
  - Enhancement : Enhancement of Oauth2 integration
  - NewFeature : Add option to restrict Push visualization to Phraseanet users only
  - NewFeature : Add API webhook
  - NewFeature : Add CORS support for API
  - NewFeature : Add /me route in API
  - NewFeature : Add h264 pseudo stream configuration
  - NewFeature : Add session idle & life time in configuration
  - NewFeature : Add possibility to search “unknown” type document through API

## 3.8.3 (2014-02-24)

  - BugFix : Fix record type editing.
  - BugFix : Fix scheduler timeout.
  - BugFix : Fix thesaurus tab javascript errors.
  - BugFix : Fix IE slow script error messages.
  - BugFix : Fix basket records sorting.
  - BugFix : Fix admin field editing on a field delete.
  - BugFix : Fix HTTP 400 on email test.
  - BugFix : Fix records export names.
  - BugFix : Fix collection rights injection on create.
  - BugFix : Fix disconnection of removed users.
  - BugFix : Fix language selection on mobile devices.
  - BugFix : Fix collection and databox popups in admin views.
  - BugFix : Fix suggested values editing on Firefox.
  - BugFix : Fix lightbox that could not be load in case some validation have been removed.
  - BugFix : Fix user settings precedence.
  - BugFix : Fix user search by last applied template.
  - BugFix : Fix thesaurus highlight.
  - BugFix : Fix collection sorting.
  - BugFix : Fix FTP test messages.
  - BugFix : Fix video width and height extraction.
  - BugFix : Fix caption sanitization.
  - BugFix : Fix report locales.
  - BugFix : Fix FTP receiver email reception.
  - BugFix : Fix user registration management display.
  - BugFix : Fix report icons.
  - BugFix : Fix report pagination.
  - BugFix : Fix Phrasea SearchEngine cache duration.
  - BugFix : Fix basket caption display.
  - BugFix : Fix collection mount.
  - BugFix : Fix password grant authorization in API.
  - BugFix : Fix video display on mobile devices.
  - BugFix : Fix record mover task.
  - BugFix : Fix bug on edit presets load.
  - BugFix : Fix detailed view access by guests users.
  - Enhancement : Add datepicker input placeholder.
  - Enhancement : Add support for portrait videos.
  - Enhancement : Display terms of use in a new window.
  - Enhancement : Increase tasks memory limit.
  - Enhancement : Add an option to reset advanced search on production reload.
  - Enhancement : Update task manager log messages.
  - Enhancement : Update to Symfony 2.3.9.
  - Enhancement : Add plugins:list command.
  - Enhancement : Images and Videos are not interpolated anymore.
  - Enhancement : Add option to disable filesystem logs.
  - Enhancement : Add compatibility with PHP 5.6.

## 3.8.2 (2013-11-15)

  - BugFix : Locale translation may block administration module load.

## 3.8.1 (2013-11-15)

  - BugFix : IE 6 homepage error message is broken.
  - BugFix : Databox fields administration is broken on firefox.
  - BugFix : Report CSS is broken.
  - BugFix : Databox fields administration has some behavior bugs.
  - BugFix : Install data-path is not saved.
  - BugFix : Third-party applications are displayed disabled when enabled and vice-versa.
  - BugFix : Increase tasks default memory limit.
  - BugFix : Oauth2 password grant_type authentication is broken.
  - BugFix : CSS issues on mobile devices.
  - BugFix : Editing records from multiple databoxes triggers a fatal error.
  - BugFix : API search query is discarded with GET method.
  - BugFix : Wrong offset for Classic query result.
  - BugFix : API does not return SearchEngine suggestions correctly.
  - BugFix : SearchEngine collection filter does not work in Classic.
  - BugFix : Unable to start scheduler on Windows platform.
  - BugFix : Resizing images is broken on mobile devices in landscape mode.
  - BugFix : Text input color is not correctly rendered on old IEs.
  - BugFix : IE11 is not recognize as HTML5 compatible.
  - BugFix : Disallow push when records can not be pushed.
  - BugFix : Upgrade data command fails.
  - BugFix : Export by mail fails.
  - BugFix : ACL cache issue.
  - BugFix : Registration collection auto-selection is broken.
  - BugFix : Allow thesaurus browsing to non-thesaurus-admins.
  - BugFix : Datepickers displays incorrectly on firefox.
  - BugFix : Bridge playlists loading fails.
  - BugFix : Editing modal box is broken on IE7.
  - BugFix : A user can remove himself from the admin panel.
  - BugFix : Basket export fails.
  - BugFix : Allow stemmed search only if stemming is enabled.
  - BugFix : Reset date sort to the correct value on advanced-search reset.
  - BugFix : Disable SQL logging when in non-dev environment.
  - BugFix : Task-Manager scheduler randomly stops.
  - BugFix : Increase usr_login size, display error if login is longer than possible.
  - Enhancement : Allow default user settings customisation.
  - Enhancement : Propose rights reset prior apply template.
  - Enhancement : Enhance CSS selector for IE performance.
  - Enhancement : Sanitize caption XML values.
  - Enhancement : Add checkbox on feed creation to disable email notifications.
  - Enhancement : Add Bootstrap Carousel & Galleria to homepage presentation mode.
  - Enhancement : Push or feedback names are now mandatory.
  - Enhancement : Add Phraseanet twig namespace.
  - Enhancement : Allow video bitrate up to 12M.

## 3.8.0 (2013-09-26)

  - BC Break : Removed `bin/console check:system` command, replaced by `bin/setup check:system`.
  - BC Break : Removed `bin/console system:upgrade` command, replaced by `bin/setup system:upgrade`.
  - BC Break : Removed `bin/console check:ensure-production-settings` and `bin/console check:ensure-dev-settings`
    commands, replaced by `bin/console check:config`.
  - BC break : Configuration simplification, optimized for performances.
  - BC Break : Time limits are now applied on templates application.

  - SwiftMailer integration (replaces PHPMailer).
      - Emails now include an HTML view.
      - Emails can now have a customized subject prefix.
      - Emails can be sent to SMTP server using TLS encryption (only SSL was supported).
  - Sphinx-Search is now stable (require Sphinx-Search 2.0.6).
  - Add support for stemmatisation in Phrasea-Engine.
  - Add bin/setup command utility, it is now recommanded to use `bin/setup system:install`
    command to install Phraseanet.
  - Lots of cleanup and code refactorisation.
  - Add bin/console mail:test command to check email configuration.
  - Admin databox structure fields editing redesigned.
  - Refactor of the configuration tester.
  - Refactor authentication, add support for external authentication providers
      - Support for Facebook, Twitter, Viadeo, Github, Linkedin, Google-Plus.
  - Add `Link` header in permalink resources HTTP responses.
  - Global speed improvement on report.
  - Upload now monitors number of files transmitted.
  - Add bin/developer console for developement purpose.
  - Add possibility to delete a basket from the workzone basket browser.
  - Add localized labels for databox documentary fields.
  - Add localized labels for databox collections.
  - Add localized labels for databox status-bits.
  - Add localized labels for databox names.
  - Add plugin architecture for third party modules and customization.
  - Add records sent-by-mail report.
  - User time limit restrictions can now be set per databox.
  - Add gzip/bzip2 options for DBs backup commandline tool.
  - Add convenient XSendFile configuration tools in bin/console :
      - bin/console xsendfile:configuration-generator that generates your
        xsendfile mapping depending on databoxes configuration.
      - bin/console xsendfile:configuration-dumper that dumps your virtual
        host configuration depending on Phraseanet configuration
  - Phraseanet enabled languages is now configurable.

## 3.7.15 (2013-09-14)

  - Add Office Plugin client id and secret.

## 3.7.14 (2013-07-23)

  - BugFix : Multi layered images are not rendered properly.
  - BugFix : Status editing can be accessed on some records by users that are not granted.
  - BugFix : Records index is not updated after databox structure field rename.
  - Enhancement : Add support for grayscale colorspaces.

## 3.7.13 (2013-07-04)

  - Some users were able to access story creation form whereas they were not allowed to.
  - Disable detailed view keyboard shortcuts when export modal is open.
  - Update to PHP-FFMpeg 0.2.4, better support for video resizing.
  - BugFix : Unablt to reject a thesaurus term from thesaurus module.

## 3.7.12 (2013-05-13)

  - BugFix : : Removed "required" attribute on non-required fields in order form.
  - BugFix : : Fix advanced search dialog CSS.
  - BugFix : : Grouped status bits are not displayed in advanced search dialog.
  - Enhancement : Locales update.

## 3.7.11 (2013-04-23)

  - Enhancement : Animated Gifs (video support) does not requir Gmagick anymore to work properly.
  - BugFix : : When importing users from CSV file, some properties were missing.
  - BugFix : : In Report, CSV export is limited to 30 lines.

## 3.7.10 (2013-04-03)

  - BugFix : : Permalinks pages may be broken.
  - BugFix : : Permalinks always expose the file extension of the original document.
  - BugFix : : Thesaurus multi-bases queries may return incorrect proposals.
  - BugFix : : Phraseanet installation fails.
  - BugFix : : Consecutive calls to image tools may fail.

## 3.7.9 (2013-03-27)

  - BugFix : : Detailed view does not display the right search result.
  - BugFix : : Twitter and Facebook share are available even if it's disabled in system settings.
  - Add timers in API.
  - Permalinks now expose a filename.
  - Permalinks returned by the API now embed a download URL.
  - Bump to API version 1.3.1 (see https://docs.phraseanet.com/3.7/en/Devel/API/Changelog.html).

## 3.7.8 (2013-03-22)

  - BugFix : : Phraseanet API does not return results at correct offset.
  - BugFix : : Manual thumbnail extraction for videos returns images with anamorphosis.
  - BugFix : : Rollover images have light anamorphosis.
  - BugFix : : Document and sub-definitions substitution may not work properly.
  - Add preview and caption to order manager.
  - Add support for CMYK images.
  - Preserve ICC profiles data in sub-definitions.

## 3.7.7 (2013-03-08)

  - BugFix : : Archive task fails with stories.
  - Update of dutch locales.
  - BugFix : : Fix feeds entry notification display.
  - BugFix : : Read receipts are not associated to email for push and validation.

## 3.7.6 (2013-02-01)

  - BugFix : : Load of a publication entry with a publisher that refers to a deleted users fails.
  - BugFix : : Wrong ACL check for displaying feeds in Lightbox (thumbnails are displayed instead of preview).
  - Releasing a validation feedback now requires at least one agreement.
  - BugFix : : Lightbox zoom fails when image is larger than container.
  - BugFix : : Landscape format images are displayed with a wrong ratio in quarantine.
  - General enhancement of Lightbox display on IE 7/8/9.

## 3.7.5 (2013-01-09)

  - Support of Dailymotion latest API.
  - BugFix : : Bridge application creation is not possible after having upload a file.
  - Upload speed is now in octet (previously in bytes).
  - Upload is de-activated when no data box is mounted.
  - BugFix : : Lightbox display is broken on IE 7/8.
  - BugFix : : Collection setup via console throws an exception.
  - BugFix : : Metadata extraction via Dublin Core mapping returns broken data.
  - BugFix : : Minilogos with a size less than 24px are resized.
  - BugFix : : Watermark custom files are not handled correctly.
  - BugFix : : XML import to metadata fields that do not have proper source do not work correctly.
  - BugFix : : Databox unmount can provide 500's to users that have attached stories to their work zone.

## 3.7.4 (2012-12-20)

  - BugFix : : Upgrade from 3.5 may lose metadatas.
  - BugFix : : Selection of a metadata source do not behave correctly.
  - BugFix : : Remember collections selection on production reload.
  - BugFix : : Manually renew a developer token fails.
  - BugFix : : Terms Of Use template displays HTML entitites.
  - Replace javascript alert by Phraseanet dialog box in export dialog box.
  - Video subdef GOP option has now 300 as max value with steps of 10.
  - BugFix : : Some subdef options are not saved correctly (audio samplerate, GOP).
  - Support for multi-layered tiff.
  - BugFix : : Long collection names are not displayed correctly.
  - BugFix : : Document permalinks were not correctly supported.
  - BugFix : : Export name containing non ASCII are now escaped.
  - Default structure now have a the thumbtitle attribute correctly set up.
  - Chrome mobile User Agent is now supported.
  - BugFix : : Remove minilogos do not work.
  - BugFix : : Send orders do not triggers notifications.
  - BugFix : : Story thumbnails are not displayed correctly.
  - BugFix : : Add dutch (nl_NL) support.

## 3.7.3 (2012-11-09)

  - BugFix : : Security flaw (thanks TEHTRI-Security http://www.tehtri-security.com/).
  - BugFix : : Thesaurus issue when a term contains HTML entity.
  - BugFix : : Video width and height are now multiple of 16.
  - BugFix : : Download over HTTPS on IE 6 fails.
  - BugFix : : Permalinks that embeds PDF are broken.
  - BugFix : : Lightbox shows record preview at load even if the user does not have the right to access it.
  - BugFix : : Reminders that have been sent to validation participants are not saved.
  - BugFix : : IE 6 is now correctly handled in Classic module.
  - BugFix : : Download of a basket with a title containing a slash ('/') fails.
  - BugFix : : Add check on posix extension alongside pcntl extension.
  - BugFix : : Some process may fail with pcntl extension.
  - File-info mime-type guesser is deprecated in favor of binary mime-type guesser.
  - Add an option to force Terms of Use re-validation for each export.
  - Move binary configuration to config file (config/binaries.yml).
  - Lazy load thumbnails in result view.
  - When duplicating rights (at collection creation), quotas, masks and time-restrictions are now copied.
  - Add Wincache support.
  - Add Dutch localization.
  - Add Expiration cache strategy for thumbnail-class sub definitions.
  - Display job and company in Push / Validation user search results.
  - Add a captcha field for registration.
  - Bridge accounts are now deletable.
  - Mails links are now clickable in Thunderbird and Outlook.
  - Emails list in mail export now supports comma and space separators.

## 3.7.2 (2012-10-04)

  - Significant speed enhancement on thumbnail display.
  - Add a purge option to quarantine.
  - BugFix : ascending date sort in search results.
  - Multiple thesaurus fixes on IE.
  - BugFix : description field source selection.
  - Add option to rotate multiple image.
  - `Remember-me` was applied even if the box was not checked.

## 3.7.1 (2012-09-18)

  - Multiple fixes in archive task.
  - Add options -f and -y to upgrade command.
  - Add a Flash fallback for browser that does not support HTML5 file API.
  - BugFix : upgrade from version 3.1 and 3.5.
  - BugFix : : Print tool is not working on IE version 8 and less over HTTPS.

## 3.7.0 (2012-07-24)

  - Lots of graphics enhancements.
  - Windows Server 2008 support.
  - Add business fields.
  - Add new video formats (HTML5 compatibility).
  - Add target devices for subviews.
  - Thumbnail extraction tool for videos.
  - Upgrade of the Phraseanet API to version 1.2 (see https://docs.phraseanet.com/3.7/en/Devel/API/Changelog.html#id1).
  - Phraseanet PHP SDK http://phraseanet-php-sdk.readthedocs.org/.

## 3.6.5 (2012-05-11)

  - BugFix : : Bridge buttons are not visible on some browsers.
  - Youtube and Dailymotion APIs updates.
  - Stories can now be deleted from the work zone.
  - Push and validation logs were missing.

## 3.6.4 (2012-04-30)

  - BugFix : DatePicker menus do not format date correctly.
  - BugFix : Dead records can remain in orders and may broke order window.

## 3.6.3 (2012-04-26)

  - BugFix : selection in webkit based browers.

## 3.6.2 (2012-04-19)

  - BugFix : : Users can be created by some pushers.
  - BugFix : : Collection owner can not disable watermark.
  - BugFix : : Basket element reorder issues.
  - BugFix : : Multiple order managers can not be added.
  - BugFix : : Basket editing can fail.
  - Remove original file extension for downloaded files.
  - Template is not applied when importing users from a file.
  - Document + XML hot folder import produces corrupted files.
  - Enhanced Push list view on small device.

## 3.6.1 (2012-03-27)

  - BugFix : upgrade from 3.5 versions with large datasets.

## 3.6.0 (2012-03-20)

  - Add a Vocabulary mapping to multivalued fields.
  - Redesign of Push and Feedback.
  - Add shareable users list for use with Push and Feedback.
  - WorkZone sidebar redesign.
  - Add an `archive` flag to baskets.
  - Add a basket browser to browse archived baskets.
  - New API 1.1, not compliant with 1.0, see release note v3.6 https://docs.phraseanet.com/3.6/en/Admin/Upgrade/3.6.html.
