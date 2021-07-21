<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MOBI;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CodePage extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'CodePage';

    protected $FullName = 'Palm::MOBI';

    protected $GroupName = 'MOBI';

    protected $g0 = 'Palm';

    protected $g1 = 'MOBI';

    protected $g2 = 'Document';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Code Page';

    protected $Values = array(
        1252 => array(
            'Id' => 1252,
            'Label' => 'Windows Latin 1 (Western European)',
        ),
        65001 => array(
            'Id' => 65001,
            'Label' => 'Unicode (UTF-8)',
        ),
    );

}
