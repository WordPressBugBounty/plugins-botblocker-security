<?php

namespace BotBlocker\Vendor\PragmaRX\Google2FA\Exceptions;

use BotBlocker\Vendor\PragmaRX\Google2FA\Exceptions\Contracts\Google2FA as Google2FAExceptionContract;
use BotBlocker\Vendor\PragmaRX\Google2FA\Exceptions\Contracts\IncompatibleWithGoogleAuthenticator as IncompatibleWithGoogleAuthenticatorExceptionContract;
class IncompatibleWithGoogleAuthenticatorException extends Google2FAException implements Google2FAExceptionContract, IncompatibleWithGoogleAuthenticatorExceptionContract
{
    protected $message = 'This secret key is not compatible with Google Authenticator.';
}
