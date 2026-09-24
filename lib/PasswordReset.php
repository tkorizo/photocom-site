<?php

declare(strict_types=1);

/**
 * Récupération de mot de passe client (lien à usage unique par e-mail).
 */
class PasswordReset
{
    private const TTL_SECONDS = 3600;
    private const REQUEST_MAX_PER_IP = 5;
    private const REQUEST_WINDOW = 3600;

    /**
     * Demande un reset. Ne révèle jamais si l'e-mail existe.
     * @return array{ok:bool,message:string}
     */
    public static function request(string $email): array
    {
        $email = strtolower(trim($email));
        $generic = 'Si un compte existe pour cette adresse, un e-mail de réinitialisation vient d’être envoyé. Vérifiez aussi vos indésirables.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Adresse e-mail invalide.'];
        }
        if (!Mailer::isConfigured()) {
            return ['ok' => false, 'message' => 'L’envoi d’e-mails n’est pas disponible pour le moment. Contactez PHOTOCOM.'];
        }
        if (!RateLimiter::allow('password_reset', self::REQUEST_MAX_PER_IP, self::REQUEST_WINDOW)) {
            return ['ok' => false, 'message' => 'Trop de demandes. Réessayez dans une heure.'];
        }

        $customer = CustomerRepository::findByEmail($email);
        if (!$customer || (int) ($customer['is_active'] ?? 0) !== 1) {
            return ['ok' => true, 'message' => $generic];
        }

        self::purgeExpired();
        self::invalidateForCustomer((int) $customer['id']);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);

        $stmt = Database::getInstance()->prepare(
            'INSERT INTO password_reset_tokens (customer_id, token_hash, expires_at)
             VALUES (:customer_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'customer_id' => (int) $customer['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $resetUrl = Helpers::absoluteUrl('/compte/mot-de-passe?token=' . rawurlencode($token));
        $name = trim((string) ($customer['name'] ?? '')) ?: 'Client';
        $subject = 'Réinitialisation de votre mot de passe PHOTOCOM';
        $html = self::emailHtml($name, $resetUrl);
        $text = "Bonjour {$name},\n\n"
            . "Une demande de réinitialisation de mot de passe a été faite pour votre compte PHOTOCOM.\n\n"
            . "Ouvrez ce lien (valable 1 heure) :\n{$resetUrl}\n\n"
            . "Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail.\n\n"
            . "— L’équipe PHOTOCOM";

        $sent = Mailer::send($email, $subject, $html, $text);
        if (!$sent['ok']) {
            return ['ok' => false, 'message' => 'Impossible d’envoyer l’e-mail pour le moment. Réessayez plus tard ou contactez-nous.'];
        }

        return ['ok' => true, 'message' => $generic];
    }

    public static function findValidCustomerId(string $token): ?int
    {
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        self::purgeExpired();
        $stmt = Database::getInstance()->prepare(
            'SELECT customer_id FROM password_reset_tokens
             WHERE token_hash = :hash
               AND used_at IS NULL
               AND expires_at > datetime(\'now\')
             LIMIT 1'
        );
        $stmt->execute(['hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $customer = CustomerRepository::find((int) $row['customer_id']);
        if (!$customer || (int) ($customer['is_active'] ?? 0) !== 1) {
            return null;
        }

        return (int) $customer['id'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function reset(string $token, string $password, string $passwordConfirm): array
    {
        if ($password !== $passwordConfirm) {
            return ['ok' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }

        $customerId = self::findValidCustomerId($token);
        if ($customerId === null) {
            return ['ok' => false, 'message' => 'Ce lien est invalide ou a expiré. Demandez une nouvelle réinitialisation.'];
        }

        CustomerRepository::updatePassword($customerId, $password);

        $stmt = Database::getInstance()->prepare(
            'UPDATE password_reset_tokens
             SET used_at = datetime(\'now\')
             WHERE token_hash = :hash AND used_at IS NULL'
        );
        $stmt->execute(['hash' => hash('sha256', trim($token))]);
        self::invalidateForCustomer($customerId);

        return ['ok' => true, 'message' => 'Mot de passe mis à jour. Vous pouvez vous connecter.'];
    }

    private static function invalidateForCustomer(int $customerId): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE password_reset_tokens
             SET used_at = datetime(\'now\')
             WHERE customer_id = :id AND used_at IS NULL'
        );
        $stmt->execute(['id' => $customerId]);
    }

    private static function purgeExpired(): void
    {
        Database::getInstance()->exec(
            'DELETE FROM password_reset_tokens
             WHERE expires_at < datetime(\'now\')
                OR (used_at IS NOT NULL AND used_at < datetime(\'now\', \'-7 days\'))'
        );
    }

    private static function emailHtml(string $name, string $resetUrl): string
    {
        $safeName = Helpers::e($name);
        $safeUrl = Helpers::e($resetUrl);

        return '<!DOCTYPE html><html lang="fr"><body style="font-family:Arial,sans-serif;line-height:1.5;color:#111">'
            . '<p>Bonjour ' . $safeName . ',</p>'
            . '<p>Une demande de réinitialisation de mot de passe a été faite pour votre compte <strong>PHOTOCOM</strong>.</p>'
            . '<p><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 18px;background:#111;color:#fff;text-decoration:none;border-radius:4px">Choisir un nouveau mot de passe</a></p>'
            . '<p style="font-size:13px;color:#555">Lien valable 1 heure :<br><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>'
            . '<p style="font-size:13px;color:#555">Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail.</p>'
            . '<p>— L’équipe PHOTOCOM</p>'
            . '</body></html>';
    }
}
