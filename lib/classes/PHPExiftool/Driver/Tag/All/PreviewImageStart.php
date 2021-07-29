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
class PreviewImageStart extends AbstractTag
{

    protected $Id = 273;

    protected $Name = 'PreviewImageStart';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'All';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Preview Image Start';

    protected $local_g1 = 'All';

    protected $flag_Permanent = true;

    protected $flag_Protected = true;

    protected $Index = 3;

}
