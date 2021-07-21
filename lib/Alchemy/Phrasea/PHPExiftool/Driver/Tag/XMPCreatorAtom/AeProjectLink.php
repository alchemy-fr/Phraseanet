<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPCreatorAtom;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AeProjectLink extends AbstractTag
{

    protected $Id = 'aeProjectLink';

    protected $Name = 'AeProjectLink';

    protected $FullName = 'XMP::creatorAtom';

    protected $GroupName = 'XMP-creatorAtom';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-creatorAtom';

    protected $g2 = 'Image';

    protected $Type = 'struct';

    protected $Writable = true;

    protected $Description = 'Ae Project Link';

}
