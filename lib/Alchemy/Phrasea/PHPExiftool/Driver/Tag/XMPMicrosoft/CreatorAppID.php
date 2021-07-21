<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPMicrosoft;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CreatorAppID extends AbstractTag
{

    protected $Id = 'CreatorAppId';

    protected $Name = 'CreatorAppID';

    protected $FullName = 'Microsoft::XMP';

    protected $GroupName = 'XMP-microsoft';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-microsoft';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Creator App ID';

}
