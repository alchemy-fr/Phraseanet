<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\WriteMetadataEditor;

class WriteMetadataEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return [
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<cleardoc>0</cleardoc><mwg>0</mwg></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', []
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <cleardoc>1</cleardoc>
  <mwg>0</mwg>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <cleardoc>0</cleardoc>
</tasksettings>', ['cleardoc' => 1]
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<cleardoc>0</cleardoc><mwg>1</mwg></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', ['mwg' => 1]
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <cleardoc>0</cleardoc>
  <mwg>0</mwg>
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
        return new WriteMetadataEditor($this->createTranslatorMock());
    }
}
