<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MultiSelectorLiveView extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MultiSelectorLiveView';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Multi Selector Live View';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Zoom On/Off',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Start Movie Recording',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Not Used',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Zoom',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'Not Used',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Zoom',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'Not Used',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Zoom',
        ),
        12 => array(
            'Id' => 3,
            'Label' => 'Not Used',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Zoom',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'Not Used',
        ),
    );

}
