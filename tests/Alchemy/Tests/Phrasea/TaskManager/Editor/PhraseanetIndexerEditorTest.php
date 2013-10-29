<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\PhraseanetIndexerEditor;

class PhraseanetIndexerEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return array(
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<host></host><port></port><base></base><user></user><password></password><socket></socket><nolog>0</nolog><clng></clng><winsvc_run>0</winsvc_run><charset></charset><debugmask></debugmask><stem></stem><sortempty></sortempty></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', array()
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <host>cool-host</host>
  <port>1234</port>
  <base>basename</base>
  <user>username</user>
  <password>passwordvalue</password>
  <socket>4321</socket>
  <nolog>1</nolog>
  <clng>clngvalue</clng>
  <winsvc_run>1</winsvc_run>
  <charset>utf32</charset>
  <debugmask>256</debugmask>
  <stem>stemvalue</stem>
  <sortempty>asc</sortempty>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings><host></host><port></port><base></base><user></user><password></password><socket>25200</socket><nolog>0</nolog><clng></clng><winsvc_run>0</winsvc_run><charset>utf8</charset>
</tasksettings>', array(
    'host'       => 'cool-host',
    'port'       => 1234,
    'base'       => 'basename',
    'user'       => 'username',
    'password'   => 'passwordvalue',
    'socket'     => '4321',
    'nolog'      => '1',
    'clng'       => 'clngvalue',
    'winsvc_run' => '1',
    'charset'    => 'utf32',
    'debugmask'  => 256,
    'stem'       => 'stemvalue',
    'sortempty'  => 'asc',
)
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <host></host>
  <port></port>
  <base></base>
  <user></user>
  <password></password>
  <socket></socket>
  <nolog>0</nolog>
  <clng></clng>
  <winsvc_run>0</winsvc_run>
  <charset></charset>
  <debugmask></debugmask>
  <stem></stem>
  <sortempty></sortempty>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
</tasksettings>', array()
            ),
        );
    }

    protected function getEditor()
    {
        return new PhraseanetIndexerEditor();
    }
}
