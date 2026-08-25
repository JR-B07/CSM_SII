<?php

namespace App\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Encripta/desencripta de forma transparente los atributos listados en
 * la propiedad $encryptable del modelo, usando el cifrado AES-256-CBC
 * configurado en config/app.php (APP_CIPHER).
 */
trait Encryptable
{
    public function setAttribute($key, $value)
    {
        if ($value !== null && in_array($key, $this->getEncryptableAttributes(), true)) {
            $value = Crypt::encryptString((string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($value !== null && in_array($key, $this->getEncryptableAttributes(), true)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                // Valor no encriptado (p. ej. datos legacy): se retorna tal cual.
                return $value;
            }
        }

        return $value;
    }

    public function getEncryptableAttributes(): array
    {
        return $this->encryptable ?? [];
    }

    /**
     * Eloquent's attributesToArray() reads raw attributes directly for keys
     * without an explicit accessor method, bypassing getAttribute(). Without
     * this override, toArray()/toJson() (and therefore JSON API responses)
     * would leak the encrypted ciphertext instead of the decrypted value.
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getEncryptableAttributes() as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = $this->getAttribute($key);
            }
        }

        return $attributes;
    }
}
