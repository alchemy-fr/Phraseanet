<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Scalado;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PreviewQuality extends AbstractTag
{

    protected $Id = 'QUAL';

    protected $Name = 'PreviewQuality';

    protected $FullName = 'Scalado::Main';

    protected $GroupName = 'Scalado';

    protected $g0 = 'APP4';

    protected $g1 = 'Scalado';

    protected $g2 = 'Image';

    protected $Type = 'int32s';

    protected $Writable = false;

    protected $Description = 'Preview Quality';

}
