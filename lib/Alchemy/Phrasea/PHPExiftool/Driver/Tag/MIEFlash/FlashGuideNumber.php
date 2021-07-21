<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MIEFlash;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class FlashGuideNumber extends AbstractTag
{

    protected $Id = 'GuideNumber';

    protected $Name = 'FlashGuideNumber';

    protected $FullName = 'MIE::Flash';

    protected $GroupName = 'MIE-Flash';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Flash';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Flash Guide Number';

}
