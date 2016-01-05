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

    const API_OAUTH2_START = 'api.oauth2.start';
    const API_OAUTH2_END = 'api.oauth2.end';
    const API_LOAD_START = 'api.load.start';
    const API_LOAD_END = 'api.load.end';
    const API_RESULT = 'api.result';

    const RECORD_EDIT = 'record.edit';
    const RECORD_UPLOAD = 'record.upload';

    const ACCOUNT_DELETED = 'account.deleted';
    const ACCOUNT_CREATED = 'account.created';

    const ACL_SYSADMIN_CHANGED = 'acl.syadmin.changed';
    const ACL_ACCESS_TO_BASE_REVOKED = 'acl.access.to.base.revoked';
    const ACL_ACCESS_TO_BASE_GRANTED = 'acl.acces.to.base.granted';
    const ACL_ACCESS_TO_SBAS_GRANTED = 'acl.acces.to.sbas.granted';
    const ACL_RIGHTS_TO_BASE_CHANGED = 'acl.rights.to.base.changed';
    const ACL_RIGHTS_TO_SBAS_CHANGED = 'acl.rights.to.sbas.changed';
    const ACL_DOWNLOAD_QUOTAS_ON_BASE_REMOVED = 'acl.download.quotas.on.base.removed';
    const ACL_DOWNLOAD_QUOTAS_RESET = 'acl.download.quotas.reset';
    const ACL_DOWNLOAD_QUOTAS_ON_BASE_CHANGED = 'acl.download.quotas.on.base.changed';
    const ACL_MASKS_ON_BASE_CHANGED = 'acl.masks.on.base.changed';
    const ACL_ACCESS_PERIOD_CHANGED = 'acl.access.period.changed';

    const DATABOX_UNMOUNTED = 'databox.unmounted';
    const DATABOX_CREATED = 'databox.created';
    const DATABOX_MOUNTED = 'databox.mounted';
    const DATABOX_DELETED = 'databox.deleted';
    const DATABOX_STRUCTURE_CHANGED = 'databox.structure.changed';
    const DATABOX_THESAURUS_CHANGED = 'databox.thesaurus.changed';
    const DATABOX_REINDEX_ASKED = 'databox.reindex.asked';
    const DATABOX_TOU_CHANGED = 'databox.tou.changed';

    const COLLECTION_CREATED = 'collection.created';
    const COLLECTION_EMPTIED = 'collection.emptied';
    const COLLECTION_ENABLED = 'collection.enabled';
    const COLLECTION_DISABLED = 'collection.disabled';
    const COLLECTION_MOUNTED = 'collection.mounted';
    const COLLECTION_UNMOUNTED = 'collection.unmounted';
    const COLLECTION_SETTING_CHANGED = 'collection.settings.changed';
    const COLLECTION_NAME_CHANGED = 'collection.name.changed';
    const COLLECTION_LABEL_CHANGED = 'collection.label.changed';
}
