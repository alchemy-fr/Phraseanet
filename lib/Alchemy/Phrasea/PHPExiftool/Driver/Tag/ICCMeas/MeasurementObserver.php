<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ICCMeas;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class MeasurementObserver extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'MeasurementObserver';

    protected $FullName = 'ICC_Profile::Measurement';

    protected $GroupName = 'ICC-meas';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-meas';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Measurement Observer';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'CIE 1931',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'CIE 1964',
        ),
    );

}
