<?php

namespace Slides\Connector\Auth\Sync;

use Illuminate\Encryption\Encrypter;

/**
 * Trait ImportsUsers
 *
 * @package Slides\Connector\Auth\Sync
 */
trait ImportsUsers
{
    /**
     * The import dump headers.
     *
     * @var array
     */
    protected $importHeaders;

    /**
     * Export local users to a file in the compressed GZIP format.
     *
     * @param string $path
     * @param string $sharedKey
     * @param bool $importModes Whether import modes from the dump.
     *
     * @return void
     */
    public function import(string $path, string $sharedKey, bool $importModes = true)
    {
        $difference = $this->parseDump($path, $sharedKey, $importModes);

        $foreigners = array_map(function (array $user) {
            return $this->createRemoteUserFromResponse($user);
        }, $difference);

        $this->foreigners = collect($foreigners);
    }

    /**
     * Parse a dump and retrieve user entities in array representation.
     *
     * Steps:
     * 1. Read a file contents
     * 2. Decrypt using shared token and tenant credentials
     * 3. Decompress and decode from JSON to array
     *
     * @param string $filename
     * @param string $sharedKey
     * @param bool $importModes Whether import modes from the dump.
     *
     * @return array
     */
    private function parseDump(string $filename, string $sharedKey, bool $importModes): array
    {
        if(!file_exists($filename)) {
            throw new \InvalidArgumentException($filename . ' cannot be found');
        }

        // Retrieve dump contents, it's encrypted and gzipped
        // We should handle it firstly
        $payload = file_get_contents($filename);

        // Extract and parse headers which contain necessary information
        // Then delete from a dump so we can proceed with decryption
        $this->extractHeaders($payload);

        if($importModes) {
            $this->modes = array_get($this->importHeaders, 'modes', []);
        }

        $encrypter = new Encrypter(
            $this->createDecryptionKey($sharedKey),
            $this->cipher
        );

        // Decrypt a payload. If successful, we retrieve compressed user entities
        $data = $encrypter->decrypt($payload);

        // Decompress users
        $data = gzdecode($data);

        return json_decode($data, true);
    }

    /**
     * Create a decryption key based on shared key and signature which authorizes tenant.
     *
     * @param string $sharedKey
     *
     * @return string
     */
    private function createDecryptionKey(string $sharedKey): string
    {
        return base64_decode($sharedKey) . ':' . $this->signature();
    }

    /**
     * Create a signature based on credentials.
     *
     * @return string
     */
    private function signature(): string
    {
        $sign = hash('sha256', $this->credential('public') . $this->credential('secret'));

        return substr($sign, 0, 15);
    }

    /**
     * Extract and parse headers from the dump.
     *
     * @param string $payload
     *
     * @return array
     */
    private function extractHeaders(string &$payload): array
    {
        $headers = unserialize(
            base64_decode(strtok($payload, '/'))
        );

        // Remove header string from the payload
        $payload = ltrim(strstr($payload, '/'), '/');

        return $this->importHeaders = $headers;
    }
}