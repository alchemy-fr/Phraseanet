<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

final class PhraseaEvents
{
    const LOGOUT = 'phrasea.logout';

    const PRE_AUTHENTICATE = 'phrasea.pre-authenticate';
    const POST_AUTHENTICATE = 'phrasea.post-authenticate';

    const INSTALL_FINISH = "phrasea.install-finish";

    const API_OAUTH2_START = 'api.oauth2.start';
    const API_OAUTH2_END = 'api.oauth2.end';
    const API_LOAD_START = 'api.load.start';
    const API_LOAD_END = 'api.load.end';
    const API_RESULT = 'api.result';

    const ORDER_CREATE = 'order.create';
    const ORDER_DELIVER = 'order.deliver';
    const ORDER_DENY = 'order.deny';
    const FEED_ENTRY_CREATE = 'feed-entry.create';
    const REGISTRATION_CREATE = 'registration.create';
    const REGISTRATION_AUTOREGISTER = 'registration.autoregister';
    const BASKET_PUSH = 'basket.push';
    const VALIDATION_CREATE = 'validation.create';
    const VALIDATION_DONE = 'validation.done';
    const VALIDATION_REMINDER = 'validation.reminder';

    const LAZARET_CREATE = 'lazaret.create';
    const BRIDGE_UPLOAD_FAILURE = 'bridge.upload-failure';
    const EXPORT_MAIL_FAILURE = 'export.mail-failure';
    const EXPORT_CREATE = 'export.create';

    const RECORD_CREATED = 'record.created';
    const RECORD_DELETED = 'record.deleted';
    const STORY_CREATE = 'story.create';
    const STORY_DELETE = 'story.delete';
    const RECORD_CHANGE_COLLECTION = 'record.collection';
    const RECORD_CHANGE_METADATA = 'record.metadata';
    const RECORD_CHANGE_ORIGINAL_NAME = 'record.original_name';
    const RECORD_CHANGE_STATUS = 'record.status';
    const RECORD_BUILD_SUB_DEFINITION = 'record.sub_definition';
    const RECORD_SUBSTITUTE = 'record.substitute';

    const DATABOX_UPDATE_FIELD = 'databox.update.field';
    const DATABOX_DELETE_FIELD = 'databox.delete.field';

    const DATABOX_UPDATE_STATUS = 'databox.create.status';
    const DATABOX_DELETE_STATUS = 'databox.delete.status';

    const COLLECTION_CHANGE_NAME = 'collection.name';
    const COLLECTION_CREATE = 'collection.create';

    const INDEX_NEW_RECORD = 'index.new.record';
    const INDEX_UPDATE_RECORD = 'index.update.record';
    const INDEX_REMOVE_RECORD = 'index.remove.record';
    const INDEX_COLLECTION = 'index.collection';
    const INDEX_DATABOX = 'index.databox';
    const INDEX_THESAURUS = 'index.thesaurus';
}
