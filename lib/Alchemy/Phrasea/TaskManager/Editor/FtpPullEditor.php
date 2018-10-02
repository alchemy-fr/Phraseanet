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

class FtpPullEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/ftp-pull.html.twig';
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
    <passive>0</passive>
    <ssl>0</ssl>
    <password></password>
    <user></user>
    <ftppath></ftppath>
    <localpath></localpath>
    <port>21</port>
    <host></host>
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
            'passive'   => static::FORM_TYPE_BOOLEAN,
            'ssl'       => static::FORM_TYPE_BOOLEAN,
            'password'  => static::FORM_TYPE_STRING,
            'user'      => static::FORM_TYPE_STRING,
            'ftppath'   => static::FORM_TYPE_STRING,
            'localpath' => static::FORM_TYPE_STRING,
            'port'      => static::FORM_TYPE_INTEGER,
            'host'      => static::FORM_TYPE_STRING,
        ];
    }
}
