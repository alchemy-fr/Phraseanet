<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\M2TS;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Duration extends AbstractTag
{

    protected $Id = 'Duration';

    protected $Name = 'Duration';

    protected $FullName = 'M2TS::Main';

    protected $GroupName = 'M2TS';

    protected $g0 = 'M2TS';

    protected $g1 = 'M2TS';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Duration';

}
