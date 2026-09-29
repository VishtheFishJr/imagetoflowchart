<?php

define('GEMINI_API_KEY', 'AQ.Ab8RN6IxW4sfxn7I-fZMwKXxlurU1PBvfq-b8AAGMZJDePmHzA');

// -----------------------------------------------------------------------
// SMTP Configuration — set SMTP_PASS_VALUE to your Gmail App Password.
// Apache does NOT inherit /etc/environment, so we read it here directly.
// -----------------------------------------------------------------------
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'camchartssupport@gmail.com');

// Try every possible source for the password (env var set various ways,
// then fall back to the hardcoded value below if all else fails).
$_smtpPass = getenv('SMTP_PASS')                       // apache2/envvars or shell export
          ?: ($_ENV['SMTP_PASS']        ?? '')          // PHP-loaded env
          ?: ($_SERVER['SMTP_PASS']     ?? '')          // server-level var
          ?: (function_exists('apache_getenv') ? apache_getenv('SMTP_PASS') : ''); // Apache SetEnv

// ---------- HARDCODE FALLBACK (fill in if env var not being picked up) ----------
// If $_smtpPass is still empty, paste your Gmail App Password between the quotes:
if (empty($_smtpPass)) {
    $_smtpPass = '';   // <-- paste App Password here if env var is not working
}
// --------------------------------------------------------------------------------

define('SMTP_PASS', $_smtpPass);
unset($_smtpPass);

?>