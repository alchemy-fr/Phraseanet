<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Symfony\Component\HttpFoundation\Response;
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
     * @param string|null $mimeType
     * @param integer     $cacheDuration
     * @return Response
     */
    public function deliverFile($file, $filename = null, $disposition = self::DISPOSITION_INLINE, $mimeType = null, $cacheDuration = null);
}
