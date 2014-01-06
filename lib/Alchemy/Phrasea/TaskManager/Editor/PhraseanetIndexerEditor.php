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

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

class PhraseanetIndexerEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/phraseanet-indexer.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        // Phraseanet Indexer actually never restart.
        return 5;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(ConfigurationInterface $config = null)
    {
        if (null !== $config) {
            $database = $config['main']['database'];

            return '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <host>'.$database['host'].'</host>
    <port>'.$database['port'].'</port>
    <base>'.$database['dbname'].'</base>
    <user>'.$database['user'].'</user>
    <password>'.$database['password'].'</password>
    <socket>25200</socket>
    <nolog>0</nolog>
    <clng></clng>
    <winsvc_run>0</winsvc_run>
    <charset>utf8</charset>
</tasksettings>';
        }

        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <host></host>
    <port></port>
    <base></base>
    <user></user>
    <password></password>
    <socket>25200</socket>
    <nolog>0</nolog>
    <clng></clng>
    <winsvc_run>0</winsvc_run>
    <charset>utf8</charset>
</tasksettings>
EOF;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return [
            'host'       => static::FORM_TYPE_STRING,
            'port'       => static::FORM_TYPE_INTEGER,
            'base'       => static::FORM_TYPE_STRING,
            'user'       => static::FORM_TYPE_STRING,
            'password'   => static::FORM_TYPE_STRING,
            'socket'     => static::FORM_TYPE_INTEGER,
            'nolog'      => static::FORM_TYPE_BOOLEAN,
            'clng'       => static::FORM_TYPE_STRING,
            'winsvc_run' => static::FORM_TYPE_BOOLEAN,
            'charset'    => static::FORM_TYPE_STRING,
            'debugmask'  => static::FORM_TYPE_INTEGER,
            'stem'       => static::FORM_TYPE_STRING,
            'sortempty'  => static::FORM_TYPE_STRING,
        ];
    }
}
