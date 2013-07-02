<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class ApacheMode extends AbstractXSendFileMode implements ModeInterface
{
    /**
     * {@inheritdoc}
     */
    public function setHeaders(Request $request)
    {
        $request->headers->add(array(
            'X-Sendfile-Type' => 'X-SendFile',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setMapping(array $mapping)
    {
        $final = array();

        foreach ($mapping as $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException('XSendFile mapping entry must be an array');
            }

            if (!isset($entry['directory'])) {
                throw new InvalidArgumentException('XSendFile mapping entry must contain at least a "directory" key');
            }

            if (false === is_dir(trim($entry['directory']))) {
                continue;
            }

            $final[] = array(
                'directory' => $this->sanitizePath(realpath($entry['directory']))
            );
        }

        $this->mapping = $final;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualHostConfiguration()
    {
        $output = "\n";
        $output .= "<IfModule mod_xsendfile.c>\n";
        $output .= "  <Files *>\n";
        $output .= "      XSendFile on\n";
        foreach ($this->mapping as $entry) {
            $output .= '      XSendFilePath  ' .  $entry['directory'] . "\n";
        }
        $output .= "  </Files>\n";
        $output .= "</IfModule>\n";

        return $output;
    }
}
