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
class VerticalMultiSelector extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'VerticalMultiSelector';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Vertical Multi Selector';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Same as Multi-Selector with Info(U/D) & Playback(R/L)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Same as Multi-Selector with Info(R/L) & Playback(U/D)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Focus Point Selection',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Same as Multi-Selector with Info(U/D) & Playback(R/L)',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'Same as Multi-Selector with Info(R/L) & Playback(U/D)',
        ),
        5 => array(
            'Id' => 128,
            'Label' => 'Focus Point Selection',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Same as Multi-Selector with Info(U/D) & Playback(R/L)',
        ),
        7 => array(
            'Id' => 8,
            'Label' => 'Same as Multi-Selector with Info(R/L) & Playback(U/D)',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'Focus Point Selection',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Same as Multi-Selector with Info(U/D) & Playback(R/L)',
        ),
        10 => array(
            'Id' => 8,
            'Label' => 'Same as Multi-Selector with Info(R/L) & Playback(U/D)',
        ),
        11 => array(
            'Id' => 128,
            'Label' => 'Focus Point Selection',
        ),
    );

}
