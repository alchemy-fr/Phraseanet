<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Symfony\Component\HttpFoundation\Request;

class PathHelper extends Helper
{
    public function checkPath()
    {
        return [
            'exists'     => file_exists($this->request->query->get('path')),
            'file'       => is_file($this->request->query->get('path')),
            'dir'        => is_dir($this->request->query->get('path')),
            'readable'   => is_readable($this->request->query->get('path')),
            'writeable'  => is_writable($this->request->query->get('path')),
            'executable' => is_executable($this->request->query->get('path')),
        ];
    }

    public function checkUrl()
    {
        return ['code' => \http_query::getHttpCodeFromUrl($this->request->query->get('url'))];
    }
}
