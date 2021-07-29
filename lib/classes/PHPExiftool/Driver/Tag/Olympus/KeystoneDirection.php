<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class KeystoneDirection extends AbstractTag
{

    protected $Id = 6401;

    protected $Name = 'KeystoneDirection';

    protected $FullName = 'Olympus::ImageProcessing';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Keystone Direction';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Vertical',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Horizontal',
        ),
    );

}
