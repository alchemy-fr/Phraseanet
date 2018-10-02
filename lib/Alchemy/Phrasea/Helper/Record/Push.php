<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper;

class Push extends RecordHelper
{
    protected $flatten_groupings = true;
    protected $required_rights = ['canpush'];

}
