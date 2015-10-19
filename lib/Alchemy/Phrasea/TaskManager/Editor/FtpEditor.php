<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class FtpEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/ftp.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        return 900;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(PropertyAccess $config = null)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <proxy></proxy>
    <proxyport></proxyport>
    <proxyuser></proxyuser>
    <proxypwd></proxypwd>
</tasksettings>
EOF;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return [
            'proxy'     => static::FORM_TYPE_STRING,
            'proxyport' => static::FORM_TYPE_STRING,
            'proxyuser' => static::FORM_TYPE_STRING,
            'proxypwd'  => static::FORM_TYPE_STRING,
        ];
    }
}
