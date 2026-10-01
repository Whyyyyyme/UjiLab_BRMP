<?php

namespace App\Services;

class FileIntegrityService
{
    /**
     * Menghitung hash SHA-256 dari sebuah file untuk verifikasi integritas.
     *
     * @param string $filePath
     * @return string|null
     */
    public function generateHash(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        return hash_file('sha256', $filePath);
    }

    /**
     * Memverifikasi apakah hash file cocok dengan hash yang disimpan.
     *
     * @param string $filePath
     * @param string $storedHash
     * @return bool
     */
    public function verifyHash(string $filePath, string $storedHash): bool
    {
        $currentHash = $this->generateHash($filePath);
        return $currentHash === $storedHash;
    }
}
