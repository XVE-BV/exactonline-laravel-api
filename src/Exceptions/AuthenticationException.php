<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception for OAuth authentication errors.
 *
 * Thrown when the OAuth flow fails, including authorization errors,
 * invalid credentials, and CSRF protection failures.
 */
class AuthenticationException extends ExactOnlineException
{
    protected ?string $oauthError = null;

    protected ?string $oauthErrorDescription = null;

    /**
     * Create exception for invalid client credentials.
     */
    public static function invalidCredentials(string $connectionId): self
    {
        $exception = new self(
            "Invalid OAuth credentials for connection '{$connectionId}'. ".
            'Please verify your client_id and client_secret are correct.'
        );

        return $exception->setConnectionId($connectionId);
    }

    /**
     * Create exception for invalid redirect URI.
     */
    public static function invalidRedirectUri(string $connectionId, string $redirectUri): self
    {
        $exception = new self(
            "Invalid redirect URI for connection '{$connectionId}'. ".
            "The configured redirect URI '{$redirectUri}' does not match ".
            "the URI registered in Exact Online's app settings."
        );

        return $exception
            ->setConnectionId($connectionId)
            ->addContext('redirect_uri', $redirectUri);
    }

    /**
     * Create exception for CSRF validation failure.
     */
    public static function csrfValidationFailed(): self
    {
        return new self(
            'OAuth state validation failed. The state parameter does not match '.
            'the expected value. This may indicate a CSRF attack or expired session.'
        );
    }

    /**
     * Create exception for missing state parameter.
     */
    public static function missingState(): self
    {
        return new self(
            'OAuth state parameter is missing. The OAuth flow may have expired '.
            'or was initiated from a different browser session.'
        );
    }

    /**
     * Create exception for user denied access.
     */
    public static function accessDenied(string $connectionId): self
    {
        $exception = new self(
            "User denied access to Exact Online for connection '{$connectionId}'. ".
            'The OAuth authorization was cancelled by the user.'
        );

        return $exception
            ->setConnectionId($connectionId)
            ->setOAuthError('access_denied');
    }

    /**
     * Create exception for invalid authorization code.
     */
    public static function invalidAuthorizationCode(string $connectionId): self
    {
        $exception = new self(
            "Invalid or expired authorization code for connection '{$connectionId}'. ".
            'The code may have already been used or has expired. Please restart the OAuth flow.'
        );

        return $exception
            ->setConnectionId($connectionId)
            ->setOAuthError('invalid_grant');
    }

    /**
     * Create exception for missing authorization code.
     */
    public static function missingAuthorizationCode(): self
    {
        return new self(
            'No authorization code received from Exact Online. '.
            'The OAuth callback did not include the required code parameter.'
        );
    }

    /**
     * Create exception from OAuth error response.
     */
    public static function fromOAuthError(
        string $error,
        ?string $errorDescription = null,
        ?string $connectionId = null
    ): self {
        $errorMessages = [
            'access_denied' => 'User denied access to Exact Online',
            'invalid_request' => 'Invalid OAuth request parameters',
            'unauthorized_client' => 'Client not authorized for this grant type',
            'invalid_client' => 'Invalid client credentials',
            'invalid_grant' => 'Invalid or expired authorization/refresh token',
            'unsupported_grant_type' => 'Unsupported OAuth grant type',
            'invalid_scope' => 'Invalid OAuth scope requested',
            'server_error' => 'Exact Online server error during authentication',
            'temporarily_unavailable' => 'Exact Online is temporarily unavailable',
        ];

        $message = $errorMessages[$error] ?? $errorDescription ?? "Unknown OAuth error: {$error}";

        if ($errorDescription && isset($errorMessages[$error])) {
            $message .= ": {$errorDescription}";
        }

        $exception = new self($message);
        $exception->setOAuthError($error, $errorDescription);

        if ($connectionId) {
            $exception->setConnectionId($connectionId);
        }

        return $exception;
    }

    /**
     * Create exception for expired session.
     */
    public static function sessionExpired(): self
    {
        return new self(
            'OAuth session has expired. Please restart the authentication flow.'
        );
    }

    /**
     * Set OAuth error details.
     */
    public function setOAuthError(string $error, ?string $description = null): self
    {
        $this->oauthError = $error;
        $this->oauthErrorDescription = $description;

        return $this;
    }

    /**
     * Get the OAuth error code.
     */
    public function getOAuthError(): ?string
    {
        return $this->oauthError;
    }

    /**
     * Get the OAuth error description.
     */
    public function getOAuthErrorDescription(): ?string
    {
        return $this->oauthErrorDescription;
    }

    /**
     * Check if this is an access denied error.
     */
    public function isAccessDenied(): bool
    {
        return $this->oauthError === 'access_denied';
    }

    /**
     * Check if credentials are invalid.
     */
    public function isInvalidCredentials(): bool
    {
        return $this->oauthError === 'invalid_client';
    }

    /**
     * Get exception data for logging.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'oauth_error' => $this->oauthError,
            'oauth_error_description' => $this->oauthErrorDescription,
        ]));
    }
}
