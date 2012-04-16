<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class task_abstractTest extends PhraseanetPHPUnitAbstract
{

  public function testCreate()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $task = task_abstract::create($appbox, 'task_period_apibridge');
    $task->delete();
  }

}

