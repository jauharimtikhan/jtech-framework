<?php

namespace Jtech\Security;

class Encrypter
{
    protected string $key = 'base64:CHANGE_ME_32_BYTES';

    protected function key(): string
    {
        return base64_decode(str_replace('base64:', '', $this->key));
    }

    public function encrypt(string $value): string
    {
        $iv = random_bytes(16);

        $cipher = openssl_encrypt(
            $value,
            'AES-256-CBC',
            $this->key(),
            0,
            $iv
        );

        return base64_encode($iv . $cipher);
    }

    public function decrypt(string $payload): string
    {
        $data = base64_decode($payload);
        $iv   = substr($data, 0, 16);
        $val  = substr($data, 16);

        return openssl_decrypt(
            $val,
            'AES-256-CBC',
            $this->key(),
            0,
            $iv
        );
    }
}
