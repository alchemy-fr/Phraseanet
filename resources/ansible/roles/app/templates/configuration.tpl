servername: 'http://local.phrasea/'
languages:
    available: []
    default: 'fr'
main:
    maintenance: false
    languages: []
    key: ''
    api_require_ssl: true
    database:
        host: 127.0.0.1
        port: 3306
        user: '{{ mariadb.user }}'
        password: '{{ mariadb.password }}'
dbname: '{{ mariadb.appbox_db }}'
        driver: pdo_mysql
        charset: UTF8
    database-test:
        driver: pdo_sqlite
        path: '/tmp/db.sqlite'
        charset: UTF8
    cache:
        type: MemcacheCache
        options:
            host: localhost
            port: 11211
    search-engine:
        type: phrasea
        options: []
    task-manager:
        status: started
        enabled: true
        options:
            protocol: tcp
            host: 127.0.0.1
            port: 6660
            linger: 500
        logger:
            max-files: 10
            enabled: true
            level: INFO
    session:
        type: 'file'
        options: []
        ttl: 86400
    binaries:
        ghostscript_binary: null
        php_binary: null
        swf_extract_binary: null
        pdf2swf_binary: null
        swf_render_binary: null
        unoconv_binary: null
        ffmpeg_binary: null
        ffprobe_binary: null
        mp4box_binary: null
        pdftotext_binary: null
        ffmpeg_timeout: 3600
        ffprobe_timeout: 60
        gs_timeout: 60
        mp4box_timeout: 60
        swftools_timeout: 60
        unoconv_timeout: 60
    task-manager:
        status: started
        listener:
            protocol: tcp
            host: 127.0.0.1
            port: 6700
    storage:
        subdefs: null
        cache: null
        log : null
        download: null
        lazaret: null
        caption: null
    bridge:
        youtube:
            enabled: false
            client_id: null
            client_secret: null
            developer_key: null
        flickr:
            enabled: false
            client_id: null
            client_secret: null
        dailymotion:
            enabled: false
            client_id: null
            client_secret: null
debugger:
    allowed-ips:
        - 192.168.56.1
border-manager:
    enabled: true
    extension-mapping: { }
    checkers:
        -
            type: Checker\Sha256
            enabled: true
        -
            type: Checker\UUID
            enabled: true
        -
            type: Checker\Colorspace
            enabled: false
            options:
                colorspaces: [cmyk, grayscale, rgb]
        -
            type: Checker\Dimension
            enabled: false
            options:
                width: 80
                height: 160
        -
            type: Checker\Extension
            enabled: false
            options:
                extensions: [jpg, jpeg, bmp, tif, gif, png, pdf, doc, odt, mpg, mpeg, mov, avi, xls, flv, mp3, mp2]
        -
            type: Checker\Filename
            enabled: false
            options:
                sensitive: true
        -
            type: Checker\MediaType
            enabled: false
            options:
                mediatypes: [Audio, Document, Flash, Image, Video]
authentication:
    auto-create:
        templates: {  }
    captcha:
        enabled: true
        trials-before-display: 9
    providers:
        facebook:
            enabled: false
            display: false
            title: Facebook
            type: facebook
            options:
                app-id: ''
                secret: ''
                default-graph-version: 'v2.10'
        twitter:
            enabled: false
            display: false
            title: Twitter
            type: twitter
            options:
                consumer-key: ''
                consumer-secret: ''
        github:
            enabled: false
            display: false
            title: Github
            type: github
            options:
                client-id: ''
                client-secret: ''
        viadeo:
            enabled: false
            display: false
            title: Viadeo
            type: viadeo
            options:
                client-id: ''
                client-secret: ''
        linkedin:
            enabled: false
            display: false
            title: LinkedIN
            type: linkedin
            options:
                client-id: ''
                client-secret: ''
        ps_auth_1:
            enabled: false
            display: false
            title: 'PS Auth 1'
            type: 'ps-auth'
            options:
                client-id: ''
                client-secret: ''
                base-url: 'https://api-auth.phrasea.local'
                provider-type: 'oauth'
                provider-name: 'v2'
                icon-uri: null
                debug: false
                birth-group: '_firstlog'
                everyone-group: '_everyone'
                metamodel: '_metamodel'
                model-gpfx: '_M_'
                model-upfx: '_U_'
                auto-logout: false
                auto-connect-idp-name: null
registration-fields:
    -
        name: company
        required: true
    -
        name: lastname
        required: true
    -
        name: firstname
        required: true
    -
        name: geonameid
        required: true
xsendfile:
    enabled: false
    type: nginx
    mapping: []
h264-pseudo-streaming:
    enabled: false
    type: nginx
    mapping: []
plugins: []
api_cors:
  enabled: false
  allow_credentials: false
  allow_origin: []
  allow_headers: []
  allow_methods: []
  expose_headers: []
  max_age: 0
  hosts: []
session:
  idle: 0
  lifetime: 604800 # 1 week
crossdomain:
    site-control: 'master-only'
    allow-access-from:
        -
            domain: '*.example.com'
            secure: 'false'
        -
            domain: 'www.example.com'
            secure: 'true'
            to-ports: '507,516-523'
    allow-access-from-identity:
        -
            fingerprint-algorithm: 'sha-1'
            fingerprint: '01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67'
        -
            fingerprint-algorithm: 'sha256'
            fingerprint: '01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67'
    allow-http-request-headers-from:
        -
            domain: '*.bar.com'
            secure: 'true'
            headers: 'SOAPAction, X-Foo*'
        -
            domain: 'foo.example.com'
            secure: 'false'
            headers: 'Authorization,X-Foo*'
embed_bundle:
    video:
        player: videojs
        autoplay: false
        coverSubdef: previewx4
        available-speeds:
            - 1
            - 1.5
            - 3
    audio:
        player: videojs
        autoplay: false
    document:
        player: flexpaper
        enable-pdfjs: true
geocoding-providers:
  -
    name: 'mapBox'
    public-key: ''
