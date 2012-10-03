<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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
    protected $use_sbas;

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
    protected $nolog;

    /**
     *
     * @var string
     */
    protected $winsvc_run;

    /**
     *
     * @var string
     */
    protected $binpath;

    /**
     *
     * @return string
     */
    public function getName()
    {
        return(_("task::cindexer:Indexation"));
    }

    /**
     *
     * @return string
     */
    public function help()
    {
        return(_("task::cindexer:indexing records"));
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
            'binpath', 'host', 'port', 'base', 'user', 'password', 'socket', 'use_sbas', 'nolog', 'clng', 'winsvc_run', 'charset', 'debugmask'
        );
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        if ($dom->loadXML($oldxml)) {
            $xmlchanged = false;
            foreach (array("str:binpath", "str:host", "str:port", "str:base", "str:user", "str:password", "str:socket", "boo:use_sbas", "boo:nolog", "str:clng", "boo:winsvc_run", "str:charset", 'str:debugmask') as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0)) != NULL) {
                    // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                    while (($n = $ns->firstChild)) {
                        $ns->removeChild($n);
                    }
                } else {
                    // le champ n'existait pas dans le xml, on le cr�e
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
     * @param  string $xml
     * @param  string $form
     * @return string
     */
    public function xml2graphic($xml, $form)
    {
        if (($sxml = simplexml_load_string($xml)) != FALSE) { // in fact XML IS always valid here...
            ?>
            <script type="text/javascript">
            <?php echo $form ?>.binpath.value      = "<?php echo p4string::MakeString($sxml->binpath, "js", '"') ?>";
            <?php echo $form ?>.host.value         = "<?php echo p4string::MakeString($sxml->host, "js", '"') ?>";
            <?php echo $form ?>.port.value         = "<?php echo p4string::MakeString($sxml->port, "js", '"') ?>";
            <?php echo $form ?>.base.value         = "<?php echo p4string::MakeString($sxml->base, "js", '"') ?>";
            <?php echo $form ?>.user.value         = "<?php echo p4string::MakeString($sxml->user, "js", '"') ?>";
            <?php echo $form ?>.socket.value       = "<?php echo p4string::MakeString($sxml->socket, "js", '"') ?>";
            <?php echo $form ?>.password.value     = "<?php echo p4string::MakeString($sxml->password, "js", '"') ?>";
            <?php echo $form ?>.clng.value         = "<?php echo p4string::MakeString($sxml->clng, "js", '"') ?>";
            <?php echo $form ?>.use_sbas.checked   = <?php echo trim((string) $sxml->use_sbas) != '' ? (p4field::isyes($sxml->use_sbas) ? 'true' : 'false') : 'true' ?>;
            <?php echo $form ?>.nolog.checked      = <?php echo p4field::isyes($sxml->nolog) ? 'true' : 'false' ?>;
            <?php echo $form ?>.winsvc_run.checked = <?php echo p4field::isyes($sxml->winsvc_run) ? 'true' : 'false' ?>;
            <?php echo $form ?>.charset.value      = "<?php echo trim((string) $sxml->charset) != '' ? p4string::MakeString($sxml->charset, "js", '"') : 'utf8' ?>";
            <?php echo $form ?>.debugmask.value    = "<?php echo trim((string) $sxml->debugmask) != '' ? p4string::MakeString($sxml->debugmask, "js", '"') : '0' ?>";
                parent.calccmd();
            </script>
            <?php
            return("");
        } else { // ... so we NEVER come here
            // bad xml
            return("BAD XML");
        }
    }

    /**
     *
     * @return void
     */
    public function printInterfaceJS()
    {
        $appname = 'phraseanet_indexer';
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $appname .= '.exe';
        }
        ?>
        <script type="text/javascript">
            function calccmd()
            {
                var cmd = '';
                with(document.forms['graphicForm'])
                {
                    cmd += binpath.value + "/<?php echo $appname ?>";
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
                    if(debugmask.value)
                        cmd += " -d=" + debugmask.value;
                    if(winsvc_run.checked)
                        cmd += " --run";
                }
                document.getElementById('cmd').innerHTML = cmd;
            }
            function chgxmltxt(textinput, fieldname)
            {
                calccmd();
                setDirty();
            }
            function chgxmlck(checkinput, fieldname)
            {
                calccmd();
                setDirty();
            }
            function chgxmlpopup(popupinput, fieldname)
            {
                calccmd();
                setDirty();
            }
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
        $appname = 'phraseanet_indexer';
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $appname .= '.exe';
        }
        ob_start();
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <br/>
        <?php echo _('task::cindexer:executable') ?>&nbsp;:&nbsp;
            <input type="text" name="binpath" style="width:300px;" onchange="chgxmltxt(this, 'binpath');" value="">&nbsp;/&nbsp;<?php echo $appname ?>
            <br/>
        <?php echo _('task::cindexer:host') ?>&nbsp;:&nbsp;<input type="text" name="host" style="width:100px;" onchange="chgxmltxt(this, 'host');" value="">
            <br/>
            <?php echo _('task::cindexer:port') ?>&nbsp;:&nbsp;<input type="text" name="port" style="width:100px;" onchange="chgxmltxt(this, 'port');" value="">
            <br/>
            <?php echo _('task::cindexer:base') ?>&nbsp;:&nbsp;<input type="text" name="base" style="width:200px;" onchange="chgxmltxt(this, 'base');" value="">
            <br/>
            <?php echo _('task::cindexer:user') ?>&nbsp;:&nbsp;<input type="text" name="user" style="width:200px;" onchange="chgxmltxt(this, 'user');" value="">
            <br/>
            <?php echo _('task::cindexer:password') ?>&nbsp;:&nbsp;<input type="password" name="password" style="width:200px;" onchange="chgxmltxt(this, 'password');" value="">
            <br/>
            <br/>

        <?php echo _('task::cindexer:control socket') ?>&nbsp;:&nbsp;<input type="text" name="socket" style="width:50px;" onchange="chgxmltxt(this, 'socket');" value="">
            <br/>
            <?php echo _('task::cindexer:Debug mask') ?>&nbsp;:&nbsp;<input type="text" name="debugmask" style="width:50px;" onchange="chgxmltxt(this, 'debugmask');" value="">
            <br/>
            <br/>

            <div style="display:none;">
                <input type="checkbox" name="use_sbas" onclick="chgxmlck(this, 'old');">&nbsp;<?php echo _('task::cindexer:use table \'sbas\' (unchecked: use \'xbas\')') ?>
                <br/>
            </div>

        <?php echo _('task::cindexer:MySQL charset') ?>&nbsp;:&nbsp;<input type="text" name="charset" style="width:100px;" onchange="chgxmltxt(this, 'charset');" value="">
            <br/>

            <input type="checkbox" name="nolog" onclick="chgxmlck(this, 'nolog');">&nbsp;<?php echo _('task::cindexer:do not (sys)log, but out to console)') ?>
            <br/>

        <?php echo _('task::cindexer:default language for new candidates') ?>&nbsp;:&nbsp;<input type="text" name="clng" style="width:50px;" onchange="chgxmltxt(this, 'clng');" value="">
            <br/>
            <br/>

            <hr/>

            <br/>
        <?php echo _('task::cindexer:windows specific') ?>&nbsp;:<br/>
            <input type="checkbox" name="winsvc_run" onclick="chgxmlck(this, 'run');">&nbsp;<?php echo _('task::cindexer:run as application, not as service') ?>
            <br/>

        </form>
        <br>
        <center>
            <div style="margin:10px; padding:5px; border:1px #000000 solid; font-family:monospace; font-size:16px; text-align:left; color:#00e000; background-color:#404040" id="cmd">cmd</div>
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
        $this->use_sbas = p4field::isyes(trim($sx_task_settings->use_sbas));
        $this->charset = trim($sx_task_settings->charset);
        $this->debugmask = (int) (trim($sx_task_settings->debugmask));
        $this->nolog = p4field::isyes(trim($sx_task_settings->nolog));
        $this->winsvc_run = p4field::isyes(trim($sx_task_settings->winsvc_run));
        $this->binpath = p4string::addEndSlash(trim($sx_task_settings->binpath));

        parent::loadSettings($sx_task_settings);
    }

    /**
     *
     * @return void
     */
    protected function run2()
    {
        $cmd = $this->binpath . 'phraseanet_indexer';

        $nullfile = '/dev/null';
        $this->method = self::METHOD_PROC_OPEN;

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $nullfile = '/dev/null';
            $cmd .= '.exe';
        }

        if ( ! file_exists($cmd) || ! is_executable($cmd)) {
            $this->setState(self::STATE_STOPPED);
            $this->log(sprintf(_('task::cindexer:file \'%s\' does not exists'), $cmd));
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

        $registry = registry::get_instance();
        $logdir = p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');

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

        $logcmd =  self::escapeShellCmd($cmd) ;
        foreach ($args_nopwd as $arg) {
            $logcmd .= ' ' . self::escapeShellArg($arg);
        }

        $this->log(sprintf('cmd=\'%s\'', $logcmd));

        $execmd =  self::escapeShellCmd($cmd) ;
        foreach ($args as $arg) {
            $execmd .= ' ' . self::escapeShellArg($arg);
        }
        $process = proc_open($execmd, $descriptors, $pipes, $this->binpath, null, array('bypass_shell' => true));

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
                        for ($i = 0; $this->running && $i < 5; $i ++ ) {
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
            if ( ! $proc_status['running']) {
                // the cindexer died
                if ($qsent == 'Q') {
                    $this->log(_('task::cindexer:the cindexer clean-quit'));
                    $this->new_status = self::STATE_STOPPED;
                } elseif ($qsent == 'K') {
                    $this->log(_('task::cindexer:the cindexer has been killed'));
                    $this->new_status = self::STATE_STOPPED;
                } else {
                    $this->log(_('task::cindexer:the cindexer crashed'));
                    $this->exception = new Exception('cindexer crashed', self::ERR_CRASHED);
                    // do not change the status so scheduler may restart it
                }
                $this->running = false;
            } else {
                // the cindexer is still alive
                if ($qsent == 'Q') {
                    if (time() > $timetokill) {
                        // must kill cindexer
                        $this->log(_('task::cindexer:killing the cindexer'));
                        $qsent = 'K';
                        proc_terminate($process); // sigint
                    }
                }
            }
            for ($i = 0; $this->running && $i < 5; $i ++ ) {
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
                        $this->log(_('task::cindexer:the cindexer crashed'));
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
                        $this->log(_('task::cindexer:the cindexer clean-quit'));
                        $this->new_status = self::STATE_STOPPED;
                    } elseif ($sigsent == SIGKILL) {
                        $this->log(_('task::cindexer:the cindexer has been killed'));
                        $this->new_status = self::STATE_STOPPED;
                    } else {
                        $this->log(_('task::cindexer:the cindexer crashed'));
                        $this->exception = new Exception('cindexer crashed', self::ERR_CRASHED);
                        // do not change the status so scheduler may restart it
                    }
                    $this->running = false;
                } else {
                    if ($sigsent == SIGINT && time() > $timetokill) {
                        // must kill cindexer
                        $this->log(_('task::cindexer:killing the cindexer'));
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
}
