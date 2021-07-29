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
class VariableLowPassFilter extends AbstractTag
{

    protected $Id = 8232;

    protected $Name = 'VariableLowPassFilter';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Variable Low Pass Filter';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'n/a',
        ),
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'Off',
        ),
        '1 1' => array(
            'Id' => '1 1',
            'Label' => 'Standard',
        ),
        '1 2' => array(
            'Id' => '1 2',
            'Label' => 'High',
        ),
    );

}
