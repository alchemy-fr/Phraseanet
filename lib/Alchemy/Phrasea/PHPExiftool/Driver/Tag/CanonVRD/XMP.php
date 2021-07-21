<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\CanonVRD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class XMP extends AbstractTag
{

    protected $Id = '4294902006';

    protected $Name = 'XMP';

    protected $FullName = 'CanonVRD::Main';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Other';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'XMP';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}
