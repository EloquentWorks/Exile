<?php

namespace EloquentWorks\Exile\Support;

use InvalidArgumentException;

/**
 * Class responsible for hashing identifiers such as IP addresses and device fingerprints.
 */
final class IdentifierHasher
{
    /**
     * Normalize an IP address to its canonical form.
     *
     * @param  string  $ipAddress  The IP address to normalize.
     * @return string The normalized IP address.
     *
     * @throws InvalidArgumentException If the supplied IP address is invalid or cannot be normalized.
     */
    public function normalizeIp(string $ipAddress): string
    {
        // Normalize the IP address by trimming whitespace and converting it to lowercase.
        $normalized = trim($ipAddress);

        // Validate that the normalized IP address is not empty.
        if (filter_var($normalized, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('The supplied IP address is invalid.');
        }

        // Convert the normalized IP address to its packed binary representation.
        $packed = inet_pton($normalized);

        // Validate that the packed binary representation is not false, which indicates an error in normalization.
        if ($packed === false) {
            throw new InvalidArgumentException('The supplied IP address could not be normalized.');
        }

        // Convert the packed binary representation back to its canonical string form.
        $canonical = inet_ntop($packed);

        // Validate that the canonical form is not false, which indicates an error in normalization.
        if ($canonical === false) {
            throw new InvalidArgumentException('The supplied IP address could not be normalized.');
        }

        // Return the canonical form of the IP address in lowercase to ensure consistency.
        return strtolower($canonical);
    }

    /**
     * Hash an IP address after normalizing it.
     *
     * @param  string  $ipAddress  The IP address to hash.
     * @return string The hashed representation of the normalized IP address.
     *
     * @throws InvalidArgumentException If the supplied IP address is invalid or cannot be normalized.
     */
    public function hashIp(string $ipAddress): string
    {
        // Normalize the IP address and then hash it
        return $this->hash($this->normalizeIp($ipAddress));
    }

    /**
     * Hash a device fingerprint.
     *
     * @param  string  $fingerprint  The device fingerprint to hash.
     * @return string The hashed representation of the device fingerprint.
     *
     * @throws InvalidArgumentException If the supplied fingerprint is empty.
     */
    public function hashDevice(string $fingerprint): string
    {
        // Normalize the fingerprint by trimming whitespace
        $normalized = trim($fingerprint);

        // Validate that the normalized fingerprint is not empty
        if ($normalized === '') {
            throw new InvalidArgumentException('The device fingerprint cannot be empty.');
        }

        // Hash the normalized fingerprint using HMAC with SHA-256 and a secret key from the configuration
        return $this->hash($normalized);
    }

    /**
     * Hash a value using HMAC with SHA-256 and a secret key from the configuration.
     *
     * @param  string  $value  The value to hash.
     * @return string The hashed representation of the value.
     *
     * @throws InvalidArgumentException If the hashing key is empty.
     */
    public function hash(string $value): string
    {
        // Retrieve the hashing key from the configuration
        $key = (string) config('exile.security.hash_key');

        // Validate that the hashing key is not empty
        if ($key === '') {
            throw new InvalidArgumentException('Exile requires a non-empty hashing key.');
        }

        // Use HMAC with SHA-256 to hash the value with the provided key
        return hash_hmac('sha256', $value, $key);
    }
}
