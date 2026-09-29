<?php

// -----------------------------------------------------------------------
// Resend API Key for sending password reset emails.
// DigitalOcean blocks SMTP ports, so we use Resend's HTTP API instead.
// -----------------------------------------------------------------------

define('RESEND_API_KEY', getenv('RESEND_API_KEY'));

// The FROM address must match a verified domain in your Resend account.
// While your domain (vishthefishjr.me) is being verified in Resend, use:
//   'onboarding@resend.dev'
// Once vishthefishjr.me is verified, use:
//   'noreply@vishthefishjr.me'

define('MAIL_FROM_ADDRESS', 'noreply@vishthefishjr.me');
define('MAIL_FROM_NAME', 'CamCharts Support');

?>