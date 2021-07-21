<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MIEUTM;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class UTMNorthing extends AbstractTag
{

    protected $Id = 'Northing';

    protected $Name = 'UTMNorthing';

    protected $FullName = 'MIE::UTM';

    protected $GroupName = 'MIE-UTM';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-UTM';

    protected $g2 = 'Location';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'UTM Northing';

}
