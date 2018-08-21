<?php

namespace Slides\Connector\Auth\Sync;

use Illuminate\Encryption\Encrypter;

/**
 * Trait ExportsUsers
 *
 * @package Slides\Connector\Auth\Sync
 */
trait ExportsUsers
{
    /**
     * The supported cipher algorithm.
     *
     * @var string
     */
    private $cipher = 'AES-256-CBC';

    /**
     * The randomly generated encryption/decryption key.
     *
     * @var string
     */
    private $encryptionKey;

    /**
     * Export local users to a file in the compressed GZIP format.
     *
     * @param string $path
     *
     * @return void
     */
    public function export(string $path)
    {
        $users = $this->formatLocals($this->locals);

        $data = json_encode($users);

        $data = gzencode($data);

        $data = $this->encrypt($data);

        $data = $this->makeHeaders($data);

        file_put_contents($path, $data);
    }

    /**
     * Get a generated encryption key.
     *
     * @return string|null
     */
    public function getEncryptionKey()
    {
        if(!$this->encryptionKey) {
            return null;
        }

        return base64_encode($this->encryptionKey);
    }

    /**
     * Encrypt a data and retrieve the key.
     *
     * @param string $data
     *
     * @return string
     */
    private function encrypt(string &$data): string
    {
        $encrypter = new Encrypter(
            $this->generateRandomKey(),
            $this->cipher
        );

        return $encrypter->encrypt($data);
    }

    /**
     * Generate a random key for the dump.
     *
     * @return string
     */
    private function generateRandomKey(): string
    {
        $this->encryptionKey = random_bytes(16);

        // Create a signature of remote service credentials
        $sign = hash('sha256', $this->credential('public') . $this->credential('secret'));

        return $this->encryptionKey . ':' . substr($sign, 0, 15);
    }

    /**
     * Make the headers.
     *
     * @param string $payload
     *
     * @return string
     */
    private function makeHeaders(string $payload): string
    {
        $headers = [
            'public' => $this->credential('public')
        ];

        $headers = serialize($headers);
        
        return $headers . '/' . $payload;
    }

    /**
     * The credentials.
     *
     * @return array
     */
    private function credentials(): array
    {
        return [
            'public' => config('connector.credentials.auth.public'),
            'secret' => config('connector.credentials.auth.secret')
        ];
    }

    /**
     * Retrieve a credential value
     *
     * @return string
     */
    private function credential(string $key)
    {
        return array_get($this->credentials(), $key);
    }
}