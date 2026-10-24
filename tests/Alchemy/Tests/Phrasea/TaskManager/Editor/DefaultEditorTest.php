<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class DefaultEditorTest extends EditorTestCase
{
    public static function provideDataForXMLUpdatesFromForm()
    {
        return [
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', []
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
</tasksettings>', []
            ],
        ];
    }

    protected function getEditor()
    {
        return new DefaultEditor($this->createTranslatorMock());
    }
}
