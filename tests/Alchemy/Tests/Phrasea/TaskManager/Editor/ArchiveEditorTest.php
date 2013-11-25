<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\ArchiveEditor;

class ArchiveEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return [
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<base_id></base_id><hotfolder></hotfolder><move_archived>0</move_archived><move_error>0</move_error><delfolder>0</delfolder><copy_spe>0</copy_spe><cold></cold></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', []
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <base_id>24</base_id>
  <hotfolder>/path/to/hot/folder</hotfolder>
  <move_archived>1</move_archived>
  <move_error>1</move_error>
  <delfolder>1</delfolder>
  <copy_spe>1</copy_spe>
  <cold>77</cold>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <base_id>56</base_id>
  <hotfolder>/path/to/old/hot/folder</hotfolder>
  <move_archived>0</move_archived>
  <move_error>0</move_error>
  <delfolder>0</delfolder>
  <copy_spe>0</copy_spe>
  <cold>42</cold>
</tasksettings>', [
        'base_id' => 24,
        'hotfolder' => '/path/to/hot/folder',
        'move_archived' => 1,
        'move_error' => 1,
        'delfolder' => 1,
        'copy_spe' => 1,
        'cold' => 77,
    ]
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <base_id></base_id>
  <hotfolder></hotfolder>
  <move_archived>0</move_archived>
  <move_error>0</move_error>
  <delfolder>0</delfolder>
  <copy_spe>0</copy_spe>
  <cold></cold>
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
        return new ArchiveEditor($this->createTranslatorMock());
    }
}
