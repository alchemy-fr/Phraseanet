<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\All;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

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
