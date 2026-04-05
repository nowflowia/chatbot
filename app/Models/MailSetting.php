<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class MailSetting extends Model
{
    protected static string $table = 'mail_settings';

    public static function get(): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM mail_settings ORDER BY id ASC LIMIT 1"
        );
    }

    public static function save(array $data): array
    {
        $db       = Database::getInstance();
        $existing = static::get();

        $host         = $data['mail_host']        ?? 'localhost';
        $port         = (int)($data['mail_port']  ?? 587);
        $encryption   = $data['mail_encryption']  ?? 'tls';
        $username     = $data['mail_username']     ?? '';
        $password     = $data['mail_password']     ?? '';
        $fromAddress  = $data['mail_from_address'] ?? '';
        $fromName     = $data['mail_from_name']    ?? 'ChatBot';
        $ts           = now();

        if ($existing) {
            $db->update(
                "UPDATE mail_settings
                 SET host=?, port=?, encryption=?, username=?, password=?,
                     from_address=?, from_name=?, is_active=1, updated_at=?
                 WHERE id=?",
                [$host, $port, $encryption, $username, $password, $fromAddress, $fromName, $ts, $existing['id']]
            );
            return array_merge($existing, compact('host','port','encryption','username','password','fromAddress','fromName'));
        }

        $id = $db->insert(
            "INSERT INTO mail_settings
             (host, port, encryption, username, password, from_address, from_name, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)",
            [$host, $port, $encryption, $username, $password, $fromAddress, $fromName, $ts, $ts]
        );

        return [
            'id'           => (int)$id,
            'host'         => $host,
            'port'         => $port,
            'encryption'   => $encryption,
            'username'     => $username,
            'password'     => $password,
            'from_address' => $fromAddress,
            'from_name'    => $fromName,
            'is_active'    => 1,
        ];
    }
}
