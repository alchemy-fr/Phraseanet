<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\HTMLNcc;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Generator extends AbstractTag
{

    protected $Id = 'generator';

    protected $Name = 'Generator';

    protected $FullName = 'HTML::ncc';

    protected $GroupName = 'HTML-ncc';

    protected $g0 = 'HTML';

    protected $g1 = 'HTML-ncc';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Generator';

}
