<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\SubdefsEditor;

/**
 * @group functional
 * @group legacy
 */
class SubdefsEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <type_image>0</type_image>
  <type_video>0</type_video>
  <type_audio>0</type_audio>
  <type_document>0</type_document>
  <type_flash>0</type_flash>
  <type_unknown>0</type_unknown>
  <flush>0</flush>
  <maxrecs>0</maxrecs>
  <maxmegs>0</maxmegs>
  <embedded>0</embedded>
</tasksettings>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>',
                []
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <sbas>42</sbas>
  <type_image>0</type_image>
  <type_video>1</type_video>
  <type_audio>0</type_audio>
  <type_document>0</type_document>
  <type_flash>0</type_flash>
  <type_unknown>0</type_unknown>
  <flush>0</flush>
  <maxrecs>12</maxrecs>
  <maxmegs>1</maxmegs>
  <embedded>1</embedded>
</tasksettings>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <sbas>24</sbas>
  <type_image>1</type_image>
  <type_video>1</type_video>
  <type_audio>1</type_audio>
  <type_document>0</type_document>
  <type_flash>1</type_flash>
  <type_unknown>0</type_unknown>
  <flush></flush>
  <maxrecs>21</maxrecs>
  <maxmegs>12</maxmegs>
  <embedded>0</embedded>
</tasksettings>',
                [
                    'sbas' => 42,
                    'type_image' => 0,
                    'type_video' => 1,
                    'type_audio' => 0,
                    'type_document' => 0,
                    'type_flash' => 0,
                    'type_unknown' => 0,
                    'flush' => 0,
                    'maxrecs' => 12,
                    'maxmegs' => 1,
                    'embedded' => 1,
                ]
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <type_image>0</type_image>
  <type_video>0</type_video>
  <type_audio>0</type_audio>
  <type_document>0</type_document>
  <type_flash>0</type_flash>
  <type_unknown>0</type_unknown>
  <flush>0</flush>
  <maxrecs>0</maxrecs>
  <maxmegs>0</maxmegs>
  <embedded>0</embedded>
</tasksettings>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
</tasksettings>',
                []
            ],
        ];
    }

    protected function getEditor()
    {
        return new SubdefsEditor($this->createTranslatorMock());
    }
}
