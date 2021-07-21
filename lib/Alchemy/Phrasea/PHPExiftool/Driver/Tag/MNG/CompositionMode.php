<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MNG;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CompositionMode extends AbstractTag
{

    protected $Id = 13;

    protected $Name = 'CompositionMode';

    protected $FullName = 'MNG::PasteImage';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Composition Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Over',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Replace',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Under',
        ),
    );

}
