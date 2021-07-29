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
class MultiSelectorLiveViewMode extends AbstractTag
{

    protected $Id = 6338;

    protected $Name = 'MultiSelectorLiveViewMode';

    protected $FullName = 'Nikon::ShotInfoD4S';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Multi Selector Live View Mode';

    protected $local_g1 = 'NikonCustom';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Zoom',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'None',
        ),
    );

}
