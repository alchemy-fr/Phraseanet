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
class OtherImage extends AbstractTag
{

    protected $Id = 'Exif::OtherImage';

    protected $Name = 'OtherImage';

    protected $FullName = 'Composite';

    protected $GroupName = 'All';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Other Image';

    protected $local_g0 = 'EXIF';

    protected $local_g1 = 'All';

    protected $local_g2 = 'Preview';

}
