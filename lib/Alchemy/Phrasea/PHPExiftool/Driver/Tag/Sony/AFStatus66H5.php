<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Sony;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AFStatus66H5 extends AbstractTag
{

    protected $Id = 132;

    protected $Name = 'AFStatus_66_H5';

    protected $FullName = 'Sony::AFStatus79';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'AF Status 66 H5';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-32768' => array(
            'Id' => '-32768',
            'Label' => 'Out of Focus',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'In Focus',
        ),
    );

}
