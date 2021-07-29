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
class StackedImage extends AbstractTag
{

    protected $Id = 2052;

    protected $Name = 'StackedImage';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Stacked Image';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'No',
        ),
        '3 2' => array(
            'Id' => '3 2',
            'Label' => 'ND2 (1EV)',
        ),
        '3 4' => array(
            'Id' => '3 4',
            'Label' => 'ND4 (2EV)',
        ),
        '3 8' => array(
            'Id' => '3 8',
            'Label' => 'ND8 (3EV)',
        ),
        '3 16' => array(
            'Id' => '3 16',
            'Label' => 'ND16 (4EV)',
        ),
        '3 32' => array(
            'Id' => '3 32',
            'Label' => 'ND32 (5EV)',
        ),
        '5 4' => array(
            'Id' => '5 4',
            'Label' => 'HDR1',
        ),
        '6 4' => array(
            'Id' => '6 4',
            'Label' => 'HDR2',
        ),
        '8 8' => array(
            'Id' => '8 8',
            'Label' => 'Tripod high resolution',
        ),
        '9 2' => array(
            'Id' => '9 2',
            'Label' => 'Focus-stacked (2 images)',
        ),
        '9 3' => array(
            'Id' => '9 3',
            'Label' => 'Focus-stacked (3 images)',
        ),
        '9 4' => array(
            'Id' => '9 4',
            'Label' => 'Focus-stacked (4 images)',
        ),
        '9 5' => array(
            'Id' => '9 5',
            'Label' => 'Focus-stacked (5 images)',
        ),
        '9 6' => array(
            'Id' => '9 6',
            'Label' => 'Focus-stacked (6 images)',
        ),
        '9 7' => array(
            'Id' => '9 7',
            'Label' => 'Focus-stacked (7 images)',
        ),
        '9 8' => array(
            'Id' => '9 8',
            'Label' => 'Focus-stacked (8 images)',
        ),
        '9 9' => array(
            'Id' => '9 9',
            'Label' => 'Focus-stacked (9 images)',
        ),
        '9 10' => array(
            'Id' => '9 10',
            'Label' => 'Focus-stacked (10 images)',
        ),
        '9 11' => array(
            'Id' => '9 11',
            'Label' => 'Focus-stacked (11 images)',
        ),
        '9 12' => array(
            'Id' => '9 12',
            'Label' => 'Focus-stacked (12 images)',
        ),
        '9 13' => array(
            'Id' => '9 13',
            'Label' => 'Focus-stacked (13 images)',
        ),
        '9 14' => array(
            'Id' => '9 14',
            'Label' => 'Focus-stacked (14 images)',
        ),
        '9 15' => array(
            'Id' => '9 15',
            'Label' => 'Focus-stacked (15 images)',
        ),
        '11 16' => array(
            'Id' => '11 16',
            'Label' => 'Hand-held high resolution',
        ),
    );

}
