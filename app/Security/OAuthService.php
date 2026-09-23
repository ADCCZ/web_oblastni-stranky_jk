<?php

declare(strict_types=1);

namespace App\Security;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use Wohali\OAuth2\Client\Provider\Discord;
use Nette\Http\Session;
use Nette\Http\Request;

/**
 * Service for handling OAuth authentication with multiple providers
 */
final class OAuthService
{
    private const PROVIDERS = ['google', 'facebook', 'discord'];

    public function __construct(
        private array $config,
        private Session $session,
        private Request $httpRequest,
    ) {
    }

    /**
     * Get list of supported providers
     * @return string[]
     */
    public function getSupportedProviders(): array
    {
        return self::PROVIDERS;
    }

    /**
     * Check if provider is supported
     */
    public function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    /**
     * Generate authorization URL for OAuth provider
     */
    public function getAuthorizationUrl(string $provider): string
    {
        if (!$this->isProviderSupported($provider)) {
            throw new OAuthException("Nepodporovaný provider: $provider");
        }

        $client = $this->getProvider($provider);
        $url = $client->getAuthorizationUrl([
            'scope' => $this->getScopes($provider),
        ]);

        // Store state in session for CSRF protection
        $section = $this->session->getSection('oauth');
        $section->set('state', $client->getState());
        $section->set('provider', $provider);

        return $url;
    }

    /**
     * Handle OAuth callback and return normalized user data
     */
    public function handleCallback(string $provider, string $code, string $state): OAuthUser
    {
        // Verify state matches (CSRF protection)
        $section = $this->session->getSection('oauth');
        $storedState = $section->get('state');
        $storedProvider = $section->get('provider');

        if ($state !== $storedState) {
            throw new OAuthException('Neplatný state parametr. Zkuste to prosím znovu.');
        }

        if ($provider !== $storedProvider) {
            throw new OAuthException('Neshoda providera. Zkuste to prosím znovu.');
        }

        // Clear session data
        $section->remove('state');
        $section->remove('provider');

        try {
            $client = $this->getProvider($provider);
            $token = $client->getAccessToken('authorization_code', ['code' => $code]);
            $resourceOwner = $client->getResourceOwner($token);

            return $this->normalizeUser($provider, $resourceOwner);
        } catch (\Exception $e) {
            throw new OAuthException('Chyba při komunikaci s ' . ucfirst($provider) . ': ' . $e->getMessage());
        }
    }

    /**
     * Get OAuth provider client instance
     */
    private function getProvider(string $provider): AbstractProvider
    {
        $redirectUri = $this->getRedirectUri($provider);

        return match ($provider) {
            'google' => new Google([
                'clientId' => $this->config['google']['clientId'] ?? '',
                'clientSecret' => $this->config['google']['clientSecret'] ?? '',
                'redirectUri' => $redirectUri,
            ]),
            'facebook' => new Facebook([
                'clientId' => $this->config['facebook']['clientId'] ?? '',
                'clientSecret' => $this->config['facebook']['clientSecret'] ?? '',
                'redirectUri' => $redirectUri,
                'graphApiVersion' => $this->config['facebook']['graphApiVersion'] ?? 'v18.0',
            ]),
            'discord' => new Discord([
                'clientId' => $this->config['discord']['clientId'] ?? '',
                'clientSecret' => $this->config['discord']['clientSecret'] ?? '',
                'redirectUri' => $redirectUri,
            ]),
            default => throw new OAuthException("Nepodporovaný provider: $provider"),
        };
    }

    /**
     * Get OAuth scopes for provider
     * @return string[]
     */
    private function getScopes(string $provider): array
    {
        return match ($provider) {
            'google' => ['email', 'profile'],
            'facebook' => ['email', 'public_profile'],
            'discord' => ['identify', 'email'],
            default => [],
        };
    }

    /**
     * Build redirect URI for OAuth callback
     */
    private function getRedirectUri(string $provider): string
    {
        $baseUrl = $this->httpRequest->getUrl()->getBaseUrl();
        // Don't include provider in URL - Google requires exact match
        // Provider is stored in session
        return $baseUrl . 'sign/oauth-callback';
    }

    /**
     * Normalize user data from different OAuth providers
     */
    private function normalizeUser(string $provider, mixed $resourceOwner): OAuthUser
    {
        $data = $resourceOwner->toArray();

        return match ($provider) {
            'google' => new OAuthUser(
                provider: 'google',
                providerId: (string) $resourceOwner->getId(),
                email: $data['email'] ?? '',
                firstName: $data['given_name'] ?? null,
                lastName: $data['family_name'] ?? null,
            ),
            'facebook' => new OAuthUser(
                provider: 'facebook',
                providerId: (string) $resourceOwner->getId(),
                email: $data['email'] ?? '',
                firstName: $data['first_name'] ?? null,
                lastName: $data['last_name'] ?? null,
            ),
            'discord' => new OAuthUser(
                provider: 'discord',
                providerId: (string) $resourceOwner->getId(),
                email: $data['email'] ?? '',
                firstName: $data['username'] ?? null,
                lastName: null,
            ),
            default => throw new OAuthException("Nelze normalizovat uživatele pro provider: $provider"),
        };
    }
}
