<?php

namespace GeneroWP\GDPR;

use Composer\Composer;
use Composer\Factory;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use ParagonIE\Halite\Key;
use ParagonIE\Halite\KeyFactory;

class ComposerScript
{
    public static function generateKeyPair(Event $event)
    {
        $dir = self::getKeyDir($event->getComposer());
        $publicKeyPath = "$dir/genero-gdpr.public.key";

        if (file_exists($publicKeyPath)) {
            return;
        }

        $keyPair = KeyFactory::generateEncryptionKeyPair();
        $publicKey = $keyPair->getPublicKey();
        $secretKey = $keyPair->getSecretKey();

        if (self::saveKeyFile($publicKey, $dir . '/genero-gdpr.public.key', $event->getIO())) {
            self::outputSecret($secretKey, $event->getIO());
        }
    }

    protected static function outputSecret(Key $secretKey, IOInterface $io)
    {
        $secret = KeyFactory::export($secretKey);
        $secretLength = strlen($secret);
        $instructions  = 'Save the following secret key somewhere secure:';

        $io->write(
            '<info>' .
            "\n" . str_pad('', ($secretLength - strlen($instructions))/2, ' ') . $instructions .
            "\n" .
            "\n##" . str_pad('', $secretLength, '#') . '##' .
            "\n# " . str_pad('', $secretLength, ' ') . ' #' .
            "\n# " . KeyFactory::export($secretKey)  . ' #' .
            "\n# " . str_pad('', $secretLength, ' ') . ' #' .
            "\n##" . str_pad('', $secretLength, '#') . '##' .
            '</info>'
        );

        $io->write("\n" . '<info>Note that if you do not need the encryption feature you can remove the public key and run `composer run generate-keys` at a later stage.</info>');
    }

    protected static function getKeyDir(Composer $composer)
    {
        $dir = dirname(Factory::getComposerFile());
        $extra = $composer->getPackage()->getExtra();

        if (!empty($extra['public-key-path'])) {
            $dir = $dir . '/' . $extra['public-key-path'];
        }

        return $dir;
    }


    protected static function saveKeyFile(Key $key, string $filePath, IOInterface $io)
    {
        if (file_exists($filePath)) {
            $io->write("Key already exists in $filePath");
            return false;
        }

        $dir = dirname($filePath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            $io->write("<info>Created directory with chmod 0755: $dir</info>");
        }

        if (KeyFactory::save($key, $filePath)) {
            chmod($filePath, 0444);
            $io->write("<info>Save key in $filePath with chmod 0444</info>");
            return true;
        } else {
            $io->writeError("<warning>Failed saving key to $filePath<warning>");
        }

        return false;
    }
}
