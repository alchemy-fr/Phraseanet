<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\Process\ExecutableFinder;

class task_period_cindexer extends task_abstract
{
    // how to execute indexer (choose in 'run2' method)
    private $method;

    const METHOD_FORK = 'METHOD_FORK';
    const METHOD_EXEC = 'METHOD_EXEC';
    const METHOD_PROC_OPEN = 'METHOD_PROC_OPEN';
    const ERR_EXECUTABLE_NOT_FOUND = 2;   // aka ENOENT (No such file or directory)
    const ERR_CRASHED = 14;      // aka EFAULT (Bad address)
    const ERR_CANT_FORK = 3;      // aka ESRCH (No such process)

    /**
     *
     * @var string
     */
    protected $host;

    /**
     *
     * @var int
     */
    protected $port;

    /**
     *
     * @var string
     */
    protected $base;

    /**
     *
     * @var string
     */
    protected $user;

    /**
     *
     * @var string
     */
    protected $password;

    /**
     *
     * @var int
     */
    protected $socket;

    /**
     *
     * @var string
     */
    protected $charset;

    /**
     *
     * @var string
     */
    protected $debugmask;

    /**
     *
     * @var string
     */
    protected $stem;

    /**
     *
     * @var string
     */
    protected $sortempty;

    /**
     *
     * @var string
     */
    protected $nolog;

    /**
     *
     * @var string
     */
    protected $winsvc_run;

    /**
     *
     * @return string
     */
    public static function getName()
    {
        return(_("Indexation task"));
    }

    /**
     *
     * @return string
     */
    public static function help()
    {
        return(_("This task is used to index records for Phrasea engine."));
    }

    /**
     *
     * @param  string $oldxml
     * @return string
     */
    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            'host', 'port', 'base', 'user', 'password', 'socket', 'nolog', 'clng', 'winsvc_run', 'charset', 'debugmask', 'stem', 'sortempty'
        );
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        if ($dom->loadXML($oldxml)) {
            $xmlchanged = false;
            foreach (array("str:host", "str:port", "str:base", "str:user", "str:password", "str:socket", "boo:nolog", "str:clng", "boo:winsvc_run", "str:charset", 'str:debugmask', 'str:stem', 'str:sortempty') as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0)) != NULL) {
                    // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                    while (($n = $ns->firstChild)) {
                        $ns->removeChild($n);
                    }
                } else {
                    // le champ n'existait pas dans le xml, on le cree
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                }
                // on fixe sa valeur
                switch ($ptype) {
                    case "str":
                        $ns->appendChild($dom->createTextNode($pvalue));
                        break;
                    case "boo":
                        $ns->appendChild($dom->createTextNode($pvalue ? '1' : '0'));
                        break;
                }
                $xmlchanged = true;
            }
        }

        return($dom->saveXML());
    }


    /**
     *
     * @return void
     */
    public function printInterfaceJS()
    {
        ?>
        <script type="text/javascript">

            function taskFillGraphic_<?php echo(get_class($this));?>(xml)
            {
                if (xml) {
                    xml = $.parseXML(xml);
                    xml = $(xml);

                    var isyes = function(v) {
                        v = v.toUpperCase().trim();

                        return v=='O' || v=='Y' || v=='OUI' || v=='YES' || v=='1';
                    }

                    with(document.forms['graphicForm'])
                    {
                        host.value         = xml.find("host").text();
                        port.value         = xml.find("port").text();
                        base.value         = xml.find("base").text();
                        user.value         = xml.find("user").text();
                        socket.value       = xml.find("socket").text();
                        password.value     = xml.find("password").text();
                        clng.value         = xml.find("clng").text();
                        nolog.checked      = isyes(xml.find("nolog").text());
                        winsvc_run.checked = isyes(xml.find("winsvc_run").text());
                        charset.value      = xml.find("charset").text();
                        stem.value         = xml.find("stem").text();
                        sortempty.value    = xml.find("sortempty").text();
                        debugmask.value    = 0|xml.find("debugmask").text();
                    }
                }

                var cmd = '';
                with(document.forms['graphicForm'])
                {
                    cmd += "<?php echo $this->getIndexer() ?>";
                    if(host.value)
                        cmd += " -h=" + host.value;
                    if(port.value)
                        cmd += " -P=" + port.value;
                    if(base.value)
                        cmd += " -b=" + base.value;
                    if(user.value)
                        cmd += " -u=" + user.value;
                    if(password.value)
                        cmd += " -p=xxxxxx"; // + password.value;
                    if(socket.value)
                        cmd += " --socket=" + socket.value;
                    if(charset.value)
                        cmd += " --default-character-set=" + charset.value;

                    cmd += " -o";

                    if(nolog.checked)
                        cmd += " -n";
                    if(clng.value)
                        cmd += " -c=" + clng.value;
                    if(stem.value)
                        cmd += " --stem=" + stem.value;
                    if(sortempty.value)
                        cmd += " --sort-empty=" + sortempty.value;
                    if(debugmask.value)
                        cmd += " -d=" + debugmask.value;
                    if(winsvc_run.checked)
                        cmd += " --run";
                }
                $('#cmd').html(cmd);
            }

            $(document).ready(function(){
                $("#graphicForm *").change(function(){
                    taskFillGraphic_<?php echo(get_class($this));?>(null);
                });
            });

        </script>
        <?php

        return;
    }

    /**
     *
     * @return return
     */
    public function getInterfaceHTML()
    {
        ob_start();
        ?>
        <form id="graphicForm" name="graphicForm" class="form-horizontal" onsubmit="return(false);" method="post">
            <div class="control-group">
                <label class="control-label"><?php echo _('MySQL Host') ?></label>
                <div class="controls">
                    <input type="text" name="host" value="">
                </div>
                <label class="control-label"><?php echo _('MySQL Port') ?></label>
                <div class="controls">
                    <input type="text" name="port" value="">
                </div>
                <label class="control-label"><?php echo _('MySQL Database') ?></label>
                <div class="controls">
                    <input type="text" name="base" value="">
                </div>
                <label class="control-label"><?php echo _('MySQL Login') ?></label>
                <div class="controls">
                    <input type="text" name="user" value="">
                </div>
                <label class="control-label"><?php echo _('MySQL password') ?></label>
                <div class="controls">
                    <input type="password" name="password" value="">
                </div>
                <label class="control-label"><?php echo _('MySQL connection charset') ?></label>
                <div class="controls">
                    <input type="text" name="charset" class="input-small" value="">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Socket port') ?></label>
                <div class="controls">
                    <input type="text" name="socket" class="input-small" value="">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Debug binary mask') ?></label>
                <div class="controls">
                    <input type="text" name="debugmask" class="input-small" value="">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Default language for thesaurus candidates') ?></label>
                <div class="controls">
                    <input type="text" name="clng" class="input-small" value="">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Enable stemming languages') ?></label>
                <div class="controls">
                    <input type="text" name="stem" class="input-small" value="">
                    <span class="help-inline"><?php echo _('example : fr,en') ?></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Sort records with an empty field') ?></label>
                <div class="controls">
                    <select name="sortempty">
                        <option value=""><?php echo _('Hide records') ?></option>
                        <option value="A"><?php echo _('At the beginning') ?></option>
                        <option value="Z"><?php echo _('At the end') ?></option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox">
                        <input type="checkbox" name="nolog">
                        <?php echo _('Do not log, output to console') ?>
                    </label>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox">
                        <input type="checkbox" name="winsvc_run">
                        <?php echo _('Run as application, not as service') ?>
                    <span class="help-inline">(<?php echo _('Windows specific') ?>)</span>
                    </label>
                </div>
            </div>
        </form>

        <center>
            <div style="margin:10px; padding:5px; border:1px #000000 solid; font-family:monospace; font-size:14px; text-align:left; color:#00e000; background-color:#404040" id="cmd">cmd</div>
        </center>
        <?php

        return ob_get_clean();
    }

    /**
     *
     * @param  SimpleXMLElement $sx_task_settings
     * @return task_cindexer
     */
    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->host = trim($sx_task_settings->host);
        $this->port = trim($sx_task_settings->port);
        $this->base = trim($sx_task_settings->base);
        $this->user = trim($sx_task_settings->user);
        $this->password = trim($sx_task_settings->password);
        $this->socket = trim($sx_task_settings->socket);
        $this->charset = trim($sx_task_settings->charset);
        $this->stem = trim($sx_task_settings->stem);
        $this->sortempty = trim($sx_task_settings->sortempty);
        $this->debugmask = (int) (trim($sx_task_settings->debugmask));
        $this->nolog = p4field::isyes(trim($sx_task_settings->nolog));
        $this->winsvc_run = p4field::isyes(trim($sx_task_settings->winsvc_run));

        parent::loadSettings($sx_task_settings);
    }

    private function getIndexer()
    {
        $binaries = $this->dependencyContainer['phraseanet.configuration']['binaries'];

        if (isset($binaries['phraseanet_indexer'])) {
            $cmd = $binaries['phraseanet_indexer'];
        } else {
            $finder = new ExecutableFinder();
            $cmd = $finder->find('phraseanet_indexer');
        }

        return $cmd;
    }

    /**
     *
     * @return void
     */
    protected function run2()
    {
        $cmd = $this->getIndexer();

        $nullfile = '/dev/null';
        $this->method = self::METHOD_PROC_OPEN;

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $nullfile = '/dev/null';
        }

        if ( ! file_exists($cmd) || ! is_executable($cmd)) {
            $this->setState(self::STATE_STOPPED);
            $this->log(sprintf('File \'%s\' does not exists', $cmd));
            throw new Exception('cindexer executable not found', self::ERR_EXECUTABLE_NOT_FOUND);

            return;
        }

        $args = array();
        $args_nopwd = array();
        if ($this->host) {
            $args[] = '-h=' . $this->host;
            $args_nopwd[] = '-h=' . $this->host;
        }
        if ($this->port) {
            $args[] = '-P=' . $this->port;
            $args_nopwd[] = '-P=' . $this->port;
        }
        if ($this->base) {
            $args[] = '-b=' . $this->base;
            $args_nopwd[] = '-b=' . $this->base;
        }
        if ($this->user) {
            $args[] = '-u=' . $this->user;
            $args_nopwd[] = '-u=' . $this->user;
        }
        if ($this->password) {
            $args[] = '-p=' . $this->password;
            $args_nopwd[] = '-p=xxxxxxx';
        }
        if ($this->socket) {
            $args[] = '--socket=' . $this->socket;
            $args_nopwd[] = '--socket=' . $this->socket;
        }

        $args[] = '-o';
        $args_nopwd[] = '-o';

        if ($this->charset) {
            $args[] = '--default-character-set=' . $this->charset;
            $args_nopwd[] = '--default-character-set=' . $this->charset;
        }
        if ($this->stem) {
            $args[] = '--stem=' . $this->stem;
            $args_nopwd[] = '--stem=' . $this->stem;
        }
        if ($this->sortempty) {
            $args[] = '--sort-empty=' . $this->sortempty;
            $args_nopwd[] = '--sort-empty=' . $this->sortempty;
        }
        if ($this->debugmask > 0) {
            $args[] = '-d=' . $this->debugmask;
            $args_nopwd[] = '-d=' . $this->debugmask;
        }
        if ($this->nolog) {
            $args[] = '-n';
            $args_nopwd[] = '-n';
        }
        if ($this->winsvc_run) {
            $args[] = '--run';
            $args_nopwd[] = '--run';
        }

        $logdir = $this->dependencyContainer['root.path'] . '/logs/';

        $this->new_status = NULL; // new status to set at the end
        $this->exception = NULL; // exception to throw at the end

        $this->log(sprintf("running cindexer with method %s", $this->method));
        switch ($this->method) {
            case self::METHOD_PROC_OPEN:
                $this->run_with_proc_open($cmd, $args, $args_nopwd);
                break;
            case self::METHOD_FORK:
                $this->run_with_fork($cmd, $args, $args_nopwd);
                break;
            case self::METHOD_EXEC:
                $this->run_with_exec($cmd, $args, $args_nopwd);
                break;
        }

        if ($this->new_status !== NULL) {
            $this->setState($this->new_status);
        }

        if ($this->exception) {
            throw $this->exception;
        }
    }

    private function run_with_proc_open($cmd, $args, $args_nopwd)
    {
        $nullfile = defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null';

        $descriptors = array();
        $descriptors[1] = array("file", $nullfile, "a+");
        $descriptors[2] = array("file", $nullfile, "a+");

        $pipes = array();

        $logcmd = escapeshellarg($cmd).' '.implode(' ', array_map('escapeshellarg', $args_nopwd));
        $execmd = escapeshellarg($cmd).' '.implode(' ', array_map('escapeshellarg', $args));

        $this->log(sprintf('cmd=\'%s\'', $logcmd));

        $process = proc_open($execmd, $descriptors, $pipes, dirname($cmd), null, array('bypass_shell' => true));

        $pid = NULL;
        if (is_resource($process)) {
            $proc_status = proc_get_status($process);
            if ($proc_status['running']) {
                $pid = $proc_status['pid'];
            }
        }
        $qsent = '';
        $timetokill = NULL;
        $sock = NULL;

        $this->running = true;

        while ($this->running) {
            if ($this->getState() == self::STATE_TOSTOP && $this->socket > 0) {
                // must quit task, so send 'Q' to port 127.0.0.1:XXXX to cindexer
                if ( ! $qsent && (($sock = socket_create(AF_INET, SOCK_STREAM, 0)) !== false)) {
                    if (socket_connect($sock, '127.0.0.1', $this->socket) === true) {
                        socket_write($sock, 'Q', 1);
                        socket_write($sock, "\r\n", strlen("\r\n"));
                        for ($i = 0; $this->running && $i < 5; $i ++) {
                            sleep(1);
                        }
                        $qsent = 'Q';
                        $timetokill = time() + 10;
                    } else {
                        socket_close($sock);
                        $sock = NULL;
                    }
                }
            }

            $proc_status = proc_get_status($process);
            if (! $proc_status['running']) {
                // the cindexer died
                if ($qsent == 'Q') {
                    $this->log('Phrasea indexer stopped');
                    $this->new_status = self::STATE_STOPPED;
                } elseif ($qsent == 'K') {
                    $this->log('Phrasea indexer has been killed');
                    $this->new_status = self::STATE_STOPPED;
                } else {
                    $this->log('Phrasea indexer crashed');
                    $this->exception = new Exception('cindexer crashed', self::ERR_CRASHED);
                    // do not change the status so scheduler may restart it
                }
                $this->running = false;
            } else {
                // the cindexer is still alive
                if ($qsent == 'Q') {
                    if (time() > $timetokill) {
                        // must kill cindexer
                        $this->log('Sending kill signal to Phrasea indexer');
                        $qsent = 'K';
                        proc_terminate($process); // sigint
                    }
                }
            }
            for ($i = 0; $this->running && $i < 5; $i ++) {
                sleep(1);
            }
        }

        if ($sock) {
            socket_close($sock);
            $sock = NULL;
        }

        foreach (array_keys($pipes) as $offset) {
            if (is_resource($pipes[$offset])) {
                fclose($pipes[$offset]);
            }
        }

        proc_terminate($process); // sigint
        proc_close($process);
    }

    private function run_with_fork($cmd, $args, $args_nopwd)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->exception = new Exception('cindexer can\'t fork', self::ERR_CANT_FORK);
        } elseif ($pid == 0) {
            // child
            umask(0);
            if (($err = posix_setsid()) < 0) {
                $this->exception = new Exception('cindexer can\'t detach from terminal', $err);
            } else {
                chdir(dirname(__FILE__));
                pcntl_exec($cmd, $args);
                sleep(2);
            }
        } else {
            // parent
            $this->running = true;

            $sigsent = NULL;
            while ($this->running) {
                // is the cindexer alive ?
                if ( ! posix_kill($pid, 0)) {
                    // dead...
                    if ($sigsent === NULL) {
                        // but it's not my fault
                        $this->log('Phrasea indexer crashed');
                        $this->exception = new Exception('cindexer crashed', self::ERR_CRASHED);
                        // do not change the status so scheduler may restart it
                        break;
                    }
                }

                if ($this->getState() == self::STATE_TOSTOP) {
                    posix_kill($pid, ($sigsent = SIGINT));
                    $timetokill = time() + 10;
                    sleep(2);
                }

                $status = NULL;
                if (pcntl_wait($status, WNOHANG) == $pid) {
                    // child (indexer) has exited
                    if ($sigsent == SIGINT) {
                        $this->log('Phrasea indexer stopped');
                        $this->new_status = self::STATE_STOPPED;
                    } elseif ($sigsent == SIGKILL) {
                        $this->log('Phrasea indexer has been killed');
                        $this->new_status = self::STATE_STOPPED;
                    } else {
                        $this->log('Phrasea indexer crashed');
                        $this->exception = new Exception('cindexer crashed', self::ERR_CRASHED);
                        // do not change the status so scheduler may restart it
                    }
                    $this->running = false;
                } else {
                    if ($sigsent == SIGINT && time() > $timetokill) {
                        // must kill cindexer
                        $this->log('Kill signal sent to Phrasea indexer');
                        posix_kill($pid, ($sigsent = SIGKILL));
                    }
                    sleep(2);
                }
            } // while running
        }
    }

    private function run_with_exec($cmd, $args, $args_nopwd)
    {
        pcntl_exec($cmd, $args);
        sleep(2);
    }

    /**
     * @param array $params
     */
    public static function getDefaultSettings(Configuration $config, array $params = array())
    {
        $database = $config['main']['database'];

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n"
            ."<host>" . $database['host'] . "</host><port>"
            . $database['port'] . "</port><base>"
            . $database['dbname'] . "</base><user>"
            . $database['user'] . "</user><password>"
            . $database['password'] . "</password><socket>25200</socket>"
            . "<use_sbas>1</use_sbas><nolog>0</nolog><clng></clng>"
            . "<winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>";
    }
}
