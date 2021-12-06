<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

final class WorkerEvents
{
    const ASSETS_CREATE                     = 'assets.create';
    const ASSETS_CREATION_FAILURE           = 'assets.create_failure';
    const ASSETS_CREATION_RECORD_FAILURE    = 'assets.creation_record_failure';

    const EXPORT_FTP                        = 'export.ftp';

    const EXPORT_MAIL_FAILURE               = 'export.worker_mail_failure';

    const POPULATE_INDEX                    = 'populate.index';
    const POPULATE_INDEX_FAILURE            = "populate.index_failure";

    const STORY_CREATE_COVER                = 'story.create_cover';

    const SUBDEFINITION_WRITE_META          = 'subdefinition.write_meta';
    const SUBDEFINITION_CREATION_FAILURE    = 'subdefinition.creation_failure';

    const WEBHOOK_DELIVER_FAILURE           = 'webhook.deliver_failure';

    const EXPOSE_UPLOAD_ASSETS              = 'expose.upload_assets';

    const RECORD_EDIT_IN_WORKER             = 'record.edit_in_worker';

    const RECORDS_WRITE_META                = 'records.write_meta';

    const RECORD_DELETE_INDEX               = 'record.delete_index';
}
