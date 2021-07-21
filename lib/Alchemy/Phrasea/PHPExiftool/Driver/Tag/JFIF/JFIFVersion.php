<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\JFIF;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class JFIFVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'JFIFVersion';

    protected $FullName = 'JFIF::Main';

    protected $GroupName = 'JFIF';

    protected $g0 = 'JFIF';

    protected $g1 = 'JFIF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'JFIF Version';

    protected $flag_Mandatory = true;

    protected $MaxLength = 2;

}
