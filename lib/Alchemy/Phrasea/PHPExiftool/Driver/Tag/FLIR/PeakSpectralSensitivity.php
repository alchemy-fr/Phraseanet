<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\FLIR;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PeakSpectralSensitivity extends AbstractTag
{

    protected $Id = 'FLIR::PeakSpectralSensitivity';

    protected $Name = 'PeakSpectralSensitivity';

    protected $FullName = 'Composite';

    protected $GroupName = 'FLIR';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Peak Spectral Sensitivity';

    protected $local_g1 = 'FLIR';

    protected $local_g2 = 'Camera';

}
