<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Rawzor;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CompressionFactor extends AbstractTag
{

    protected $Id = 'CompressionFactor';

    protected $Name = 'CompressionFactor';

    protected $FullName = 'Rawzor::Main';

    protected $GroupName = 'Rawzor';

    protected $g0 = 'Rawzor';

    protected $g1 = 'Rawzor';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Compression Factor';

}
