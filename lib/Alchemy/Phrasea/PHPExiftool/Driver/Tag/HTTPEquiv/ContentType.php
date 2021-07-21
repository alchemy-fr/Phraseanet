<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\HTTPEquiv;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ContentType extends AbstractTag
{

    protected $Id = 'content-type';

    protected $Name = 'ContentType';

    protected $FullName = 'HTML::equiv';

    protected $GroupName = 'HTTP-equiv';

    protected $g0 = 'HTML';

    protected $g1 = 'HTTP-equiv';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Content Type';

}
