<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\SubIFD1;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class OtherImageStart extends AbstractTag
{

    protected $Id = 513;

    protected $Name = 'OtherImageStart';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'SubIFD1';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Other Image Start';

    protected $local_g1 = 'SubIFD1';

    protected $flag_Permanent = true;

    protected $flag_Protected = true;

    protected $Index = 7;

}
