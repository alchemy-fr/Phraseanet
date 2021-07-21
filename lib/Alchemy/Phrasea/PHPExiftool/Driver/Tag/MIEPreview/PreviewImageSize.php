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
class PreviewImageSize extends AbstractTag
{

    protected $Id = 'ImageSize';

    protected $Name = 'PreviewImageSize';

    protected $FullName = 'MIE::Preview';

    protected $GroupName = 'MIE-Preview';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Preview';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Preview Image Size';

}
