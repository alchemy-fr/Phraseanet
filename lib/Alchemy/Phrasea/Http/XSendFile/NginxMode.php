<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Http\AbstractServerMode;
use Symfony\Component\HttpFoundation\Request;

class NginxMode extends AbstractServerMode implements ModeInterface
{
     /**
     * {@inheritdoc}
     */
    public function setHeaders(Request $request)
    {
        $xAccelMapping = [];

        foreach ($this->mapping as $entry) {
            $xAccelMapping[] = sprintf('%s=%s', $entry['directory'], $entry['mount-point']);
        }

        if (count($xAccelMapping) > 0 ) {
            $request->headers->add([
                'X-Sendfile-Type' => 'X-Accel-Redirect',
                'X-Accel-Mapping' => implode(',', $xAccelMapping),
            ]);
        }
    }

     /**
     * {@inheritdoc}
     */
    public function setMapping(array $mapping)
    {
        $final = [];

        foreach ($mapping as $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException('XSendFile mapping entry must be an array');
            }

            if (!isset($entry['directory'])) {
                throw new InvalidArgumentException('XSendFile mapping entry must contain at least a "directory" key');
            }

            if (!isset($entry['mount-point'])) {
                 throw new InvalidArgumentException('XSendFile mapping entry must contain at least a "mount-point" key');
            }

            if (false === is_dir(trim($entry['directory'])) || '' === trim($entry['mount-point'])) {
                continue;
            }

            $final[] = [
                'directory' => $this->sanitizePath(realpath($entry['directory'])),
                'mount-point' => $this->sanitizeMountPoint($entry['mount-point'])
            ];
        }

        $this->mapping = $final;
    }

     /**
     * {@inheritdoc}
     */
    public function getVirtualHostConfiguration()
    {
        $output = "\n";
        foreach ($this->mapping as $entry) {
            $output .= "  location " . $entry['mount-point']. " {\n";
            $output .= "      internal;\n";
            $output .= "      add_header Etag \$upstream_http_etag;\n";
            $output .= "      add_header Link \$upstream_http_link;\n";
            $output .= "      alias " .  $entry['directory'] . ";\n";
            $output .= "  }\n";
        }

        return $output;
    }
}
