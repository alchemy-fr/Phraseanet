<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Task;

class patch_370alpha8a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.0-alpha.8';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    public function getDoctrineMigrations()
    {
        return ['20131118000004'];
    }

    /**
     * transform tasks 'workflow 01' to 'RecordMover'
     * will group tasks(01) with same period to a single task(02)
     *
     * @param base        $appbox
     * @param Application $app
     *
     * @return boolean
     */
    public function apply(base $appbox, Application $app)
    {
        $ttasks = [];
        $conn = $appbox->get_connection();
        $sql = 'SELECT task_id, active, name, class, settings
                FROM task2
                WHERE class=\'task_period_workflow01\'';
        if (($stmt = $conn->prepare($sql)) !== FALSE) {
            $stmt->execute();
            $ttasks = $stmt->fetchAll();
        }
        $stmt->closeCursor();

        $tdom = [];     // key = period
        $taskstodel = [];
        foreach ($ttasks as $task) {
            $active = true;
            $warning = [];

            /*
             * migrating task 'workflow01'
             */
            $x = $task['settings'];
            if (false !== $sx = simplexml_load_string($x)) {
                $period = (int) ($sx->period);

                if ( ! array_key_exists('_' . $period, $tdom)) {
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->formatOutput = true;
                    $dom->preserveWhiteSpace = false;
                    $ts = $dom->appendChild($dom->createElement('tasksettings'));
                    $ts->appendChild($dom->createElement('period'))->appendChild($dom->createTextNode(60 * $period));
                    $ts->appendChild($dom->createElement('logsql'))->appendChild($dom->createTextNode('1'));
                    $tasks = $ts->appendChild($dom->createElement('tasks'));
                    $tdom['_' . $period] = ['dom'   => $dom, 'tasks' => $tasks];
                } else {
                    $dom = &$tdom['_' . $period]['dom'];
                    $tasks = &$tdom['_' . $period]['tasks'];
                }

                /*
                 * migrating task 'workflow01'
                 */
                if ($task['class'] === 'task_period_workflow01') {
                    $t = $tasks->appendChild($dom->createElement('task'));
                    $t->setAttribute('active', '0');
                    $t->setAttribute('name', 'imported from \'' . $task['name'] . '\'');
                    $t->setAttribute('action', 'update');

                    if ($sx->sbas_id) {
                        $sbas_id = trim($sx->sbas_id);
                        if ($sbas_id != '' && is_numeric($sbas_id)) {
                            $t->setAttribute('sbas_id', $sx->sbas_id);
                        } else {
                            $warning[] = sprintf("Bad sbas_id '%s'", $sbas_id);
                            $active = false;
                        }
                    } else {
                        $warning[] = sprintf("missing sbas_id");
                        $active = false;
                    }

                    // 'from' section
                    $from = $t->appendChild($dom->createElement('from'));
                    if ($sx->coll0) {
                        if (($coll0 = trim($sx->coll0)) != '') {
                            if (is_numeric($coll0)) {
                                $n = $from->appendChild($dom->createElement('coll'));
                                $n->setAttribute('compare', '=');
                                $n->setAttribute('id', $coll0);
                            } else {
                                $warning[] = sprintf("Bad (from) coll_id '%s'", $coll0);
                                $active = false;
                            }
                        }
                    }
                    if ($sx->status0 && trim($sx->status0) != '') {
                        $st = explode('_', trim($sx->status0));
                        if (count($st) == 2) {
                            $bit = (int) ($st[0]);
                            if ($bit >= 0 && $bit < 32 && ($st[1] == '0' || $st[1] == '1')) {
                                $from->appendChild($dom->createElement('status'))
                                    ->setAttribute('mask', $st[1] . str_repeat('x', $bit - 1));
                            } else {
                                $warning[] = sprintf("Bad (from) status '%s'", trim($sx->status0));
                                $active = false;
                            }
                        } else {
                            $warning[] = sprintf("Bad (from) status '%s'", trim($sx->status0));
                            $active = false;
                        }
                    }

                    // 'to' section
                    $to = $t->appendChild($dom->createElement('to'));
                    if ($sx->coll1) {
                        if (($coll1 = trim($sx->coll1)) != '') {
                            if (is_numeric($coll1)) {
                                $n = $to->appendChild($dom->createElement('coll'));
                                $n->setAttribute('id', $coll1);
                            } else {
                                $warning[] = sprintf("Bad (to) coll_id '%s'", $coll1);
                                $active = false;
                            }
                        }
                    }
                    if ($sx->status1 && trim($sx->status1) != '') {
                        $st = explode('_', trim($sx->status1));
                        if (count($st) == 2) {
                            $bit = (int) ($st[0]);
                            if ($bit >= 0 && $bit < 32 && ($st[1] == '0' || $st[1] == '1')) {
                                $to->appendChild($dom->createElement('status'))
                                    ->setAttribute('mask', $st[1] . str_repeat('x', $bit - 1));
                            } else {
                                $warning[] = sprintf("Bad (to) status '%s'", trim($sx->status1));
                                $active = false;
                            }
                        } else {
                            $warning[] = sprintf("Bad (to) status '%s'", trim($sx->status1));
                            $active = false;
                        }
                    }

                    if ($active && $task['active'] == '1') {
                        $t->setAttribute('active', '1');
                    }
                    foreach ($warning as $w) {
                        $t->appendChild($dom->createComment($w));
                    }

                    $taskstodel[] = $task['task_id'];
                }
            }

            if (count($taskstodel) > 0) {
                $conn->exec('DELETE FROM task2 WHERE task_id IN(' . implode(',', $taskstodel) . ')');
            }
        }

        /*
         * save new tasks
         */
        foreach ($tdom as $newtask) {
            $settings = $newtask['dom']->saveXML();
            $sxml = simplexml_load_string($settings);
            $period = $sxml->period ? (int) $sxml->period : 300;

            $task = new Task();
            $task
                ->setName('Record mover')
                ->setJobId('RecordMover')
                ->setSettings($settings)
                ->setPeriod($period)
                ->setStatus(Task::STATUS_STARTED);
            $app['orm.em']->persist($task);
        }
        $app['orm.em']->flush();

        return true;
    }
}
