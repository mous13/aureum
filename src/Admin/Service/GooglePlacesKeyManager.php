<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Service;

use Citadel\Aureum\Core\Exception\GooglePlacesException;
use DateTimeImmutable;
use Forumify\Core\Repository\SettingRepository;
use SodiumException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GooglePlacesKeyManager
{
    private const KEY_SETTING = 'aureum.google_places.api_key';
    private const SET_AT_SETTING = 'aureum.google_places.api_key_set_at';
    private const DERIVATION_CONTEXT = 'aureum.google_places.api_key.v1';

    public function __construct(
        private readonly SettingRepository $settingRepository,
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
    ) {
    }

    public function setKey(string $plaintext): void
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->deriveKey());

        $this->settingRepository->set(self::KEY_SETTING, base64_encode($nonce . $ciphertext));
        $this->settingRepository->set(self::SET_AT_SETTING, (new DateTimeImmutable())->format(DATE_ATOM));
    }

    public function hasKey(): bool
    {
        return !empty($this->settingRepository->get(self::KEY_SETTING));
    }

    public function getKeySetAt(): ?DateTimeImmutable
    {
        if (!$this->hasKey()) {
            return null;
        }

        $setAt = $this->settingRepository->get(self::SET_AT_SETTING);

        return is_string($setAt) && $setAt !== ''
            ? new DateTimeImmutable($setAt)
            : null;
    }

    public function removeKey(): void
    {
        $this->settingRepository->unset(self::KEY_SETTING);
        $this->settingRepository->unset(self::SET_AT_SETTING);
    }

    public function getKey(): string
    {
        $stored = $this->settingRepository->get(self::KEY_SETTING);
        if (!is_string($stored) || $stored === '') {
            throw new GooglePlacesException('No Google Places API key is configured.');
        }

        $decoded = base64_decode($stored, true);
        if ($decoded === false || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new GooglePlacesException('The stored Google Places API key is corrupt.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->deriveKey());
        } catch (SodiumException) {
            throw new GooglePlacesException('The stored Google Places API key could not be decrypted.');
        }

        if ($plaintext === false) {
            throw new GooglePlacesException('The stored Google Places API key could not be decrypted.');
        }

        return $plaintext;
    }

    private function deriveKey(): string
    {
        return sodium_crypto_generichash(
            self::DERIVATION_CONTEXT . $this->appSecret,
            '',
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
        );
    }
}
