<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ExifIFD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class MakerNoteLeica10 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MakerNoteLeica10';

    protected $FullName = 'mixed';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'mixed';

    protected $g1 = 'mixed';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Maker Note Leica 10';

    protected $local_g1 = 'ExifIFD';

    protected $flag_Binary = true;

    protected $flag_Permanent = true;

    protected $Index = 54;

}
