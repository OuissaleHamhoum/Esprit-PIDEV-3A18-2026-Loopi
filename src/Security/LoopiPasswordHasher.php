<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Accepte les mots de passe en clair du dump Loopi (dev) et les hash bcrypt après rehash.
 */
final class LoopiPasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ($hashedPassword === '' || $plainPassword === '') {
            return false;
        }
        if (str_starts_with($hashedPassword, '$2y$') || str_starts_with($hashedPassword, '$argon')) {
            return password_verify($plainPassword, $hashedPassword);
        }

        return hash_equals($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return !str_starts_with($hashedPassword, '$2y$') && !str_starts_with($hashedPassword, '$argon');
    }
}
