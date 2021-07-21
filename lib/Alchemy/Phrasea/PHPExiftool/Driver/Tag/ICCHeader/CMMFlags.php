<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ICCHeader;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CMMFlags extends AbstractTag
{

    protected $Id = 44;

    protected $Name = 'CMMFlags';

    protected $FullName = 'ICC_Profile::Header';

    protected $GroupName = 'ICC-header';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-header';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'CMM Flags';

}
