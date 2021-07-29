<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiFilm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FujiFlashMode extends AbstractTag
{

    protected $Id = 4112;

    protected $Name = 'FujiFlashMode';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Fuji Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Red-eye reduction',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'External',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Commander',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Not Attached',
        ),
        33056 => array(
            'Id' => 33056,
            'Label' => 'TTL',
        ),
        33568 => array(
            'Id' => 33568,
            'Label' => 'TTL Auto - Did not fire',
        ),
        38976 => array(
            'Id' => 38976,
            'Label' => 'Manual',
        ),
        39008 => array(
            'Id' => 39008,
            'Label' => 'Flash Commander',
        ),
        39040 => array(
            'Id' => 39040,
            'Label' => 'Multi-flash',
        ),
        43296 => array(
            'Id' => 43296,
            'Label' => '1st Curtain (front)',
        ),
        43552 => array(
            'Id' => 43552,
            'Label' => 'TTL Slow - 1st Curtain (front)',
        ),
        43808 => array(
            'Id' => 43808,
            'Label' => 'TTL Auto - 1st Curtain (front)',
        ),
        44320 => array(
            'Id' => 44320,
            'Label' => 'TTL - Red-eye Flash - 1st Curtain (front)',
        ),
        44576 => array(
            'Id' => 44576,
            'Label' => 'TTL Slow - Red-eye Flash - 1st Curtain (front)',
        ),
        44832 => array(
            'Id' => 44832,
            'Label' => 'TTL Auto - Red-eye Flash - 1st Curtain (front)',
        ),
        51488 => array(
            'Id' => 51488,
            'Label' => '2nd Curtain (rear)',
        ),
        51744 => array(
            'Id' => 51744,
            'Label' => 'TTL Slow - 2nd Curtain (rear)',
        ),
        52000 => array(
            'Id' => 52000,
            'Label' => 'TTL Auto - 2nd Curtain (rear)',
        ),
        52512 => array(
            'Id' => 52512,
            'Label' => 'TTL - Red-eye Flash - 2nd Curtain (rear)',
        ),
        52768 => array(
            'Id' => 52768,
            'Label' => 'TTL Slow - Red-eye Flash - 2nd Curtain (rear)',
        ),
        53024 => array(
            'Id' => 53024,
            'Label' => 'TTL Auto - Red-eye Flash - 2nd Curtain (rear)',
        ),
        59680 => array(
            'Id' => 59680,
            'Label' => 'High Speed Sync (HSS)',
        ),
    );

}
