<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SubIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VignettingCorrection extends AbstractTag
{

    protected $Id = 28721;

    protected $Name = 'VignettingCorrection';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'SubIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Vignetting Correction';

    protected $local_g1 = 'SubIFD';

    protected $flag_Unsafe = true;

    protected $Values = array(
        256 => array(
            'Id' => 256,
            'Label' => 'Off',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'Auto',
        ),
        511 => array(
            'Id' => 511,
            'Label' => 'No correction params available',
        ),
    );

}
