<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MSDOC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CommentBy extends AbstractTag
{

    protected $Id = 'CommentBy';

    protected $Name = 'CommentBy';

    protected $FullName = 'FlashPix::DocTable';

    protected $GroupName = 'MS-DOC';

    protected $g0 = 'FlashPix';

    protected $g1 = 'MS-DOC';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Comment By';

    protected $local_g2 = 'Author';

}
