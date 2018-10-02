<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\H264PseudoStreaming;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Http\AbstractServerMode;
use Guzzle\Http\Url;

class Nginx extends AbstractServerMode implements H264Interface
{
    /**
     * @params array $mapping
     *
     * @throws InvalidArgumentException if mapping is invalid;
     */
    public function setMapping(array $mapping)
    {
        $final = [];

        foreach ($mapping as $key => $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException('H264 pseudo streaming mapping entry must be an array');
            }

            if (!isset($entry['directory'])) {
                throw new InvalidArgumentException('H264 pseudo streaming mapping entry must contain at least a "directory" key');
            }

            if (!isset($entry['mount-point'])) {
                throw new InvalidArgumentException('H264 pseudo streaming mapping entry must contain at least a "mount-point" key');
            }

            if (!isset($entry['passphrase'])) {
                throw new InvalidArgumentException('H264 pseudo streaming mapping entry must contain at least a "passphrase" key');
            }

            if (false === is_dir(trim($entry['directory'])) || '' === trim($entry['mount-point']) || '' === trim($entry['passphrase'])) {
                continue;
            }

            $final[$key] = [
                'directory' => $this->sanitizePath(realpath($entry['directory'])),
                'mount-point' => $this->sanitizeMountPoint($entry['mount-point']),
                'passphrase' => trim($entry['passphrase']),
            ];
        }

        $this->mapping = $final;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($pathfile)
    {
        if (!is_file($pathfile)) {
            return null;
        }
        $pathfile = realpath($pathfile);

        foreach ($this->mapping as $entry) {
            if (0 !== strpos($pathfile, $entry['directory'])) {
                continue;
            }

            return $this->generateUrl($pathfile, $entry);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualHostConfiguration()
    {
        $output = "\n";
        foreach ($this->mapping as $entry) {
            $output .= "    location " . $entry['mount-point']. " {\n";
            $output .= "        mp4;\n";
            $output .= "        secure_link \$arg_hash,\$arg_expires;\n";
            $output .= "        secure_link_md5 \"\$secure_link_expires\$uri ".$entry['passphrase']."\";\n";
            $output .= "        \n";
            $output .= "        if (\$secure_link = \"\") {\n";
            $output .= "            return 403;\n";
            $output .= "        }\n";
            $output .= "        if (\$secure_link = \"0\") {\n";
            $output .= "            return 410;\n";
            $output .= "        }\n";
            $output .= "        \n";
            $output .= "        alias ".$entry['directory'].";\n";
            $output .= "    }\n";
        }

        return $output;
    }

    private function generateUrl($pathfile, array $entry)
    {
        $path = $entry['mount-point'].substr($pathfile, strlen($entry['directory']));
        $expire = time() + 3600; // At which point in time the file should expire. time() + x; would be the usual usage.

        $hash = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(md5($expire.$path.' '.$entry['passphrase'], true)));

        return Url::factory($path.'?hash='.$hash.'&expires='.$expire);
    }
}
