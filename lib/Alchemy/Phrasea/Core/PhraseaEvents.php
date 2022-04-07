<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
    const FEED_ENTRY_UPDATE = 'feed-entry.update';

    const REGISTRATION_CREATE = 'registration.create';
    const REGISTRATION_AUTOREGISTER = 'registration.autoregister';

    const BASKET_PUSH = 'basket.push';
    const BASKET_SHARE = 'basket.share';
    const BASKET_ELEMENTS_ADDED = 'basket.elements-added';
    const BASKET_ELEMENTS_REMOVED = 'basket.elements-removed';

    const VALIDATION_CREATE = 'validation.create';
    const VALIDATION_DONE = 'validation.done';
    const VALIDATION_REMINDER = 'validation.reminder';

    const LAZARET_CREATE = 'lazaret.create';

    const BRIDGE_UPLOAD_FAILURE = 'bridge.upload-failure';

    const EXPORT_MAIL_FAILURE = 'export.mail-failure';
    const EXPORT_CREATE       = 'export.create';
    const EXPORT_MAIL_CREATE  = 'export.mail-create';

    const RECORD_EDIT = 'record.edit';
    const RECORD_UPLOAD = 'record.upload';

    const RECORD_AUTO_SUBTITLE = 'record.auto-subtitle';

    const THESAURUS_IMPORTED = 'thesaurus.imported';
    const THESAURUS_FIELD_LINKED = 'thesaurus.field-linked';
    const THESAURUS_CANDIDATE_ACCEPTED_AS_CONCEPT = 'thesaurus.candidate-accepted-as-concept';
    const THESAURUS_CANDIDATE_ACCEPTED_AS_SYNONYM = 'thesaurus.candidate-accepted-as-synonym';
    const THESAURUS_SYNONYM_LNG_CHANGED = 'thesaurus.synonym-lng-changed';
    const THESAURUS_SYNONYM_POSITION_CHANGED = 'thesaurus.synonym-position-changed';
    const THESAURUS_SYNONYM_TRASHED = 'thesaurus.synonym-trashed';
    const THESAURUS_CONCEPT_TRASHED = 'thesaurus.concept-trashed';
    const THESAURUS_CONCEPT_DELETED = 'thesaurus.concept-deleted';
    const THESAURUS_SYNONYM_ADDED = 'thesaurus.synonym-added';
    const THESAURUS_CONCEPT_ADDED = 'thesaurus.concept-added';
}
