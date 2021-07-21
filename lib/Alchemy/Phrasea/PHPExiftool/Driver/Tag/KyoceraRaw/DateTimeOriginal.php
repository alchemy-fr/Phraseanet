<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\KyoceraRaw;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class DateTimeOriginal extends AbstractTag
{

    protected $Id = 33;

    protected $Name = 'DateTimeOriginal';

    protected $FullName = 'KyoceraRaw::Main';

    protected $GroupName = 'KyoceraRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KyoceraRaw';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Date/Time Original';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

    protected $MaxLength = 20;

}
