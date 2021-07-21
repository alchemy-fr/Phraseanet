<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\RAF;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RawExposureBias extends AbstractTag
{

    protected $Id = 38480;

    protected $Name = 'RawExposureBias';

    protected $FullName = 'FujiFilm::RAF';

    protected $GroupName = 'RAF';

    protected $g0 = 'RAF';

    protected $g1 = 'RAF';

    protected $g2 = 'Image';

    protected $Type = 'rational32s';

    protected $Writable = false;

    protected $Description = 'Raw Exposure Bias';

}
