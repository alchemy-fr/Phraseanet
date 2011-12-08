Phraseanet - Digital Asset Management application
=================================================

#Installation

**Nginx**

<pre>
server {
  listen       80;
  server_name  subdeomain.domain.tld;
  root         /path/to/Phraseanet/www;

  index  index.php;


  location /web {
    alias /home/grosroro/workspace/Phraseanet-Trunk/datas/web;
  }
  location /download {
    internal;
    alias /home/grosroro/workspace/Phraseanet-Trunk/tmp/download;
  }
  location /lazaret {
    internal;
    alias /home/grosroro/workspace/Phraseanet-Trunk/tmp/lazaret;
  }
}
</pre>

#Pimp my install

**xsendfile**
<pre>
  location /protected {
    internal;
    alias /home/grosroro/workspace/Phraseanet-Trunk/datas/noweb/;
  }
</pre>

**MP4 pseudo stream**
<pre>
  location /mp4_video {
    internal;
    mp4;
    alias /home/grosroro/workspace/Phraseanet-Trunk/datas/noweb/;
  }

  location /mp4_videos {
    secure_download on;
    secure_download_secret S3cre3t;
    secure_download_path_mode file;

    if ($secure_download = "-1") {
      return 403;
    }
    if ($secure_download = "-2") {
      return 403;
    }
    if ($secure_download = "-3") {
      return 500;
    }
    rewrite ^/mp4_videos(.*)/[0-9a-zA-Z]*/[0-9a-zA-Z]*$ /mp4_video$1 last;
  }
</pre>

#RESTFULL APIs

See the [online developer reference] [1]

#License

Phraseanet is licensed under GPL-v3 license.

[1]: http://developer.phraseanet.com/