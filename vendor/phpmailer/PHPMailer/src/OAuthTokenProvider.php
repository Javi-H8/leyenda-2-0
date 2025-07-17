<?php
/**
 * OAuthTokenProvider - OAuth2 token provider interface.
 * Provides base64 encoded OAuth2 auth strings for SMTP authentication.
 *
 * This file is part of PHPMailer.
 * @link https://github.com/PHPMailer/PHPMailer/
 */

namespace PHPMailer\PHPMailer;

/**
 * Interface OAuthTokenProvider
 */
interface OAuthTokenProvider
{
    /**
     * Generate a base64-encoded OAuth token string for SMTP authentication.
     * Ensures that the access token has not expired.
     *
     * The string should be:
     *   "user=<user_email_address>\x01auth=Bearer <access_token>\x01\x01"
     *
     * @return string
     */
    public function getOauth64(): string;
}
