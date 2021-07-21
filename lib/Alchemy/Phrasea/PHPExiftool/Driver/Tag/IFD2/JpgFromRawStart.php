<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\IFD2;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class JpgFromRawStart extends AbstractTag
{

    protected $Id = 513;

    protected $Name = 'JpgFromRawStart';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD2';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Jpg From Raw Start';

    protected $local_g1 = 'IFD2';

    protected $flag_Permanent = true;

    protected $flag_Protected = true;

    protected $Index = 6;

}
