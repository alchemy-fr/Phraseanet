<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CR2CFAPattern extends AbstractTag
{

    protected $Id = 50656;

    protected $Name = 'CR2CFAPattern';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'CR2 CFA Pattern';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        '0 1 1 2' => array(
            'Id' => '0 1 1 2',
            'Label' => '[Red,Green][Green,Blue]',
        ),
        '1 0 2 1' => array(
            'Id' => '1 0 2 1',
            'Label' => '[Green,Red][Blue,Green]',
        ),
        '1 2 0 1' => array(
            'Id' => '1 2 0 1',
            'Label' => '[Green,Blue][Red,Green]',
        ),
        '2 1 1 0' => array(
            'Id' => '2 1 1 0',
            'Label' => '[Blue,Green][Green,Red]',
        ),
    );

}
