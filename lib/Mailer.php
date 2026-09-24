<?php

declare(strict_types=1);

/**
 * Envoi d'e-mails transactionnels via SMTP (Hostinger).
 */
class Mailer
{
    public static function isConfigured(): bool
    {
        $c = self::config();

        return $c['host'] !== ''
            && $c['username'] !== ''
            && $c['password'] !== ''
            && $c['from'] !== '';
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): array
    {
        $to = strtolower(trim($to));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Adresse destinataire invalide.'];
        }
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'SMTP non configuré.'];
        }

        $c = self::config();
        $from = $c['from'];
        $fromName = $c['from_name'];
        if ($textBody === '') {
            $textBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $boundary = 'b_' . bin2hex(random_bytes(12));
        $encodedSubject = self::encodeHeader($subject);
        $encodedFromName = self::encodeHeader($fromName);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $encodedFromName . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Reply-To: <' . ($c['reply_to'] !== '' ? $c['reply_to'] : $from) . '>',
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@photocom.ma>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: PHOTOCOM',
        ];

        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($textBody))
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($htmlBody))
            . '--' . $boundary . "--\r\n";

        $message = 'Subject: ' . $encodedSubject . "\r\n"
            . implode("\r\n", $headers) . "\r\n\r\n"
            . $body;

        try {
            self::smtpSend($c, $from, $to, $message);
            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            error_log('PHOTOCOM mail.send_failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Échec d’envoi de l’e-mail.'];
        }
    }

    /** @return array{host:string,port:int,encryption:string,username:string,password:string,from:string,from_name:string,reply_to:string} */
    private static function config(): array
    {
        $cfg = Helpers::config();

        return [
            'host' => trim((string) ($cfg['mail_host'] ?? '')),
            'port' => (int) ($cfg['mail_port'] ?? 465),
            'encryption' => strtolower(trim((string) ($cfg['mail_encryption'] ?? 'ssl'))),
            'username' => trim((string) ($cfg['mail_username'] ?? '')),
            'password' => (string) ($cfg['mail_password'] ?? ''),
            'from' => trim((string) ($cfg['mail_from'] ?? '')),
            'from_name' => trim((string) ($cfg['mail_from_name'] ?? 'PHOTOCOM')),
            'reply_to' => trim((string) ($cfg['mail_reply_to'] ?? '')),
        ];
    }

    private static function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value) !== 1) {
            return $value;
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /** @param array{host:string,port:int,encryption:string,username:string,password:string} $c */
    private static function smtpSend(array $c, string $from, string $to, string $data): void
    {
        $host = $c['host'];
        $port = $c['port'] > 0 ? $c['port'] : 465;
        $encryption = $c['encryption'];
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT);
        if ($fp === false) {
            throw new RuntimeException('Connexion SMTP impossible (' . $errno . ' ' . $errstr . ').');
        }
        stream_set_timeout($fp, 25);

        self::expect($fp, [220]);
        self::command($fp, 'EHLO photocom.ma', [250]);

        if ($encryption === 'tls') {
            self::command($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                throw new RuntimeException('STARTTLS échoué.');
            }
            self::command($fp, 'EHLO photocom.ma', [250]);
        }

        self::command($fp, 'AUTH LOGIN', [334]);
        self::command($fp, base64_encode($c['username']), [334]);
        self::command($fp, base64_encode($c['password']), [235]);
        self::command($fp, 'MAIL FROM:<' . $from . '>', [250]);
        self::command($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        self::command($fp, 'DATA', [354]);

        $payload = preg_replace('/^\./m', '..', $data) ?? $data;
        fwrite($fp, $payload . "\r\n.\r\n");
        self::expect($fp, [250]);
        self::command($fp, 'QUIT', [221, 250]);
        fclose($fp);
    }

    /** @param resource $fp */
    private static function command($fp, string $line, array $okCodes): void
    {
        fwrite($fp, $line . "\r\n");
        self::expect($fp, $okCodes);
    }

    /** @param resource $fp @param list<int> $okCodes */
    private static function expect($fp, array $okCodes): void
    {
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new RuntimeException('SMTP ' . $code . ': ' . trim($response));
        }
    }
}
