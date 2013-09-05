<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

class WriteMetadataEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/write-metadata.html.twig';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        return 10;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(ConfigurationInterface $config = null)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <cleardoc>0</cleardoc>
</tasksettings>
EOF;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return array(
            'cleardoc' => static::FORM_TYPE_BOOLEAN,
        );
    }
}
