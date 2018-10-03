<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CSVFileResponse extends StreamedResponse
{
    public function __construct($filename, $callback = null, $status = 200, $headers = [])
    {
        parent::__construct($callback, $status, array_merge(
            // set some headers to fix ie issues
            [
                'Expires'               => 'Mon, 26 Jul 1997 05:00:00 GMT',
                'Last-Modified'         => gmdate('D, d M Y H:i:s'). ' GMT',
                'Cache-Control'         => 'no-store, no-cache, must-revalidate',
                'Cache-Control'         => 'post-check=0, pre-check=0',
                'Pragma'                => 'no-cache',
                'Cache-Control'         => 'max-age=3600, must-revalidate',
                'Content-Disposition'   => 'max-age=3600, must-revalidate',
            ],
            $headers
        ));

        $this->headers->set('Content-Type', 'text/csv');

        $this->headers->set('Content-Disposition', $this->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            // ascii filename fallback
            false === preg_match('/^[\x20-\x7e]*$/', $filename) ? '' : preg_replace('/[^(x20-x7F)]*$/', '', $filename)
        ));
    }
}
