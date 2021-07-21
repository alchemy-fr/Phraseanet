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
class PreviewImageLength extends AbstractTag
{

    protected $Id = 279;

    protected $Name = 'PreviewImageLength';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'All';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Preview Image Length';

    protected $local_g1 = 'All';

    protected $flag_Permanent = true;

    protected $flag_Protected = true;

    protected $Index = 3;

}
