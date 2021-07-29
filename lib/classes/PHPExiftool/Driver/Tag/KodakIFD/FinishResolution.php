<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FinishResolution extends AbstractTag
{

    protected $Id = 3513;

    protected $Name = 'FinishResolution';

    protected $FullName = 'Kodak::IFD';

    protected $GroupName = 'KodakIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KodakIFD';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Finish Resolution';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '100%',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '67%',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '50%',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '25%',
        ),
    );

}
