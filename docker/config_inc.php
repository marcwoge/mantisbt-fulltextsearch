<?php
# MantisBT configuration for the local Docker test environment.
# FOR LOCAL TESTING ONLY - do not use these settings/credentials in production.

# --- Database (matches docker-compose.yml) --------------------------------
$g_hostname      = 'db';
$g_db_type       = 'mysqli';
$g_database_name = 'bugtracker';
$g_db_username   = 'mantisbt';
# Password comes from .env via docker-compose.yml (MANTIS_DB_PASSWORD).
$g_db_password   = (string) getenv('MANTIS_DB_PASSWORD');

# --- General --------------------------------------------------------------
$g_default_timezone   = 'Europe/Berlin';
# Required by MantisBT (>= 16 chars); set MANTIS_CRYPTO_MASTER_SALT in .env.
$g_crypto_master_salt = (string) getenv('MANTIS_CRYPTO_MASTER_SALT');

# --- E-mail: route everything to the Mailpit catcher ----------------------
# 2 = PHPMAILER_METHOD_SMTP
$g_phpMailer_method          = 2;
$g_smtp_host                 = 'mailpit';
$g_smtp_port                 = 1025;
$g_smtp_connection_mode      = '';
$g_enable_email_notification = 1;
$g_administrator_email       = 'admin@example.com';
$g_webmaster_email           = 'webmaster@example.com';
$g_from_email                = 'noreply@example.com';
$g_return_path_email         = 'admin@example.com';
$g_from_name                 = 'MantisBT (Test)';

# Send all notifications immediately so the test cron output is easy to follow.
# 0 = OFF (do not defer e-mails to a separate cron job).
$g_email_send_using_cronjob = 0;
