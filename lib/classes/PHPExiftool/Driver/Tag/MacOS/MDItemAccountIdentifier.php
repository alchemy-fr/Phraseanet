<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MacOS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MDItemAccountIdentifier extends AbstractTag
{

    protected $Id = 'MDItemAccountIdentifier';

    protected $Name = 'MDItemAccountIdentifier';

    protected $FullName = 'MacOS::MDItem';

    protected $GroupName = 'MacOS';

    protected $g0 = 'File';

    protected $g1 = 'MacOS';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'MD Item Account Identifier';

}
