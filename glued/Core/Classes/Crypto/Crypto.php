<?php
namespace Glued\Core\Classes\Crypto;
use Respect\Validation\Validator as v;

class Crypto
{

    public function __construct() {
        $this->base64_variant = SODIUM_BASE64_VARIANT_URLSAFE;
    }


    public function genkey_base64() {
        $key = sodium_bin2base64(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES), $this->base64_variant); // 256 bit
        return $key;
    }


    public function encrypt($msg, $key) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); // 24 bytes
        $ciphertext = sodium_crypto_secretbox($msg, $nonce, sodium_base642bin($key, $this->base64_variant));
        $encoded = sodium_bin2base64($nonce . $ciphertext, $this->base64_variant);
        sodium_memzero($msg);
        sodium_memzero($key);
        return $encoded;
    }


    public function decrypt($encoded, $key) {
        $decoded = sodium_base642bin($encoded, $this->base64_variant);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, sodium_base642bin($key, $this->base64_variant));
        sodium_memzero($ciphertext);
        sodium_memzero($key);
        return $plaintext;
    }
}