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
class DistortionCorrParamsNumber extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'DistortionCorrParamsNumber';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Distortion Corr Params Number';

    protected $flag_Permanent = true;

    protected $Values = array(
        11 => array(
            'Id' => 11,
            'Label' => '11 (APS-C)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '16 (Full-frame)',
        ),
    );

}
