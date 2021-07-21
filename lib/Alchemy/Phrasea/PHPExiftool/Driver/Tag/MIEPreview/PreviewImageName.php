<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MIEPreview;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImageName extends AbstractTag
{

    protected $Id = '1Name';

    protected $Name = 'PreviewImageName';

    protected $FullName = 'MIE::Preview';

    protected $GroupName = 'MIE-Preview';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Preview';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Preview Image Name';

}
