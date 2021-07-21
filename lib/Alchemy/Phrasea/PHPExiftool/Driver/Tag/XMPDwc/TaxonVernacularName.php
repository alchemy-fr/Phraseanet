<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPDwc;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class TaxonVernacularName extends AbstractTag
{

    protected $Id = 'TaxonVernacularName';

    protected $Name = 'TaxonVernacularName';

    protected $FullName = 'DarwinCore::Main';

    protected $GroupName = 'XMP-dwc';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-dwc';

    protected $g2 = 'Other';

    protected $Type = 'lang-alt';

    protected $Writable = true;

    protected $Description = 'Taxon Vernacular Name';

}
