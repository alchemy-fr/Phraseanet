<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper,
    Symfony\Component\HttpFoundation\Request;

/**
 * Edit Record Helper
 * This object handles /edit/ request and filters records that user can edit
 *
 * It prepares metadatas, databases structures.
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Push extends RecordHelper
{
    protected $flatten_groupings = true;
    protected $required_rights = array('canpush');

}
