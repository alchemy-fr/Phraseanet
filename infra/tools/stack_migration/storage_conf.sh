#bin/bash

cd "/var/alchemy/Phraseanet"

echo `date +"%Y-%m-%d %H:%M:%S"` - "update binaries path in accordance of docker stack"

bin/setup system:config -s set main.binaries.php_binary "/usr/local/bin/php"
bin/setup system:config -s set main.binaries.ghostscript_binary "/usr/bin/gs"
bin/setup system:config -s set main.binaries.swf_extract_binary "/usr/bin/swfextract"
bin/setup system:config -s set main.binaries.pdf2swf_binary null
bin/setup system:config -s set main.binaries.swf_render_binary "/usr/bin/swfrender"
bin/setup system:config -s set main.binaries.unoconv_binary "/usr/bin/unoconv"
bin/setup system:config -s set main.binaries.ffmpeg_binary "/usr/local/bin/ffmpeg"
bin/setup system:config -s set main.binaries.ffprobe_binary "/usr/local/bin/ffprobe"
bin/setup system:config -s set main.binaries.mp4box_binary "/usr/bin/MP4Box"
bin/setup system:config -s set main.binaries.pdftotext_binary "/usr/bin/pdftotext"

echo `date +"%Y-%m-%d %H:%M:%S"` - "binaries path applied"

echo `date +"%Y-%m-%d %H:%M:%S"` - "update storage path in accordance of docker stack"

bin/setup system:config -s set main.storage.subdefs "/var/alchemy/Phraseanet/datas"
bin/setup system:config -s set main.storage.cache "/var/alchemy/Phraseanet/cache"
bin/setup system:config -s set main.storage.log "/var/alchemy/Phraseanet/logs"
bin/setup system:config -s set main.storage.download "/var/alchemy/Phraseanet/datas/download"
bin/setup system:config -s set main.storage.lazaret "/var/alchemy/Phraseanet/datas/lazaret"
bin/setup system:config -s set main.storage.caption "/var/alchemy/Phraseanet/tmp/caption"
bin/setup system:config -s set main.storage.worker_tmp_files "/var/alchemy/Phraseanet/tmp/worker"

echo `date +"%Y-%m-%d %H:%M:%S"` - "storage path path applied"

cd -
