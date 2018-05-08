<?php

namespace GeneroWP\GDPR\Crypto;

use GeneroWP\GDPR\Crypto as Base;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Asymmetric\Crypto as AsymmetricCrypto;

class Asymmetric extends Base
{
    protected $publicKey;

    public function __construct()
    {
        $this->publicKey = KeyFactory::loadEncryptionPublicKey(GENERO_GDPR_PUBLIC_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt(string $value)
    {
        return AsymmetricCrypto::seal(new HiddenString($value), $this->publicKey);
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt(string $encrypted, string $secret)
    {
        $secretKey = KeyFactory::importEncryptionSecretKey(new HiddenString($secret));
        return AsymmetricCrypto::unseal(new HiddenString($encrypted), $secretKey);
    }
}
