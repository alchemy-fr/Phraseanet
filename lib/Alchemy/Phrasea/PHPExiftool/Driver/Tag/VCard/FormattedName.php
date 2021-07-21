<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\VCard;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class FormattedName extends AbstractTag
{

    protected $Id = 'Fn';

    protected $Name = 'FormattedName';

    protected $FullName = 'VCard::Main';

    protected $GroupName = 'VCard';

    protected $g0 = 'VCard';

    protected $g1 = 'VCard';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Formatted Name';

    protected $local_g2 = 'Author';

}
