<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Symfony\Component\HttpFoundation\Request;

class BinaryFileResponse extends \Symfony\Component\HttpFoundation\BinaryFileResponse
{
    public function prepare(Request $request)
    {
        parent::prepare($request);

        // see https://github.com/symfony/symfony/pull/17602
        if (!(self::$trustXSendfileTypeHeader && $request->headers->has('X-Sendfile-Type'))
            && $request->headers->has('Range') && $request->headers->has('If-Range')
            && $this->hasValidIfRangeHeader($request->headers->get('If-Range'))
        ) {
            $range = $request->headers->get('Range');
            $fileSize = $this->file->getSize();

            list($start, $end) = explode('-', substr($range, 6), 2) + array(0);

            $end = ('' === $end) ? $fileSize - 1 : (int) $end;

            if ('' === $start) {
                $start = $fileSize - $end;
                $end = $fileSize - 1;
            } else {
                $start = (int) $start;
            }

            if ($start <= $end) {
                if ($start < 0 || $end > $fileSize - 1) {
                    $this->setStatusCode(416);
                } elseif ($start !== 0 || $end !== $fileSize - 1) {
                    $this->maxlen = $end < $fileSize ? $end - $start + 1 : -1;
                    $this->offset = $start;

                    $this->setStatusCode(206);
                    $this->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
                    $this->headers->set('Content-Length', $end - $start + 1);
                }
            }

        }

        return $this;
    }

    private function hasValidIfRangeHeader($header)
    {
        if (null === $lastModified = $this->getLastModified()) {
            return false;
        }

        return $lastModified->format('D, d M Y H:i:s').' GMT' == $header;
    }
}
