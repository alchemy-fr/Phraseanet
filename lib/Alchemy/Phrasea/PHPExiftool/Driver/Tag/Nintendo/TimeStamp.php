<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Nintendo;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class TimeStamp extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'TimeStamp';

    protected $FullName = 'Nintendo::CameraInfo';

    protected $GroupName = 'Nintendo';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nintendo';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Time Stamp';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

}
