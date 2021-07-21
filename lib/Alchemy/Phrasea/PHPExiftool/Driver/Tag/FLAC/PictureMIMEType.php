<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\FLAC;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PictureMIMEType extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'PictureMIMEType';

    protected $FullName = 'FLAC::Picture';

    protected $GroupName = 'FLAC';

    protected $g0 = 'FLAC';

    protected $g1 = 'FLAC';

    protected $g2 = 'Image';

    protected $Type = 'var_pstr32';

    protected $Writable = false;

    protected $Description = 'Picture MIME Type';

}
