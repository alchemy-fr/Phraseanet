<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ICCMeta;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ManufacturerName extends AbstractTag
{

    protected $Id = 'ManufacturerName';

    protected $Name = 'ManufacturerName';

    protected $FullName = 'ICC_Profile::Metadata';

    protected $GroupName = 'ICC-meta';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-meta';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Manufacturer Name';

}
