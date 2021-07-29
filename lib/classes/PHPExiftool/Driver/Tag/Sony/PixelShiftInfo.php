<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PixelShiftInfo extends AbstractTag
{

    protected $Id = 8239;

    protected $Name = 'PixelShiftInfo';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Pixel Shift Info';

    protected $flag_Permanent = true;

    protected $Values = array(
        '00000000 0 0 0x0' => array(
            'Id' => '00000000 0 0 0x0',
            'Label' => 'n/a',
        ),
    );

}
