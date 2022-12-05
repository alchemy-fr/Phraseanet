#bin/bash

# APP_ROOT="/var/alchemy/Phraseanet"

FTP_REPO="ftp"
BACKUP_REPO="backup"

cd "/var/alchemy/Phraseanet"

if [ ! -z "$STACK_NAME" ]; then
        touch $FTP_REPO"/ftp_enable_"$STACK_NAME
        echo "File created in : "$FTP_REPO"/ftp_enable_"$STACK_NAME
fi

if [ ! -z "$STACK_NAME" ]; then
        touch $BACKUP_REPO"/backup_repo_"$STACK_NAME
        echo "File created in : "$BACKUP_REPO"/backup_repo_"$STACK_NAME

fi

cd -

