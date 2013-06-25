<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Response;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

interface DeliverDataInterface
{
    /**
     * Returns a HTTP Response ready to deliver a binary file
     *
     * @param string $file
     * @param string $filename
     * @param string $disposition
     */
    public function deliverFile($file, $filename = null, $disposition = ResponseHeaderBag::DISPOSITION_INLINE);

    /**
     * Return a HTTP Response ready to deliver data
     *
     * @param string $data
     * @param string $filename
     * @param string $mimeType
     * @param string $disposition
     */
    public function deliverData($data, $filename, $mimeType, $disposition = ResponseHeaderBag::DISPOSITION_INLINE);
}
