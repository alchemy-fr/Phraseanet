<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PostScript;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AIRulerUnits extends AbstractTag
{

    protected $Id = 'AI5_RulerUnits';

    protected $Name = 'AIRulerUnits';

    protected $FullName = 'PostScript::Main';

    protected $GroupName = 'PostScript';

    protected $g0 = 'PostScript';

    protected $g1 = 'PostScript';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'AI Ruler Units';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Inches',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Millimeters',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Points',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Picas',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Centimeters',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Pixels',
        ),
    );

}
