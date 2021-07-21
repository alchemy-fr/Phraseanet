<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\LNK;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RemoveHistoryDuplicates extends AbstractTag
{

    protected $Id = 136;

    protected $Name = 'RemoveHistoryDuplicates';

    protected $FullName = 'LNK::ConsoleData';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Remove History Duplicates';

}
