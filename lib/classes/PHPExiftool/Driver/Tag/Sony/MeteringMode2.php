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
class MeteringMode2 extends AbstractTag
{

    protected $Id = 8236;

    protected $Name = 'MeteringMode2';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Metering Mode 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        256 => array(
            'Id' => 256,
            'Label' => 'Multi-segment',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Center-weighted average',
        ),
        769 => array(
            'Id' => 769,
            'Label' => 'Spot (Standard)',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'Spot (Large)',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Average',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Highlight',
        ),
    );

}
