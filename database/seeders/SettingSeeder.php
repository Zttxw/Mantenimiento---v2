<?php

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app.company_name', 'value' => 'Municipalidad Distrital de San Jeronimo', 'type' => SettingType::STRING, 'group_name' => 'general', 'description' => 'Nombre institucional'],
            ['key' => 'maintenance.default_interval_days', 'value' => '180', 'type' => SettingType::INTEGER, 'group_name' => 'maintenance', 'description' => 'Intervalo por defecto para mantenimiento preventivo'],
            ['key' => 'agents.enabled', 'value' => 'true', 'type' => SettingType::BOOLEAN, 'group_name' => 'agents', 'description' => 'Habilita recepcion de sincronizaciones de agentes'],
            ['key' => 'agents.heartbeat_retention_days', 'value' => '7', 'type' => SettingType::INTEGER, 'group_name' => 'agents', 'description' => 'Días de retención para registros de heartbeat'],
            ['key' => 'agents.snapshot_retention_days', 'value' => '90', 'type' => SettingType::INTEGER, 'group_name' => 'agents', 'description' => 'Días de retención para snapshots históricos'],
            ['key' => 'assets.allowed_types', 'value' => json_encode(['laptop', 'computadora', 'impresora', 'switch', 'ups']), 'type' => SettingType::JSON, 'group_name' => 'assets', 'description' => 'Tipos de activos mas usados en el sistema'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting + ['is_sensitive' => false]);
        }
    }
}
