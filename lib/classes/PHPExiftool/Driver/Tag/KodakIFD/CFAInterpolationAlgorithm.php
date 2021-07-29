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
class CFAInterpolationAlgorithm extends AbstractTag
{

    protected $Id = 3680;

    protected $Name = 'CFAInterpolationAlgorithm';

    protected $FullName = 'Kodak::IFD';

    protected $GroupName = 'KodakIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KodakIFD';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'CFA Interpolation Algorithm';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AH2',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Karnak',
        ),
    );

}
