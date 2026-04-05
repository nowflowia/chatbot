<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class CompanySetting extends Model
{
    protected static string $table = 'company_settings';

    public static function get(): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM company_settings ORDER BY id ASC LIMIT 1"
        );
    }

    public static function save(array $data): array
    {
        $db       = Database::getInstance();
        $existing = static::get();
        $ts       = now();

        $companyName  = $data['company_name']  ?? '';
        $cnpj         = $data['cnpj']          ?? '';
        $email        = $data['email']         ?? '';
        $phone        = $data['phone']         ?? '';
        $address      = $data['address']       ?? '';
        $zip          = $data['zip']           ?? '';
        $city         = $data['city']          ?? '';
        $state        = $data['state']         ?? '';
        $logoPath     = $data['logo_path']     ?? ($existing['logo_path']  ?? '');
        $iconPath     = $data['icon_path']     ?? ($existing['icon_path']  ?? '');

        if ($existing) {
            $db->update(
                "UPDATE company_settings
                 SET company_name=?, cnpj=?, email=?, phone=?, address=?, zip=?, city=?, state=?,
                     logo_path=?, icon_path=?, updated_at=?
                 WHERE id=?",
                [$companyName, $cnpj, $email, $phone, $address, $zip, $city, $state,
                 $logoPath, $iconPath, $ts, $existing['id']]
            );
            return array_merge($existing, compact(
                'companyName','cnpj','email','phone','address','zip','city','state','logoPath','iconPath'
            ));
        }

        $id = $db->insert(
            "INSERT INTO company_settings
             (company_name, cnpj, email, phone, address, zip, city, state, logo_path, icon_path, custom_css, primary_color, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '#3b82f6', ?)",
            [$companyName, $cnpj, $email, $phone, $address, $zip, $city, $state, $logoPath, $iconPath, $ts]
        );

        return [
            'id'           => (int)$id,
            'company_name' => $companyName,
            'cnpj'         => $cnpj,
            'email'        => $email,
            'phone'        => $phone,
            'address'      => $address,
            'zip'          => $zip,
            'city'         => $city,
            'state'        => $state,
            'logo_path'    => $logoPath,
            'icon_path'    => $iconPath,
            'custom_css'   => '',
            'primary_color'=> '#3b82f6',
        ];
    }

    public static function saveTemplate(array $data): void
    {
        $db       = Database::getInstance();
        $existing = static::get();
        $ts       = now();

        $customCss    = $data['custom_css']    ?? '';
        $primaryColor = $data['primary_color'] ?? '#3b82f6';

        if ($existing) {
            $db->update(
                "UPDATE company_settings SET custom_css=?, primary_color=?, updated_at=? WHERE id=?",
                [$customCss, $primaryColor, $ts, $existing['id']]
            );
        } else {
            $db->insert(
                "INSERT INTO company_settings (custom_css, primary_color, updated_at) VALUES (?, ?, ?)",
                [$customCss, $primaryColor, $ts]
            );
        }
    }
}
