<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Microsoft;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CallbackNumber extends AbstractTag
{

    protected $Id = '{BF53D1C3-49E0-4F7F-8567-5A821D8AC542} 100';

    protected $Name = 'CallbackNumber';

    protected $FullName = 'Microsoft::Xtra';

    protected $GroupName = 'Microsoft';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Microsoft';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Callback Number';

}
