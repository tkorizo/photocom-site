<?php

declare(strict_types=1);

/**
 * Codes OTP à usage unique envoyés par e-mail.
 */
class OtpService
{
    public const PURPOSE_ADMIN_LOGIN = 'admin_login';
    public const PURPOSE_CUSTOMER_REGISTER = 'customer_register';

    private const TTL_SECONDS = 600;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN = 60;

    /**
     * @return array{ok:bool,message:string,code?:string}
     */
    public static function issue(string $purpose, string $email, ?int $subjectId, string $mailSubject, string $mailIntro): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Adresse e-mail invalide.'];
        }
        if (!Mailer::isConfigured()) {
            return ['ok' => false, 'message' => 'L’envoi d’e-mails n’est pas disponible. Contactez PHOTOCOM.'];
        }
        if (!RateLimiter::allow('otp_' . $purpose, 8, 3600)) {
            return ['ok' => false, 'message' => 'Trop de codes demandés. Réessayez plus tard.'];
        }

        self::purgeExpired();
        self::invalidate($purpose, $email);

        $code = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);

        $stmt = Database::getInstance()->prepare(
            'INSERT INTO email_otps (purpose, email, subject_id, code_hash, expires_at)
             VALUES (:purpose, :email, :subject_id, :code_hash, :expires_at)'
        );
        $stmt->execute([
            'purpose' => $purpose,
            'email' => $email,
            'subject_id' => $subjectId,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => $expiresAt,
        ]);

        $html = '<!DOCTYPE html><html lang="fr"><body style="font-family:Arial,sans-serif;line-height:1.5;color:#111">'
            . '<p>' . Helpers::e($mailIntro) . '</p>'
            . '<p style="font-size:28px;letter-spacing:6px;font-weight:bold">' . Helpers::e($code) . '</p>'
            . '<p style="font-size:13px;color:#555">Code valable 10 minutes. Ne le partagez avec personne.</p>'
            . '<p>— L’équipe PHOTOCOM</p></body></html>';
        $text = $mailIntro . "\n\nCode : {$code}\n\nValable 10 minutes.\n\n— PHOTOCOM";

        $sent = Mailer::send($email, $mailSubject, $html, $text);
        if (!$sent['ok']) {
            return ['ok' => false, 'message' => 'Impossible d’envoyer le code. Réessayez dans un instant.'];
        }

        return ['ok' => true, 'message' => 'Un code à 6 chiffres a été envoyé à votre e-mail.'];
    }

    /**
     * @return array{ok:bool,message:string,subject_id:?int}
     */
    public static function verify(string $purpose, string $email, string $code): array
    {
        $email = strtolower(trim($email));
        $code = preg_replace('/\s+/', '', trim($code)) ?? '';

        if (!preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'message' => 'Code invalide.', 'subject_id' => null];
        }

        self::purgeExpired();
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM email_otps
             WHERE purpose = :purpose AND email = :email AND consumed_at IS NULL
               AND expires_at > datetime(\'now\')
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['purpose' => $purpose, 'email' => $email]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['ok' => false, 'message' => 'Code expiré ou introuvable. Demandez un nouveau code.', 'subject_id' => null];
        }

        $attempts = (int) ($row['attempts'] ?? 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            self::consume((int) $row['id']);
            return ['ok' => false, 'message' => 'Trop de tentatives. Demandez un nouveau code.', 'subject_id' => null];
        }

        if (!password_verify($code, (string) $row['code_hash'])) {
            $upd = Database::getInstance()->prepare('UPDATE email_otps SET attempts = attempts + 1 WHERE id = :id');
            $upd->execute(['id' => (int) $row['id']]);
            return ['ok' => false, 'message' => 'Code incorrect.', 'subject_id' => null];
        }

        self::consume((int) $row['id']);
        self::invalidate($purpose, $email);

        return [
            'ok' => true,
            'message' => 'Vérification réussie.',
            'subject_id' => isset($row['subject_id']) ? (int) $row['subject_id'] : null,
        ];
    }

    public static function canResend(string $purpose, string $email): bool
    {
        $email = strtolower(trim($email));
        $stmt = Database::getInstance()->prepare(
            'SELECT created_at FROM email_otps
             WHERE purpose = :purpose AND email = :email
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['purpose' => $purpose, 'email' => $email]);
        $created = $stmt->fetchColumn();
        if ($created === false) {
            return true;
        }

        return (time() - strtotime((string) $created)) >= self::RESEND_COOLDOWN;
    }

    private static function consume(int $id): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE email_otps SET consumed_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    private static function invalidate(string $purpose, string $email): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE email_otps SET consumed_at = datetime(\'now\')
             WHERE purpose = :purpose AND email = :email AND consumed_at IS NULL'
        );
        $stmt->execute(['purpose' => $purpose, 'email' => strtolower(trim($email))]);
    }

    private static function purgeExpired(): void
    {
        Database::getInstance()->exec(
            'DELETE FROM email_otps
             WHERE expires_at < datetime(\'now\', \'-1 day\')
                OR (consumed_at IS NOT NULL AND consumed_at < datetime(\'now\', \'-7 days\'))'
        );
    }
}
