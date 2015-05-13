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

class SubdefsEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/subdefs.html.twig';
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
    public function getDefaultSettings(PropertyAccess $config = null)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <embedded>1</embedded>
  <sbas>0</sbas>
  <type_image>1</type_image>
  <type_video>1</type_video>
  <type_audio>1</type_audio>
  <type_document>1</type_document>
  <type_flash>1</type_flash>
  <type_unknown>1</type_unknown>
  <flush>5</flush>
  <maxrecs>20</maxrecs>
  <maxmegs>256</maxmegs>
</tasksettings>
EOF;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return [
            'sbas' => static::FORM_TYPE_INTEGER,
            'type_image' => static::FORM_TYPE_BOOLEAN,
            'type_video' => static::FORM_TYPE_BOOLEAN,
            'type_audio' => static::FORM_TYPE_BOOLEAN,
            'type_document' => static::FORM_TYPE_BOOLEAN,
            'type_flash' => static::FORM_TYPE_BOOLEAN,
            'type_unknown' => static::FORM_TYPE_BOOLEAN,
            'flush' => static::FORM_TYPE_INTEGER,
            'maxrecs' => static::FORM_TYPE_INTEGER,
            'maxmegs' => static::FORM_TYPE_INTEGER,
            'embedded' => static::FORM_TYPE_BOOLEAN
        ];
    }
}
