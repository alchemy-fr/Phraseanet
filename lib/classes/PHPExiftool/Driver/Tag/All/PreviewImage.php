<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\All;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImage extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PreviewImage';

    protected $FullName = 'mixed';

    protected $GroupName = 'All';

    protected $g0 = 'mixed';

    protected $g1 = 'mixed';

    protected $g2 = 'mixed';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Preview Image';

    protected $local_g0 = 'EXIF';

    protected $local_g1 = 'All';

    protected $local_g2 = 'Preview';

}
