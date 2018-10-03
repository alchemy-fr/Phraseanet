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

class Apache extends AbstractServerMode implements H264Interface
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
            $output .= "    Alias ".$entry['mount-point']." \"".$entry['directory']."\"\n";
            $output .= "\n";
            $output .= "    <Location ".$entry['mount-point'].">\n";
            $output .= "        AuthTokenSecret       \"".$entry['passphrase']."\"\n";
            $output .= "        AuthTokenPrefix       ".$entry['mount-point']."\n";
            $output .= "        AuthTokenTimeout      3600\n";
            $output .= "        AuthTokenLimitByIp    off\n";
            $output .= "    </Location>\n";
            $output .= "\n";
        }

        return $output;
    }

    private function generateUrl($pathfile, array $entry)
    {
        $path = substr($pathfile, strlen($entry['directory']));

        $hexTime = dechex(time() + 3600);
        $token = md5($entry['passphrase'] . $path . $hexTime);

        return Url::factory($entry['mount-point'] .'/'. $token . "/" . $hexTime . $path);
    }
}
