<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\FlashPix;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CompObjUserType extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'CompObjUserType';

    protected $FullName = 'FlashPix::CompObj';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Comp Obj User Type';

}
