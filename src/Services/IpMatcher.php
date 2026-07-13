<?php

namespace EloquentWorks\Exile\Services;

use EloquentWorks\Exile\Support\IdentifierHasher;
use InvalidArgumentException;

/**
 * Class IpMatcher
 *
 * This class provides functionality to check if an IP address is contained within a given CIDR range.
 */
final class IpMatcher
{
    /**
     * Constructor for the IpMatcher class.
     *
     * @param  IdentifierHasher  $hasher  An instance of IdentifierHasher for IP normalization.
     */
    public function __construct(private readonly IdentifierHasher $hasher) {}

    /**
     * Checks if the given IP address is contained within the specified CIDR range.
     *
     * @param  string  $cidr  The CIDR range to check against.
     * @param  string  $ipAddress  The IP address to check.
     * @return bool True if the IP address is contained within the CIDR range, false otherwise.
     */
    public function contains(string $cidr, string $ipAddress): bool
    {
        // Parse the CIDR range into network and prefix components
        [$network, $prefix] = $this->parse($cidr);
        $ipAddress = $this->hasher->normalizeIp($ipAddress);

        // Convert the network and IP address to binary format for comparison
        $networkBinary = inet_pton($network);
        $ipBinary = inet_pton($ipAddress);

        // Validate the binary representations and ensure they are of the same length
        if ($networkBinary === false || $ipBinary === false || strlen($networkBinary) !== strlen($ipBinary)) {
            return false;
        }

        // Calculate the maximum number of bits based on the binary representation length
        $maxBits = strlen($networkBinary) * 8;

        // Validate the prefix to ensure it is within the valid range
        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        // Calculate the number of whole bytes and remaining bits based on the prefix
        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        // Compare the whole bytes of the network and IP address binary representations
        if ($wholeBytes > 0 && substr($networkBinary, 0, $wholeBytes) !== substr($ipBinary, 0, $wholeBytes)) {
            return false;
        }

        // Compare the remaining bits of the network and IP address binary representations
        if ($remainingBits === 0) {
            return true;
        }

        // Create a mask to isolate the relevant bits for comparison
        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        // Compare the masked bits of the network and IP address binary representations
        return (ord($networkBinary[$wholeBytes]) & $mask) === (ord($ipBinary[$wholeBytes]) & $mask);
    }

    /**
     * Normalizes a CIDR range to a standard format.
     *
     * @param  string  $cidr  The CIDR range to normalize.
     * @return string The normalized CIDR range.
     */
    public function normalizeCidr(string $cidr): string
    {
        // Parse the CIDR range into network and prefix components
        [$network, $prefix] = $this->parse($cidr);

        // Return the normalized CIDR range in the format "network/prefix"
        return $network.'/'.$prefix;
    }

    /**
     * Parses a CIDR range into its network and prefix components.
     *
     * @param  string  $cidr  The CIDR range to parse.
     * @return array An array containing the network and prefix.
     *
     * @throws InvalidArgumentException If the CIDR range is invalid.
     */
    private function parse(string $cidr): array
    {
        // Split the CIDR range into network and prefix components
        $parts = explode('/', trim($cidr), 2);

        // Validate the CIDR range format and ensure it has exactly two parts (network and prefix)
        if (count($parts) !== 2 || $parts[1] === '' || ! ctype_digit($parts[1])) {
            throw new InvalidArgumentException('The supplied CIDR range is invalid.');
        }

        // Normalize the network IP address and convert the prefix to an integer
        $network = $this->hasher->normalizeIp($parts[0]);
        $prefix = (int) $parts[1];
        $maxBits = str_contains($network, ':') ? 128 : 32;

        // Validate the prefix to ensure it is within the valid range for the IP version
        if ($prefix < 0 || $prefix > $maxBits) {
            throw new InvalidArgumentException('The supplied CIDR prefix is invalid.');
        }

        // Return the normalized network and prefix as an array
        return [$network, $prefix];
    }
}
