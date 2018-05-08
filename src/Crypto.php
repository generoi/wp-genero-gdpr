<?php

namespace GeneroWP\GDPR;

abstract class Crypto
{
    /**
     * Encrypt a value.
     *
     * @param  string  $value
     * @return string
     */
    abstract public function encrypt(string $value);

    /**
     * Decrypt a value.
     *
     * @param  string  $value
     * @param  string  $key
     * @return string
     */
    abstract public function decrypt(string $encrypted, string $key);
}
