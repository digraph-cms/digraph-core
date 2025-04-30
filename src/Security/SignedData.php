<?php

namespace DigraphCMS\Security;

use RuntimeException;
use Stringable;

class SignedData implements Stringable
{
    protected mixed $data;
    protected string $salt;
    protected int $expires;

    /**
     * Create a new SecureData object from the given json string. If the string
     * is invalid or the hash does not match, null is returned.
     */
    public static function fromString(string $json_string): SignedData|null
    {
        $data = json_decode($json_string, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        if (!isset($data['data'], $data['salt'], $data['expires'], $data['hash'])) {
            return null;
        }
        if (!is_string($data['data']) || !is_string($data['salt']) || !is_int($data['expires'])) {
            return null;
        }
        if (!static::checkHash($data['hash'], $data['data'], $data['salt'], $data['expires'])) {
            return null;
        }
        return new SignedData($data['data'], $data['salt'], $data['expires']);
    }

    /**
     * Get the data from a json string. If the string is invalid or the hash
     * does not match, null is returned.
     */
    public static function dataFromString(string $json_string): mixed
    {
        return static::fromString($json_string)?->data();
    }

    /**
     * Create a new SecureData object from the given data. The salt and
     * expiration time are optional, and will be generated if not provided.
     */
    public function __construct(
        mixed $data,
        string|null $salt = null,
        int|null $expires = null,
    ) {
        $this->data = $data;
        $this->salt = $salt ?? SecurityKeys::generateString();
        $this->expires = $expires ?? SecurityKeys::currentExpiration();
    }

    /**
     * get the data stored in this object
     */
    public function data(): mixed
    {
        return $this->data;
    }

    /**
     * Generate an hmac hash of the given data, salt, and expiration time using
     * the most recent current security key.
     */
    public static function makeHash(string $data_json, string $salt, int|null $expires): string
    {
        // generate the hash using the current key
        return hash_hmac(
            'sha256',
            json_encode([
                'data' => $data_json,
                'salt' => $salt,
                'expires' => $expires
            ]),
            SecurityKeys::currentKey()
        );
    }

    /**
     * Check if the given hash can be verified against the given data and salt,
     * using any active keys.
     */
    public static function checkHash(string $hash, string $data_json, string $salt, int|null $expires): bool
    {
        // short circuit if expiration is passed
        if (!is_null($expires) && time() > $expires) {
            return false;
        }
        // check if the hash matches any of the active keys, trying the most
        // recent first so we can rotate keys
        foreach (SecurityKeys::active() as $key) {
            if (hash_equals(
                hash_hmac(
                    'sha256',
                    json_encode([
                        'data' => $data_json,
                        'salt' => $salt,
                        'expires' => $expires
                    ]),
                    $key
                ),
                $hash
            )) {
                return true;
            }
        }
        // if we get here, the hash didn't match any of the active keys
        return false;
    }

    /**
     * Convert this object to a JSON string including all of the current data,
     * plus a signature hash.
     */
    public function __toString(): string
    {
        $data_json = json_encode($this->data);
        if ($data_json === false) {
            throw new RuntimeException('Failed to encode data to JSON: ' . json_last_error_msg());
        }
        return json_encode([
            'data' => $data_json,
            'salt' => $this->salt,
            'expires' => $this->expires,
            'hash' => static::makeHash($data_json, $this->salt, $this->expires)
        ]);
    }
}
