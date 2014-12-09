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

    const COLLECTION_CREATE = 'collection.create';

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

    const RECORD_EDIT = 'record.edit';
    const RECORD_UPLOAD = 'record.upload';
}
