<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\SigmaRaw;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 'RESOLUTION';

    protected $Name = 'Quality';

    protected $FullName = 'SigmaRaw::Properties';

    protected $GroupName = 'SigmaRaw';

    protected $g0 = 'SigmaRaw';

    protected $g1 = 'SigmaRaw';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Quality';

    protected $Values = array(
        'HI' => array(
            'Id' => 'HI',
            'Label' => 'High',
        ),
        'LOW' => array(
            'Id' => 'LOW',
            'Label' => 'Low',
        ),
        'MED' => array(
            'Id' => 'MED',
            'Label' => 'Medium',
        ),
    );

}
