<?php

namespace Sribna\Licensee;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Class LicenseException
 * @package Sribna\Licensee
 */
class LicenseException extends RuntimeException
{
    //
}

/**
 * Class Checker
 * This file goes into client app, so it must me encoded with IonCube
 * @todo Encode
 */
final class Checker
{

    /**
     * The percentage of PHP execution time to calculate request timeout
     */
    const TIMEOUT = 50;

    /**
     * The exception code for private key validation errors
     */
    const EXCEPTION_PRIVATE_KEY = 2;

    /**
     * The exception code used to determine if the program must shutdown due to expired key
     */
    const EXCEPTION_SHUTDOWN = 3;

    /**
     * Very secret phrase used for salting private keys
     * @todo SET LICENSOR'S SECRET CODE HERE
     * @var string
     */
    private static $secret;

    /**
     * @var string The public key
     */
    private $key;

    /**
     * @var string The private (local) key
     */
    private $privateKey;

    /**
     * @var array An array of verification domains
     */
    private $verificationDomains = [];

    /**
     * @var array An array of activation domains
     */
    private $activationDomains = [];

    /**
     * Remote URL path for key verification
     * @var string
     */
    private $verificationUrlPath = 'key/check';

    /**
     * Remote URL path for key activation
     * @var string
     */
    private $activationUrlPath = 'key/activate';

    /**
     * Client callback URL path for receiving private keys
     * @var string
     */
    private $callbackUrlPath = 'key/callback';

    /**
     * @var string The message that will be displayed when the license has expired and the program does not work
     */
    private $shutdownMessage = 'Licence expired';

    /**
     * @var string The message that will be displayed when the private key failed verification and the program does not work
     */
    private $shutdownPrivateKeyMessage = 'License validation failed';

    /**
     * Validates the private key
     * @param string|null $privateKey
     * @return bool
     */
    public function validatePrivateKey(string $privateKey = null): bool
    {
        return is_array($this->getPrivateKeySettings($privateKey));
    }

    /**
     * Performs the license verification
     * @return bool
     */
    public function check(): bool
    {
        static $checked = null;

        if (isset($checked)) {
            return $checked; // Make sure its executed only once during the request
        }

        $checked = true;

        try {
            if ($this->checkExpiredPrivateKey()) {
                $this->requestVerification();
            }
        } catch (Throwable $exception) {
            $this->shutdown($exception);
        }

        return $checked;
    }

    /**
     * Shutdown the app
     * @param Throwable $exception
     */
    protected function shutdown(Throwable $exception)
    {
        if ($this->isShutdownException($exception)) {
            try {
                $this->requestVerification();
            } catch (Throwable $exception) {
                exit($this->shutdownPrivateKeyMessage);
            }
            exit($exception->getMessage());
        }

        if ($this->isPrivateKeyException($exception)) {
            exit($this->shutdownPrivateKeyMessage);
        }
    }

    /**
     * Whether the exception indicates that the application should stop working
     * @param Throwable $exception
     * @return bool
     */
    public function isShutdownException(Throwable $exception): bool
    {
        return $exception instanceof LicenseException
            && $exception->getCode() === self::EXCEPTION_SHUTDOWN;
    }

    /**
     * Whether the exception indicates that the private key is invalid
     * @param Throwable $exception
     * @return bool
     */
    public function isPrivateKeyException(Throwable $exception)
    {
        return $exception instanceof LicenseException
            && $exception->getCode() === self::EXCEPTION_PRIVATE_KEY;
    }

    /**
     * Send key verification request
     * @return ResponseInterface
     * @throws Throwable
     */
    public function requestVerification()
    {
        return $this->requests($this->getVerificationDomains(), 'verification');
    }

    /**
     * Send key activation request
     * @return ResponseInterface
     * @throws Throwable
     */
    public function requestActivation()
    {
        return $this->requests($this->getActivationDomains(), 'activation');
    }

    /**
     * Extract an array of settings from the private key
     * @param null|string $privateKey
     * @return array
     */
    public function getPrivateKeySettings(string $privateKey = null): array
    {
        $privateKey = $privateKey ?? $this->getPrivateKey();

        if (!$privateKey || !is_string($privateKey)) {
            throw new LicenseException('Invalid private key', self::EXCEPTION_PRIVATE_KEY);
        }

        if (!$base64Decoded = base64_decode($privateKey)) {
            throw new LicenseException('Failed to base64 decode private key', self::EXCEPTION_PRIVATE_KEY);
        }

        $parts = explode('|', $base64Decoded);

        if (count($parts) == 2) {
            $settings = json_decode($parts[1], true);
            if (is_array($settings) && hash_equals(md5($parts[1] . $this->getSecret()), $parts[0])) {
                $this->validatePrivateKeySettings($settings);
                $settings += ['shutdown_offset' => 60 * 60 * 24];
                $settings['shutdown_at'] = $settings['expires_at'] + $settings['shutdown_offset'];
                return $settings;
            }
        }

        throw new LicenseException('Failed to extract the private key settings', self::EXCEPTION_PRIVATE_KEY);
    }

    /**
     * Check if the private key has expired
     * @return false|array
     * @throws Exception
     */
    public function checkExpiredPrivateKey()
    {
        $now = time();
        $settings = $this->getPrivateKeySettings();

        if ($now > $settings['shutdown_at']) {
            throw new LicenseException($this->shutdownMessage, self::EXCEPTION_SHUTDOWN);
        }

        if ($now > $settings['expires_at']) {
            return [$settings['expires_at'], $settings['shutdown_at']];
        }

        return false;
    }

    /**
     * Returns the current domain
     * @return string
     */
    public function getCurrentHost(): string
    {
        if (!($host = $_SERVER['HTTP_HOST'] ?? '')) {
            $host = $_SERVER['SERVER_NAME'] ?? '';
        }

        if (!($host = strtolower(preg_replace('/:\d+$/', '', trim($host))))) {
            throw new LicenseException('Failed to get the current domain');
        }

        return $host;
    }

    /**
     * Set up the class properties from an array of values
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        unset($options['secret']);

        foreach ($options as $property => $value) {
            $this->{"set$property"}($value);
        }

        return $this;
    }

    /**
     * Returns a unique MD5 hash for the app
     * @return string
     */
    public function getToken(): string
    {
        return md5($this->hashRequest());
    }

    /**
     * Hash up the request body
     * @return string
     */
    private function hashRequest(): string
    {
        $domain = $this->getCurrentHost();

        if (!($key = $this->getKey())) {
            throw new LicenseException("Public key is not set");
        }

        $data = "$key|$domain";
        return base64_encode(md5($this->getSecret() . $data) . "|$data");
    }

    /**
     * Sends a request to an array of remote servers
     * @param array $domains
     * @param string $action
     * @return ResponseInterface
     * @throws Throwable
     */
    private function requests(array $domains, string $action)
    {
        foreach ($domains as $domain) {
            try {
                return $this->request($domain, $action);
            } catch (ConnectException $exception) {
                continue; // Assume host is down, try next one
            }
        }

        throw new LicenseException("Couldn't connect to any host while requesting $action");
    }

    /**
     * Send a single request to a remote server
     * @param string $domain
     * @param string $type
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function request(string $domain, string $type)
    {
        $options = [
            'body' => $this->hashRequest(),
            'timeout' => (float)round((self::TIMEOUT / 100) * ini_get('max_execution_time')),
            'headers' => [
                'Licensee-Callback' => $this->getCallbackUrlPath()
            ]
        ];

        return (new Client())->post($this->getRequestUrl($domain, $type), $options);
    }

    /**
     * Returns a request URL
     * @param string $domain
     * @param string $type
     * @return string
     */
    private function getRequestUrl(string $domain, string $type): string
    {
        return "http://$domain/" . call_user_func([$this, "get{$type}UrlPath"]);
    }

    /**
     * Validate the private key settings
     * @param array $settings
     */
    private function validatePrivateKeySettings(array $settings)
    {
        if (empty($settings['domain']) || $settings['domain'] !== $this->getCurrentHost()) {
            throw new LicenseException("Invalid domain in the private key settings", self::EXCEPTION_PRIVATE_KEY);
        }

        if (empty($settings['key']) || $settings['key'] !== $this->getKey()) {
            throw new LicenseException("Invalid key in the private key settings", self::EXCEPTION_PRIVATE_KEY);
        }

        if (empty($settings['expires_at'] || !is_int($settings['expires_at']))) {
            throw new LicenseException("Invalid expiration timestamp in the private key settings", self::EXCEPTION_PRIVATE_KEY);
        }
    }

    /**
     * Returns the public key
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the public key
     * @param string $key
     * @return $this
     */
    public function setKey(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Returns the private key
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * Set the private key
     * @param string $key
     * @return $this
     */
    public function setPrivateKey(string $key)
    {
        $this->privateKey = $key;
        return $this;
    }

    /**
     * Returns an array of verification domains
     * @return array
     */
    public function getVerificationDomains(): array
    {
        return $this->verificationDomains;
    }

    /**
     * Sets the verification domains
     * @param array $domains
     * @return $this
     */
    public function setVerificationDomains(array $domains)
    {
        $this->verificationDomains = $domains;
        return $this;
    }

    /**
     * Returns an array of activation domains
     * @return array
     */
    public function getActivationDomains(): array
    {
        return $this->activationDomains;
    }

    /**
     * Sets the activation domains
     * @param array $domains
     * @return $this
     */
    public function setActivationDomains(array $domains)
    {
        $this->activationDomains = $domains;
        return $this;
    }

    /**
     * Returns the activation URL path
     * @return string
     */
    public function getActivationUrlPath(): string
    {
        return $this->activationUrlPath;
    }

    /**
     * Sets the activation URL path
     * @param string $path
     * @return $this
     */
    public function setActivationUrlPath(string $path)
    {
        $this->activationUrlPath = $path;
        return $this;
    }

    /**
     * Returns the verification URL path
     * @return string
     */
    public function getVerificationUrlPath(): string
    {
        return $this->verificationUrlPath;
    }

    /**
     * Sets the verification URL path
     * @param string $path
     * @return $this
     */
    public function setVerificationUrlPath(string $path)
    {
        $this->verificationUrlPath = $path;
        return $this;
    }

    /**
     * Returns the callback URL path
     * @return string
     */
    public function getCallbackUrlPath(): string
    {
        return $this->callbackUrlPath;
    }

    /**
     * Sets the callback URL path
     * @param string $path
     * @return $this
     */
    public function setCallbackUrlPath(string $path)
    {
        $this->callbackUrlPath = $path;
        return $this;
    }

    /**
     * Returns the app shutdown message
     * @return string
     */
    public function getShutdownMessage(): string
    {
        return $this->shutdownMessage;
    }

    /**
     * Sets the app shutdown message
     * @param string $message
     * @return $this
     */
    public function setShutdownMessage(string $message)
    {
        $this->shutdownMessage = $message;
        return $this;
    }

    /**
     * Returns the private key validation shutdown message
     * @return string
     */
    public function getShutdownPrivateKeyMessage(): string
    {
        return $this->shutdownPrivateKeyMessage;
    }

    /**
     * Sets the private key validation shutdown message
     * @param string $message
     * @return $this
     */
    public function setShutdownPrivateKeyMessage(string $message)
    {
        $this->shutdownPrivateKeyMessage = $message;
        return $this;
    }

    /**
     * Returns the secret phrase
     * @return string
     */
    private function getSecret(): string
    {
        if (self::$secret) {
            return self::$secret;
        }

        throw new LicenseException('Secret is not set');
    }

    /**
     * Writes the secret phrase to the file
     * @param string $secret
     * @throws Exception
     */
    public static function writeSecret(string $secret)
    {
        $propertyMask = 'private_static_$secret;';
        $property = str_replace('_', ' ', $propertyMask);

        $content = file_get_contents(__FILE__);

        if (strpos($content, $property) === false) {
            throw new RuntimeException("Secret is already set");
        }

        $content = str_replace("@todo SET LICENSOR'S SECRET CODE HERE", '', $content);

        file_put_contents(__FILE__, str_replace(
                $property,
                'private static $secret = "' . $secret . '";',
                $content)
        );
    }

}
