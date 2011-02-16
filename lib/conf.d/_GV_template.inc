<?php

$cwd = str_replace('\\', '/', dirname(__FILE__));
if (($pos = strpos($cwd, 'lib/conf.d')) !== false)
  $cwd = substr($cwd, 0, $pos);

$EOL = PHP_EOL;

$GV_sit = md5(time() . '--' . mt_rand(1000000, 9999999));

include_once dirname(__FILE__) . "/../../lib/classes/user.class.php";
$lngs = user::avLanguages();
$avLanguages = array();
foreach ($lngs as $lng => $locales)
{
  foreach ($locales as $loc => $locname)
  {
    $avLanguages[$loc] = $locname['name'];
  }
}






$PHP_CONF = array(
    'output_buffering' => '4096'  //INI_ALL
    , 'memory_limit' => '512M'  //INI_ALL
    , 'error_reporting' => '6143'  //INI_ALL
    , 'default_charset' => 'UTF-8'  //INI_ALL
    , 'session.use_cookies' => '1'   //INI_ALL
    , 'session.use_only_cookies' => '1'   //INI_ALL
    , 'session.auto_start' => '0'   //INI_ALL
    , 'session.hash_function' => '1'   //INI_ALL
    , 'session.hash_bits_per_character' => '6'  //INI_ALL
    , 'allow_url_fopen' => 'on'  //INI_ALL
    , 'display_errors' => 'off'  //INI_ALL
    , 'display_startup_errors' => 'off'  //INI_ALL
    , 'log_errors' => 'off'  //INI_ALL
);
$PHP_REQ = array(
    'safe_mode' => 'off'
    , 'file_uploads' => '1'
    , 'magic_quotes_runtime' => 'off'  //INI_ALL
    , 'magic_quotes_gpc' => 'off'  //INI_PER_DIR -- just for check
);



$GV = array(
    array(
        'section' => _('GV::section:: Serveur HTTP'),
        'vars' => array(
            array(
                'type' => 'string',
                'name' => 'GV_ServerName',
                'end_slash' => true,
                'comment' => _('reglages:: URL d\'installation'),
                'default' => isset($_SERVER['SERVER_NAME']) ? 'http://' . $_SERVER['SERVER_NAME'] : '',
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_RootPath',
                'end_slash' => true,
                'comment' => _('reglages:: Path d\'installation'),
                'default' => $cwd,
                'required' => true
            ),
            array(
                'type' => 'timezone',
                'name' => 'GV_timezone',
                'comment' => _('reglages:: Timezone de l\'installation'),
                'default' => 'Europe/Paris',
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_sit',
                'comment' => _('reglages:: Nom de linstallation'),
                'default' => $GV_sit,
                'readonly' => true,
                'required' => true
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_default_lng',
                'comment' => _('reglages:: Langue par defaut'),
                'default' => 'fr_FR',
                'avalaible' => $avLanguages,
                'required' => true
            )
        )
    ), array(
        'section' => _('GV::section:: Etat de maintenance'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_maintenance',
                'comment' => _('reglages:: Activation de la maintenance'),
                'default' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GV_message',
                'comment' => _('reglages:: Message a diffuser aux utilisateurs'),
                'default' => "May the force be with you"
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_message_on',
                'comment' => _('reglages:: activation du message a diffuser aux utilistaeurs'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_debug',
                'comment' => _('reglages:: activation des messages de debug et des erreurs serveur'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_log_errors',
                'comment' => _('reglages:: logguer les erreurs'),
                'default' => false
            )
        )
    ), array(
        'section' => _('GV::section:: Connectivite aux webservices'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_google_api',
                'comment' => _('reglages:: Utiliser els google apis'),
                'default' => true,
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_bitly_user',
                'comment' => _('reglages:: Nom d\'utilisateur pour l\'api bit.ly'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_bitly_key',
                'comment' => _('reglages:: ccle d\'api pour l\'api bit.ly'),
                'default' => '',
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_captchas',
                'comment' => _('reglages:: Utilisation de l\'api recpatcha'),
                'default' => false,
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_captcha_public_key',
                'comment' => _('reglages:: clef publique recaptcha'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_captcha_private_key',
                'comment' => _('reglages:: clef privee recaptcha'),
                'default' => ''
            )
        )
    ), array(
        'section' => _('GV::section:: Stockage des documents'),
        'vars' => array(
            array(
                'type' => 'string',
                'name' => 'GV_appletAllowedFileExt',
                'comment' => _('reglages:: extensions de fichier autorisees en upload, separees par des virgules. * pour autoriser tous les fichiers'),
                'default' => 'jpg,jpeg,bmp,tif,gif,png,pdf,doc,odt,mpg,mpeg,mov,avi,xls,flv,mp3,mp2',
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_base_datapath_web',
                'end_slash' => true,
                'comment' => _('reglages:: dossier de stockage des vignettes publiees en acces direct'),
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_base_datapath_noweb',
                'end_slash' => true,
                'comment' => _('reglages:: dossier de stockage des fichiers proteges de l\'acces direct'),
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_base_dataurl',
                'comment' => _('reglages:: point de montage des vignettes publiees en acces direct'),
                'default' => 'web',
                'end_slash' => false,
                'required' => true
            )
        )
    ), array(
        'section' => _('GV::section:: Serveurs de cache'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_use_cache',
                'comment' => _('reglages:: Utiliser un serveur memcache'),
                'default' => false,
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_memcached',
                'comment' => _('setup:: Serveur Memcached'),
                'default' => '127.0.0.1',
                'required' => false
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_memcached_port',
                'comment' => _('reglages:: port du serveur memcached'),
                'default' => 11211
            )
        )
    ), array(
        'section' => _('GV::section:: Executables externes'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_modxsendfile',
                'comment' => _('reglages:: mod_xsendfileapache active'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_X_Accel_Redirect',
                'comment' => _('reglages:: Path en acces pour X-Accel-Redirect (NginX Uniquement)'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_X_Accel_Redirect_mount_point',
                'comment' => _('reglages:: Point de montage pour X-Accel-Redirect (NginX Uniquement)'),
                'default' => 'noweb',
                'end_slash' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_h264_streaming',
                'comment' => _('reglages:: activation du stream h264 via mod_token - attention, necessite les modules apache mod_h264_streaming et mod_auth_token'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_mod_auth_token_directory',
                'end_slash' => true,
                'comment' => _('reglages:: point de montage du dossier protege via auth_token'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_mod_auth_token_directory_path',
                'end_slash' => false,
                'comment' => _('reglages:: path complet du dossier protege via auth_token'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_mod_auth_token_passphrase',
                'comment' => _('reglages:: passphrase du mod_auth_token (definie dans le fichier de configuration apache)'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_cli',
                'comment' => _('reglages:: executable PHP CLI'),
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_PHP_INI',
                'comment' => _('reglages:: path du php.ini specifique (vide si non utilise)'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_imagick',
                'comment' => _('reglages:: chemin de l\'executable convert'),
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_pathcomposite',
                'comment' => _('reglages:: chemin de l\'executable composite'),
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_exiftool',
                'comment' => _('reglages:: chemin de l\'executable exiftools'),
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_swf_extract',
                'comment' => _('reglages:: chemin de l\'executable swfextract'),
                'default' => '',
                'required' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_pdf2swf',
                'comment' => _('reglages:: chemin de l\'executable pdf2swf'),
                'default' => '',
                'required' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_swf_render',
                'comment' => _('reglages:: chemin de l\'executable swfrender'),
                'default' => '',
                'required' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_unoconv',
                'comment' => _('reglages:: chemin de l\'executable unoconv'),
                'default' => '',
                'required' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ffmpeg',
                'comment' => _('reglages:: chemin de l\'executable FFmpeg'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_mp4box',
                'comment' => _('reglages:: chemin de l\'executable MP4Box'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_mplayer',
                'comment' => _('reglages:: chemin de l\'executable Mplayer'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_pdftotext',
                'comment' => _('reglages:: chemin de l\'executable pdftotext (xpdf)'),
                'default' => ''
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_pdfmaxpages',
                'comment' => _('reglages:: nombre maximum de page a extraire (PDF)'),
                'default' => 5
            )
        )
    ), array(
        'section' => _('GV::section:: Repertoires utilitaires'),
        'vars' => array(
            array(
                'type' => 'string',
                'name' => 'GV_filesOwner',
                'comment' => _('reglages:: proprietaire des fichiers'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_filesGroup',
                'comment' => _('reglages:: groupes des fichiers'),
                'default' => ''
            )
        )
    ), array(
        'section' => _('GV::section:: Configuration principale'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_logobeforebas',
                'comment' => _('reglages:: Voir les minilogos devant les noms de collection'),
                'default' => false,
                'required' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_adminMail',
                'comment' => _('reglages:: email de l\'administrateur'),
                'default' => 'support@alchemy.fr'
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_view_bas_and_coll',
                'comment' => _('reglages:: Afficher le nom des bases et des collections'),
                'default' => true,
                'required' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_choose_export_title',
                'comment' => _('reglages:: activer le choix du nom de fichier a l\'export'),
                'default' => false,
                'required' => true
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_default_export_title',
                'comment' => _('reglages:: choix par defaut des noms de fichier a l\'export'),
                'default' => 'support@alchemy.fr',
                'avalaible' => array('title' => 'Titre du document', 'original' => 'Nom original')
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_ArchiveSorted',
                'comment' => _('reglages:: trie des fichiers par ordre naturel a l\'import'),
                'default' => true,
                'required' => true
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_social_tools',
                'comment' => _('reglages:: Active les partages Facebook et Twitter'),
                'avalaible' => array('none' => 'Disabled', 'publishers' => 'Publishers', 'all' => 'Activated'),
                'default' => 'none',
                'required' => false
            )
        )
    ), array(
        'section' => _('GV::section:: Page d\'accueil'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_find_password',
                'comment' => _('reglages:: affiche le lien de renouvellement de mot de passe'),
                'default' => true,
                'required' => true
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_home_publi',
                'comment' => _('reglages:: presentation sur la home'),
                'default' => 'COOLIRIS',
                'avalaible' => array('DISPLAYx1' => 'slideshow', 'SCROLL' => 'Bandeau defilant', 'COOLIRIS' => 'Cooliris'),
                'required' => true
            )
        )
    ), array(
        'section' => _('GV::section:: Moteur de recherche'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'integer',
                'name' => 'GV_min_letters_truncation',
                'comment' => _('reglages:: Nombre minimal de lettre avec la troncature (Recherche)'),
                'default' => 1
            ),
            array(
                'type' => 'string',
                'name' => 'GV_defaultQuery',
                'comment' => _('reglages:: Question par defaut'),
                'default' => 'all'
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_defaultQuery_type',
                'comment' => _('reglages:: type de document cherches par defaut e l\'ouverture du site'),
                'avalaible' => array('0' => 'Documents', '1' => 'Regroupements'),
                'default' => '0'
            )
        )
    ), array(
        'section' => _('GV::section:: Report'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_anonymousReport',
                'comment' => _('reglages:: report anonyme (masque les infos concernant les utilisateurs)'),
                'default' => false,
                'required' => true
            )
        )
    ), array(
        'section' => _('GV::section:: Module d\'administration'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_admusr_id',
                'comment' => _('reglages:: Affichage de la colonne id (liste des utilisateurs)'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_admusr_name',
                'comment' => _('reglages:: Affichage de la colonne nom-prenom'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_admusr_company',
                'comment' => _('reglages:: Affichage de la colonne societe'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_admusr_mail',
                'comment' => _('reglages:: Affichage de la colonne mail'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_admusr_country',
                'comment' => _('reglages:: Affichage de la colonne pays'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_admusr_lastmodel',
                'comment' => _('reglages:: affichage de la colonne du dernier model passe sur l\'utilisateur (liste des utilisateurs)'),
                'default' => true
            )
        )
    ), array(
        'section' => _('GV::section:: Modules supplementaires'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_thesaurus',
                'comment' => _('reglages:: Activation de l\'outil thesaurus'),
                'default' => true
            ), array(
                'type' => 'boolean',
                'name' => 'GV_multiAndReport',
                'comment' => _('reglages:: Activation du Mode MultiDOC'),
                'default' => true
            ), array(
                'type' => 'boolean',
                'name' => 'GV_seeOngChgDoc',
                'comment' => _('reglages:: Substitution de HD d\'un record '),
                'default' => false
            ), array(
                'type' => 'boolean',
                'name' => 'GV_seeNewThumb',
                'comment' => _('reglages:: Substitution de thumbnail d\'un record'),
                'default' => false
            )
        )
    ), array(
        'section' => _('GV::section:: Authentification LDAP'),
        'rolled' => true,
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_ldap_enabled',
                'comment' => _('reglages:: Activation de l\'authentification LDAP '),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_addr',
                'comment' => _('reglages:: Adresse du serveur LDAP'),
                'default' => '127.0.0.1'
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_ldap_port',
                'comment' => _('reglages:: Port du serveur LDAP'),
                'default' => 389
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_ldap_userid_template',
                'comment' => _('reglages:: UserID Phraseanet du Template USER LDAP (obsolete)'),
                'default' => 4
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_user_template',
                'comment' => '',
                'default' => 'ldap_model'
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_ldap_bind_anonymous',
                'comment' => _('reglages:: Utilisation de BIND Anonyme pour la consultation (obsolete)'),
                'default' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_login_consult',
                'comment' => _('reglages:: Login de Consultation (vide=anonymous)'),
                'default' => ''
            ),
            array(
                'type' => 'password',
                'name' => 'GV_ldap_pwd_consult',
                'comment' => _('reglages:: Mot de passe de Consultation (vide=anonymous)'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_base_dn',
                'comment' => _('reglages:: Base DN du Groupe Phototheque'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_sfilter',
                'comment' => _('reglages:: Filtre de Recherche LDAP oe %LOGIN% sera substitue par le login de l\'utilisateur'),
                'default' => 'cn=%LOGIN%'
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_grp_base_dn',
                'comment' => '',
                'default' => 'o=Alchemy'
            ),
            array(
                'type' => 'string',
                'name' => 'GV_ldap_grp_sfilter',
                'comment' => _('reglages:: Filtre de recherche du groupe Phototheque'),
                'default' => '(ou=photo_*)'
            )
        )
    ), array(
        'section' => _('GV::section:: Envois de mails'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_exportmail',
                'comment' => _('reglages:: Activation de l\'export par mail'),
                'default' => true
            ),
            array(
                'type' => 'string',
                'name' => 'GV_defaulmailsenderaddr',
                'comment' => _('reglages:: Expediteur mail par defaut'),
                'default' => 'phraseanet@example.com'
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_smtp',
                'comment' => _('reglages:: Utilisation d\'un serveur SMTP'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_smtp_auth',
                'comment' => _('reglages:: Activation de l\'authentification smtp'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_smtp_host',
                'comment' => _('reglages:: Hote SMTP'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_smtp_port',
                'comment' => _('reglages:: Port SMTP'),
                'default' => ''
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_smtp_secure',
                'comment' => _('reglages:: Utiliser une conenction SSL'),
                'default' => false
            ),
            array(
                'type' => 'string',
                'name' => 'GV_smtp_user',
                'comment' => _('reglages:: User SMTP'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_smtp_password',
                'comment' => _('reglages:: Mot de passe SMTP'),
                'default' => ''
            )
        )
    ), array(
        'section' => _('GV::section:: Exports FTP'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_activeFTP',
                'comment' => _('reglages:: active la possibilite d\'exports FTP ou non (onglet dans multiexports)'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_ftp_for_user',
                'comment' => _('reglages:: Donne ou non export FTP aux utilisateurs ou e l\'admin uniquement'),
                'default' => false
            )
        )
    ), array(
        'section' => _('GV::section:: Configuration du client'),
        'vars' => array(
            array(
                'type' => 'enum',
                'name' => 'GV_bandeauHome',
                'comment' => _('reglages:: Affichage par defaut de la home du client'),
                'avalaible' => array('QUERY' => _('Question'), 'PUBLI' => _('Publications'), 'HELP' => _('Aide')),
                'default' => 'QUERY'
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_ong_search',
                'comment' => _('reglages:: position de l\'onglet de recherche'),
                'default' => 1
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_ong_advsearch',
                'comment' => _('reglages:: position de l\'onglet de recherche avancee'),
                'default' => 2
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_ong_topics',
                'comment' => _('reglages:: position de l\'onglet des topics'),
                'default' => 0
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_ong_actif',
                'comment' => _('reglages:: numero de l\'onglet actif'),
                'default' => 1
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_client_render_topics',
                'comment' => _('reglages:: rendu des topics'),
                'avalaible' => array('tree' => 'Arbres', 'popups' => 'Menus deroulants'),
                'default' => 'tree'
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_grp_preview_list',
                'comment' => _('reglages:: Voir les elements d\'un reportage sans Thumbnail (fenetre de preview)'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_rollover_reg_preview',
                'comment' => _('reglages:: Voir le rollover sur les regroupements (fenetre de preview)'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_rollover_chu',
                'comment' => _('reglages:: Voir le rollover sur les elements du chutier'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_zommPrev_rollover_clientAnswer',
                'comment' => _('reglages:: rollover sur les elements du client en recherche'),
                'default' => true
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_client_coll_ckbox',
                'comment' => _('reglages:: Presentation des collections'),
                'default' => 'checkbox',
                'avalaible' => array('popup' => 'Menu deroulant', 'checkbox' => 'case a cocher')
            ),
            array(
                'type' => 'enum',
                'name' => 'GV_client_showTitle',
                'comment' => _('reglages:: afficher le titre des imagettes'),
                'default' => 'top',
                'avalaible' => array('top' => 'haut', 'bottom' => 'bas', 'none' => 'aucun')
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_client_showMiniLogos',
                'comment' => _('reglages:: afficher le minilogo de coll dans le client'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_launchHDifNoPrev',
                'comment' => _('reglages:: Lancer HD sans prev si video ou pdf'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_viewSizeBaket',
                'comment' => _('reglages:: Voir taille HD total des doc d\'un chutiers'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_clientAutoShowProposals',
                'comment' => _('reglages:: Afficher automatiquement l\'onglet des propositions s\'il y\'en a'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_needAuth2DL',
                'comment' => _('reglages:: Il est necessaire d\'etre connecte pour pouvoir telecharger en compte invite'),
                'default' => true
            )
        )
    ), array(
        'section' => _('GV::section:: Parametrage de l\'inscription'),
        'vars' => array(
            array(
                'type' => 'boolean',
                'name' => 'GV_needMail',
                'comment' => _('reglages:: Une addresse email valide est necessaire pour l\'inscription online'),
                'default' => false
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_autoselectDB',
                'comment' => _('reglages:: A true, cette option descative le choix de selection des bases sur lesquelles on s\'inscrit, et l\'inscription se fait sur toutes les bases ou jai le droit de m\'inscrire'),
                'default' => true
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_autoregister',
                'comment' => _('reglages:: Activation de l\'autoinscription'),
                'default' => false
            )
        )
    ), array(
        'section' => _('GV::section:: Configuration du push'),
        'vars' => array(
            array(
                'type' => 'integer',
                'name' => 'GV_validation_reminder',
                'comment' => _('reglages:: Nombre de jour avant la fin de la validation pour envoie de mail de rappel'),
                'default' => 2
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_DL_checkit',
                'comment' => _('reglages:: Autorise le telechargement des fichiers pendant le process de validation'),
                'default' => true
            ),
            array(
                'type' => 'integer',
                'name' => 'GV_val_expiration',
                'comment' => _('reglages:: dure de validite des liens genere pour les  validations,  si 0 => validite permanente'),
                'default' => 10
            )
        )
    ), array(
        'section' => _('GV::section:: Indexation par les robots'),
        'vars' => array(
            array(
                'type' => 'string',
                'name' => 'GV_homeTitle',
                'comment' => _('reglages :: Titre de l\'installation'),
                'default' => 'Phraseanet IV'
            ),
            array(
                'type' => 'string',
                'name' => 'GV_metaKeywords',
                'comment' => _('reglages:: Mots clefs pour l\'indexation des robots de moteurs de recherche'),
                'default' => ''
            ),
            array(
                'type' => 'text',
                'name' => 'GV_metaDescription',
                'comment' => _('reglages :: Description de l\'installation'),
                'default' => ''
            ),
            array(
                'type' => 'string',
                'name' => 'GV_googleAnalytics',
                'comment' => _('reglages:: identifiant google analytics'),
                'default' => ''
            ),
            array(
                'type' => 'text',
                'name' => 'GV_robots_txt',
                'comment' => _('reglages:: Contenu du fichier robots.txt'),
                'default' => 'User-Agent: *
Allow: /'
            ),
            array(
                'type' => 'boolean',
                'name' => 'GV_display_gcf',
                'comment' => _('reglages:: Afficher le bandeau Google Chrome Frame'),
                'default' => true
            )
        )
    )
        )
?>
