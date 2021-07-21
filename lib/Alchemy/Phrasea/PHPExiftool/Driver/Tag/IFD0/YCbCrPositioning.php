<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\IFD0;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class YCbCrPositioning extends AbstractTag
{

    protected $Id = 531;

    protected $Name = 'YCbCrPositioning';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Y Cb Cr Positioning';

    protected $flag_Unsafe = true;

    protected $flag_Mandatory = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Centered',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Co-sited',
        ),
    );

}
