<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Jpeg2000;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpecMethod extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'ColorSpecMethod';

    protected $FullName = 'Jpeg2000::ColorSpec';

    protected $GroupName = 'Jpeg2000';

    protected $g0 = 'Jpeg2000';

    protected $g1 = 'Jpeg2000';

    protected $g2 = 'Image';

    protected $Type = 'int8s';

    protected $Writable = false;

    protected $Description = 'Color Spec Method';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Enumerated',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Restricted ICC',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Any ICC',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Vendor Color',
        ),
    );

}
