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
class MeasurementFlare extends AbstractTag
{

    protected $Id = 28;

    protected $Name = 'MeasurementFlare';

    protected $FullName = 'ICC_Profile::Measurement';

    protected $GroupName = 'ICC-meas';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-meas';

    protected $g2 = 'Image';

    protected $Type = 'fixed32u';

    protected $Writable = false;

    protected $Description = 'Measurement Flare';

}
