<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPRdf;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class About extends AbstractTag
{

    protected $Id = 'about';

    protected $Name = 'About';

    protected $FullName = 'XMP::rdf';

    protected $GroupName = 'XMP-rdf';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-rdf';

    protected $g2 = 'Document';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'About';

    protected $flag_Unsafe = true;

}
