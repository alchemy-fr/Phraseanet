<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification;

class Attachment
{
    private $path;
    private $filename;
    private $contentType;

    public function __construct($path, $filename='', $contentType='')
    {
        $this->path = $path;
        $this->filename = $filename;
        $this->contentType = $contentType;
    }

    public function As_Swift_Attachment()
    {
        $swa = \Swift_Attachment::fromPath($this->path);
        if($this->filename !== '') {
            $swa->setFilename($this->filename);
        }
        if($this->contentType !== '') {
            $swa->setContentType($this->contentType);
        }
        return $swa;
    }
}
