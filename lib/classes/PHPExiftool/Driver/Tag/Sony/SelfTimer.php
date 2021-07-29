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
class SelfTimer extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SelfTimer';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Self Timer';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Self-timer 10 s',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Self-timer 2 s',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Self-timer 5 or 10 s',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Self-timer 2 s',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Self-timer 5 or 10 s',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Self-timer 2 s',
        ),
    );

}
