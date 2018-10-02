<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Acl;

final class AclEvents
{
    const SYSADMIN_CHANGED = 'acl.syadmin_changed';
    const ACCESS_TO_BASE_REVOKED = 'acl.access_to_base_revoked';
    const ACCESS_TO_BASE_GRANTED = 'acl.acces_to_base_granted';
    const ACCESS_TO_SBAS_GRANTED = 'acl.acces_to_sbas_granted';
    const RIGHTS_TO_BASE_CHANGED = 'acl.rights_to_base_changed';
    const RIGHTS_TO_SBAS_CHANGED = 'acl.rights_to_sbas_changed';
    const DOWNLOAD_QUOTAS_ON_BASE_REMOVED = 'acl.download_quotas_on_base_removed';
    const DOWNLOAD_QUOTAS_RESET = 'acl.download_quotas_reset';
    const DOWNLOAD_QUOTAS_ON_BASE_CHANGED = 'acl.download_quotas_on_base_changed';
    const MASKS_ON_BASE_CHANGED = 'acl.masks_on_base_changed';
    const ACCESS_PERIOD_CHANGED = 'acl.access_period_changed';
}
