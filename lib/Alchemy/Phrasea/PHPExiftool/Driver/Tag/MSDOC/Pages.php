<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MSDOC;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Pages extends AbstractTag
{

    protected $Id = 46;

    protected $Name = 'Pages';

    protected $FullName = 'FlashPix::DOP';

    protected $GroupName = 'MS-DOC';

    protected $g0 = 'FlashPix';

    protected $g1 = 'MS-DOC';

    protected $g2 = 'Document';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Pages';

}
