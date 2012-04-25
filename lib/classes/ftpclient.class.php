<?php

class ftpclient
{
    protected $connexion;
    protected $proxy;
    protected $host;
    protected $cached_dirs = array();
    protected $debug = false;

    public function __construct($host, $port = 21, $timeout = 90, $ssl = false, $proxy = false, $proxyport = false)
    {
        $host = mb_substr($host, -1, 1) == '/' ? mb_substr($host, 0, (mb_strlen($host) - 1)) : $host;

        if (($p = mb_strpos($host, 'ftp://')) !== false)
            $host = mb_substr($host, 6);

        $host = $proxy ? $proxy : $host;
        $port = $proxyport ? $proxyport : $port;

        if ($this->debug && $proxy)
            echo "Utilisation du proxy $proxy\n<br>";

        if ($this->debug && $proxyport)
            echo "Utilisation du port proxy $proxyport\n<br>";

        $this->proxy = $proxy;
        $this->host = $host;

        if ($this->debug)
            echo "Ouverture de connection vers $host:$port timeout $timeout et proxy $proxy:$proxyport\n<br>";

        if (trim($host) == '') {
            throw new Exception('Nom d\'hote incorrect ' . $host);
        }

        if ($ssl === true) {
            if (($this->connexion = ftp_ssl_connect($host, $port, $timeout)) === false) {
                throw new Exception('Impossible de se connecter au serveur FTP en SSL');
            }
        } else {
            if (($this->connexion = ftp_connect($host, $port, $timeout)) === false) {
                throw new Exception('Impossible de se connecter au serveur FTP ' . $host . ":$port $timeout");
            }
        }

        return $this;
    }

    public function __destruct()
    {
        if ($this->connexion)
            $this->close();

        return;
    }

    public function login($username, $password)
    {
        $username = $this->proxy ? $username . "@" . $this->host : $username;

        $retry = 3;
        $done = false;

        if ($this->debug)
            echo "tentative de login avec $username, $password\n<br>";

        while ($retry > 0) {
            if ((ftp_login($this->connexion, $username, $password)) === false) {
                $retry --;
            } else {
                $retry = 0;
                $done = true;
            }
        }
        if ( ! $done) {
            throw new Exception('Impossible de s\'authentifier sur le serveur FTP');
        }

        return $this;
    }

    public function passive($boolean)
    {
        $boolean = ! ! $boolean;

        if ($this->debug)
            echo ($boolean ? 'des' : '') . "activation du mode passif\n<br>";

        if ((ftp_pasv($this->connexion, $boolean)) === false) {
            throw new Exception('Impossible de changer le mode passif');
        }

        return $this;
    }

    public function pwd()
    {

        if ($this->debug)
            echo "Recuperation du path working directory\n<br>";

        if (($pwd = ftp_pwd($this->connexion)) === false) {
            throw new Exception('Impossible de recuperer le path working directory');
        }
        $this->cached_dirs[$pwd] = $pwd;

        return $pwd;
    }

    public function chdir($directory)
    {
        $directory = $this->get_absolute_path($directory);
        if ($this->debug)
            echo "Changement de dossier vers $directory\n<br>";

        if ((ftp_chdir($this->connexion, $directory)) === false) {
            throw new Exception('Impossible de changer de dossier');
        }
        $this->pwd();

        return $this;
    }

    public function rmdir($remote_directory)
    {
        $remote_directory = $this->get_absolute_path($remote_directory);
        if ($this->debug)
            echo "Suppression du dossier $remote_directory\n<br>";

        if ((ftp_rmdir($this->connexion, $remote_directory)) === false) {
            throw new Exception('Impossible de supprimer le dossier');
        }

        unset($this->cached_dirs[$remote_directory]);

        return $this;
    }

    public function unlink($filepath)
    {
        $filepath = $this->get_absolute_path($filepath);
        if ($this->debug)
            echo "Suppression du fichier $filepath \n<br>";

        if ((ftp_delete($this->connexion, $filepath)) === false) {
            throw new Exception('Impossible de supprimer le fichier');
        }

        return $this;
    }

    public function rename($oldname, $newname)
    {
        $oldname = $this->get_absolute_path($oldname);
        $newname = $this->get_absolute_path($newname);
        if ($this->debug)
            echo "Renommage de $oldname en $newname\n<br>";

        if ((ftp_rename($this->connexion, $oldname, $newname)) === false) {
            throw new Exception('Impossible de renommer le dossier ou le fichier');
        }

        unset($this->cached_dirs[$oldname]);

        return $this;
    }

    public function mkdir($remote_directory)
    {
        $remote_directory = $this->get_absolute_path($remote_directory);
        if (isset($this->cached_dirs[$remote_directory])) {
            return $this;
        }

        if ($this->debug)
            echo "Creation du dossier $remote_directory\n<br>";

        if ((ftp_mkdir($this->connexion, $remote_directory)) === false) {
            throw new Exception('Impossible de creer le dossier');
        }
        $this->cached_dirs[$remote_directory] = $remote_directory;

        return $this;
    }

    public function put($remotefile, $localfile)
    {
        $remotefile = $this->get_absolute_path($remotefile);
        if ($this->debug)
            echo "Envoi du fichier par AUTORESUME $localfile vers $remotefile  de taille " . filesize($localfile) . "\n<br>";

        $ret = $this->nb_put($remotefile, $localfile);

        if ($this->debug)
            echo "APRES ESSAI EN AUTORESUME ON A retour:" . $ret . "\n<br>";

        if ($ret == FTP_FAILED) {
            if ($this->debug)
                echo "Resume seems not to be supported, try again from scratch\n<br>";
            try {
                $this->unlink($remotefile);
            } catch (Exception $e) {
                echo $e;
            }
            $ret = $this->nb_put($remotefile, $localfile, 0);

            if ($this->debug)
                echo "On doit avoir a la fin $remotefile de size " . filesize($localfile) . "\n<br>";
        }
        if ($ret != FTP_FINISHED) {
            throw new Exception('Erreur lors du transfert de fichier');
        }

        return $this;
    }

    protected function nb_put($remotefile, $localfile, $start = FTP_AUTORESUME)
    {
        $ret = ftp_nb_put($this->connexion, $remotefile, $localfile, FTP_BINARY, $start);

        while ($ret == FTP_MOREDATA) {
            set_time_limit(20);
            $ret = ftp_nb_continue($this->connexion);
        }

        return $ret;
    }

    public function get($localfile, $remotefile)
    {
        $remotefile = $this->get_absolute_path($remotefile);
        if ($this->debug)
            echo "Reception du fichier par AUTORESUME $remotefile vers $localfile \n<br>";

        $ret = $this->nb_get($localfile, $remotefile);

        if ($this->debug)
            echo "APRES ESSAI EN AUTORESUME ON A retour:" . $ret . "\n<br>";

        if ($ret == FTP_FAILED) {
            if ($this->debug)
                echo "Resume seems not to be supported, try again from scratch\n<br>";
            try {
                $this->unlink($localfile);
            } catch (Exception $e) {
                echo $e;
            }
            $ret = $this->nb_get($localfile, $remotefile, 0);

//      if($this->debug)
//        echo "On doit avoir a la fin $remotefile de size ".filesize($localfile)."\n<br>";
        }
        if ($ret != FTP_FINISHED) {
            throw new Exception('Erreur lors du transfert de fichier');
        }

        return $this;
    }

    public function delete($filepath)
    {
        if ( ! ftp_delete($this->connexion, $filepath))
            throw new Exception('Impossible de supprimer le fichier');

        return $this;
    }

    protected function nb_get($localfile, $remotefile, $start = FTP_AUTORESUME)
    {
        clearstatcache();
        if ( ! file_exists($localfile))
            $start = 0;

        $ret = ftp_nb_get($this->connexion, $localfile, $remotefile, FTP_BINARY, $start);

        while ($ret == FTP_MOREDATA) {
            set_time_limit(20);
            $ret = ftp_nb_continue($this->connexion);
        }

        return $ret;
    }

    public function system_type()
    {

        if ($this->debug)
            echo "Recuperation du type de systeme distant\n<br>";

        if (($systype = ftp_systype($this->connexion)) === false) {
            throw new Exception('Impossible de recuperer le type de systeme');
        }

        return $systype;
    }

    public function filesize($remotefile)
    {
        $remotefile = $this->get_absolute_path($remotefile);
        if ($this->debug)
            echo "Recuperation de la taille du fichier $remotefile\n<br>";

        if (($size = ftp_size($this->connexion, $remotefile)) === false) {
            throw new Exception('Impossible de recuperer la taille du fichier');
        }

        return $size;
    }

    public function close()
    {
        if ($this->debug)
            echo "Fermeture de la connexion\n<br>";
        if ( ! $this->connexion) {
            return $this;
        }

        if ((ftp_close($this->connexion)) === false) {
            throw new Exception('Impossible de fermer la connexion');
        }
        $this->connexion = null;

        return $this;
    }

    protected function is_absolute_path($path)
    {
        $absolute = false;

        if (substr($path, 0, 1) == '/')
            $absolute = true;

        return $absolute;
    }

    protected function get_absolute_path($file)
    {
        $file = str_replace('//', '/', $file);
        if ($this->is_absolute_path($file)) {
            return $file;
        }

        $pwd = $this->add_end_slash($this->pwd());

        return $pwd . $file;
    }

    public function add_end_slash($path)
    {
        $path = trim($path);

        if ($path == "" || $path == '/' || $path == '//') {
            return("/");
        }

        if (substr($path, -1, 1) != "/")
            $path .= "/";

        return($path);
    }

    public function list_directory($recursive = false)
    {
        $current_dir = $this->pwd();
        $contents = ftp_rawlist($this->connexion, $current_dir,  ! ! $recursive);

        $list = array();

        foreach ($contents as $content) {
            if ($content == '')
                continue;

            $info = preg_split("/[\s]+/", $content, 9);

            $is_dir = $info[0]{0} == 'd';
            if ($is_dir)
                continue;
            if ($content[0] == '/') {
                $current_dir = str_replace(':', '', $content);
                continue;
            }

            $file = $this->add_end_slash($current_dir) . $info[8];

            $date = strtotime($info[6] . ' ' . $info[5] . ' ' . $info[7]);
            if ( ! $date) {
                $date = strtotime($info[6] . ' ' . $info[5] . ' ' . date('Y') . ' ' . $info[7]);
            }

            $list[$file] = array(
                'date' => $date
            );
        }

        return $list;
    }
}

