<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MAC;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class SampleRate extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SampleRate';

    protected $FullName = 'mixed';

    protected $GroupName = 'MAC';

    protected $g0 = 'APE';

    protected $g1 = 'MAC';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Sample Rate';

}
