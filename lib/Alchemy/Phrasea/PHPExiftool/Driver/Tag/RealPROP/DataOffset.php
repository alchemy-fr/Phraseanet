<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\RealPROP;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class DataOffset extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'DataOffset';

    protected $FullName = 'Real::Properties';

    protected $GroupName = 'Real-PROP';

    protected $g0 = 'Real';

    protected $g1 = 'Real-PROP';

    protected $g2 = 'Video';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Data Offset';

}
