<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMP;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class XMP extends AbstractTag
{

    protected $Id = 'XMP';

    protected $Name = 'XMP';

    protected $FullName = 'Extra';

    protected $GroupName = 'XMP';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'XMP';

    protected $local_g0 = 'XMP';

    protected $local_g1 = 'XMP';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}
