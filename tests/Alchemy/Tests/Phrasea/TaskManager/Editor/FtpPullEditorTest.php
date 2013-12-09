<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\FtpPullEditor;

class FtpPullEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return [
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<proxy></proxy><proxyport></proxyport><passive>0</passive><ssl>0</ssl><password></password><user></user><ftppath></ftppath><localpath></localpath><port></port><host></host></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', []
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <proxy>1234</proxy>
  <proxyport>5678</proxyport>
  <passive>1</passive>
  <ssl>1</ssl>
  <password>a password</password>
  <user>username</user>
  <ftppath>nice path</ftppath>
  <localpath>path to the future</localpath>
  <port>22</port>
  <host>example.com</host>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings><proxy>12</proxy><proxyport>8</proxyport><passive>0</passive><ssl>0</ssl><password></password><user></user><ftppath></ftppath><localpath></localpath><port>21</port><host></host>
</tasksettings>', ['proxy' => 1234, 'proxyport' => 5678, 'passive' => 1, 'ssl' => 1, 'password' => 'a password', 'user' => 'username', 'ftppath' => 'nice path', 'localpath' => 'path to the future', 'port' => 22, 'host' => 'example.com']
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <proxy></proxy>
  <proxyport></proxyport>
  <passive>0</passive>
  <ssl>0</ssl>
  <password></password>
  <user></user>
  <ftppath></ftppath>
  <localpath></localpath>
  <port></port>
  <host></host>
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
        return new FtpPullEditor($this->createTranslatorMock());
    }
}
