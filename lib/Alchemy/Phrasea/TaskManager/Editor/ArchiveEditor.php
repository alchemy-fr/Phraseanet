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

class ArchiveEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/archive.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(PropertyAccess $config = null)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <base_id></base_id>
    <hotfolder></hotfolder>
    <move_archived>0</move_archived>
    <move_error>0</move_error>
    <delfolder>0</delfolder>
    <copy_spe>0</copy_spe>
    <cold></cold>
</tasksettings>
EOF;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return [
            'base_id'       => static::FORM_TYPE_INTEGER,
            'hotfolder'     => static::FORM_TYPE_STRING,
            'move_archived' => static::FORM_TYPE_BOOLEAN,
            'move_error'    => static::FORM_TYPE_BOOLEAN,
            'delfolder'     => static::FORM_TYPE_BOOLEAN,
            'copy_spe'      => static::FORM_TYPE_BOOLEAN,
            'cold'          => static::FORM_TYPE_INTEGER,
        ];
    }
}
