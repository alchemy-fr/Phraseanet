<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

interface DeliverDataInterface
{
    const DISPOSITION_INLINE = ResponseHeaderBag::DISPOSITION_INLINE;
    const DISPOSITION_ATTACHMENT = ResponseHeaderBag::DISPOSITION_ATTACHMENT;

    /**
     * Returns a HTTP Response ready to deliver a binary file
     *
     * @param string      $file
     * @param string      $filename
     * @param string      $disposition
     * @param string|null $mimetype
     * @param integer     $cacheDuration
     */
    public function deliverFile($file, $filename = null, $disposition = self::DISPOSITION_INLINE, $mimeType = null, $cacheDuration = 3600);

    /**
     * Return a HTTP Response ready to deliver data
     *
     * @param string  $data
     * @param string  $filename
     * @param string  $mimeType
     * @param string  $disposition
     * @param integer $cacheDuration
     */
    public function deliverData($data, $filename, $mimeType, $disposition = self::DISPOSITION_INLINE, $cacheDuration = 3600);
}
