<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\PostScript;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AIColorModel extends AbstractTag
{

    protected $Id = 'AI9_ColorModel';

    protected $Name = 'AIColorModel';

    protected $FullName = 'PostScript::Main';

    protected $GroupName = 'PostScript';

    protected $g0 = 'PostScript';

    protected $g1 = 'PostScript';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'AI Color Model';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'RGB',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'CMYK',
        ),
    );

}
