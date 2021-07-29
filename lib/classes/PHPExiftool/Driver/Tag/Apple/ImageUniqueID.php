<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Apple;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageUniqueID extends AbstractTag
{

    protected $Id = 21;

    protected $Name = 'ImageUniqueID';

    protected $FullName = 'Apple::Main';

    protected $GroupName = 'Apple';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Apple';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Unique ID';

    protected $flag_Permanent = true;

}
