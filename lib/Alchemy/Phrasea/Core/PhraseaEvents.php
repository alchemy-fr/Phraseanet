<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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

    const COLLECTION_CREATE = 'collection.create';

    const RECORD_EDIT = 'record.edit';
    const RECORD_UPLOAD = 'record.upload';

    const ACCOUNT_DELETED = 'account.deleted';
    const ACCOUNT_CREATED = 'account.created';

    const ACL_SET_ADMIN = 'acl.set.admin';
    const ACL_REVOKE_ACCESS_FROM_BASE = 'acl.revoke.access.from.base';
    const ACL_GIVE_ACCESS_TO_BASE = 'acl.give.acces.to.base';
    const ACL_GIVE_ACCESS_TO_SBAS = 'acl.give.acces.to.sbas';
    const ACL_UPDATE_RIGHTS_TO_BASE = 'acl.update.rights.to.base';
    const ACL_UPDATE_RIGHTS_TO_SBAS = 'acl.update.rights.to.sbas';
    const ACL_REMOVE_QUOTAS_ON_BASE = 'acl.remove.quotas.on.base';
    const ACL_UPDATE_DOWNLOAD_RESTRICTIONS = 'acl.update.download.restrictions';
    const ACL_SET_QUOTAS_ON_BASE = 'acl.set.quotas.on.base';
    const ACL_SET_MASKS_ON_BASE = 'acl.set.masks.on.base';
    const ACL_SET_LIMITS = 'acl.set.limits';
}
