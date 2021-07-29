<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPImage;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MPImageFlags extends AbstractTag
{

    protected $Id = '0.1';

    protected $Name = 'MPImageFlags';

    protected $FullName = 'MPF::MPImage';

    protected $GroupName = 'MPImage';

    protected $g0 = 'MPF';

    protected $g1 = 'MPImage';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'MP Image Flags';

    protected $Values = array(
        4 => array(
            'Id' => 4,
            'Label' => 'Representative image',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Dependent child image',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Dependent parent image',
        ),
    );

}
