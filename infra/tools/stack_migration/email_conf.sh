#bin/bash
cd "/var/alchemy/Phraseanet"   

echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet SMTP"

if [[ $PHRASEANET_SMTP_ENABLED && $PHRASEANET_SMTP_ENABLED = true ]]; then
  bin/setup system:config -s set registry.email.smtp-enabled $PHRASEANET_SMTP_ENABLED
  bin/setup system:config -s set registry.email.smtp-auth-enabled $PHRASEANET_SMTP_AUTH_ENABLED
  bin/setup system:config -s set registry.email.smtp-secure-mode $PHRASEANET_SMTP_SECURE_MODE
  bin/setup system:config -s set registry.email.smtp-host $PHRASEANET_SMTP_HOST
  bin/setup system:config -s set registry.email.smtp-port $PHRASEANET_SMTP_PORT
  bin/setup system:config -s set registry.email.smtp-user $PHRASEANET_SMTP_USER
  bin/setup system:config -s set registry.email.smtp-password $PHRASEANET_SMTP_PASSWORD
  bin/setup system:config -s set registry.email.emitter-email $PHRASEANET_EMITTER_EMAIL
fi

cd -

echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet SMTP setup applied"
