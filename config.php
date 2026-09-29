<?php

// -----------------------------------------------------------------------
// Resend API Key for sending password reset emails.
// DigitalOcean blocks SMTP ports, so we use Resend's HTTP API instead.
// Sign up free at https://resend.com -> API Keys -> Create API Key
// -----------------------------------------------------------------------
define('RESEND_API_KEY', 're_QLNJRJXR_E1YhzyCg5g5M1tCGHcZ8E1uV');   // <-- paste your Resend API key here

// The FROM address must match a verified domain in your Resend account.
// While your domain (vishthefishjr.me) is being verified, use:
//   'onboarding@resend.dev'  (works immediately for testing)
// Once vishthefishjr.me is verified in Resend, change to:
//   'noreply@vishthefishjr.me'
define('MAIL_FROM_ADDRESS', 'noreply@vishthefishjr.me');
define('MAIL_FROM_NAME', 'CamCharts Support');

?>