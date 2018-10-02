<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification;

/**
 * Class Attachment     attach file to a mail
 * @package Alchemy\Phrasea\Notification
 */
class Attachment
{
    private $path;
    private $filename;
    private $contentType;

    /**
     * @param string $path          path to an existing file to be added as attachment
     * @param string $filename      change the name of attachment, (default to '' to use filename from path)
     * @param string $contentType   change mime, (default to '' to get from path)
     */
    public function __construct($path, $filename='', $contentType='')
    {
        $this->path = $path;
        $this->filename = $filename;
        $this->contentType = $contentType;
    }

    /**
     * @return \Swift_Mime_Attachment   the attachment as a swift attachment
     */
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
