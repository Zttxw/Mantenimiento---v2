<?php

namespace Database\Seeders;

use App\Enums\OrgUnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrganizationalUnitSeeder extends Seeder
{
    private const SOURCE_FILE = 'database/seeders/data/sgd/dependencias.json';

    public function run(): void
    {
        $path = base_path(self::SOURCE_FILE);
        if (! is_file($path)) {
            throw new RuntimeException("No se encontro {$path}. Ejecuta export_to_json.py del proyecto SGD primero.");
        }

        $payload = json_decode(file_get_contents($path), true);
        $rows = $payload['data'] ?? [];
        if (empty($rows)) {
            $this->command?->warn('dependencias.json esta vacio.');

            return;
        }

        $cleanRows = $this->filterValid($rows);
        $codeMap = $this->resolveUniqueCodes($cleanRows);

        DB::transaction(function () use ($cleanRows, $codeMap) {
            $this->upsertWithoutParent($cleanRows, $codeMap);
            $this->resolveParents($cleanRows, $codeMap);
        });

        $skipped = count($rows) - count($cleanRows);
        $this->command?->info(sprintf(
            'Dependencias: %d insertadas/actualizadas, %d descartadas (basura).',
            count($cleanRows),
            $skipped
        ));
    }

    private function filterValid(array $rows): array
    {
        return array_values(array_filter($rows, function (array $r): bool {
            $sigla = trim((string) ($r['sigla'] ?? ''));
            $nombre = trim((string) ($r['nombre'] ?? ''));

            if ($sigla === '' || strcasecmp($sigla, 'NULL') === 0) {
                return false;
            }
            if ($nombre === '' || strcasecmp($nombre, 'NULL') === 0) {
                return false;
            }

            return true;
        }));
    }

    private function resolveUniqueCodes(array $rows): array
    {
        $countBySigla = [];
        foreach ($rows as $r) {
            $sigla = $r['sigla'];
            $countBySigla[$sigla] = ($countBySigla[$sigla] ?? 0) + 1;
        }

        $codeMap = [];
        foreach ($rows as $r) {
            $sigla = $r['sigla'];
            $codeMap[$r['co_dependencia']] = $countBySigla[$sigla] > 1
                ? "{$sigla}-{$r['co_dependencia']}"
                : $sigla;
        }

        return $codeMap;
    }

    private function upsertWithoutParent(array $rows, array $codeMap): void
    {
        foreach ($rows as $r) {
            $code = $codeMap[$r['co_dependencia']];

            OrganizationalUnit::updateOrCreate(
                ['code' => $code],
                [
                    'parent_id' => null,
                    'type' => OrgUnitType::fromName($r['nombre']),
                    'name' => $this->normalizeName($r['nombre']),
                    'is_active' => (bool) ($r['is_active'] ?? 1),
                    'sort_order' => (int) ($r['nivel'] ?? 0),
                    'meta_json' => [
                        'co_dependencia' => $r['co_dependencia'],
                        'sigla' => $r['sigla'],
                        'nombre_corto' => $r['nombre_corto'] ?: null,
                        'nivel' => $r['nivel'] ?: null,
                        'tipo_sgd' => $r['tipo_sgd'] ?: null,
                        'co_dependencia_padre' => $r['co_dependencia_padre'] ?: null,
                        'co_empleado_jefe' => $r['co_empleado_jefe'] ?: null,
                        'co_empleado_titular' => $r['co_empleado_titular'] ?: null,
                        'cargo_completo' => $r['cargo_completo'] ?: null,
                        'mesa_partes' => $r['mesa_partes'] === '1',
                    ],
                ]
            );
        }
    }

    private function resolveParents(array $rows, array $codeMap): void
    {
        $codeToId = OrganizationalUnit::query()
            ->pluck('id', 'code')
            ->all();

        foreach ($rows as $r) {
            $childCode = $codeMap[$r['co_dependencia']];
            $parentSgd = $r['co_dependencia_padre'] ?? '';

            if ($parentSgd === '' || $parentSgd === $r['co_dependencia']) {
                continue;
            }

            $parentCode = $codeMap[$parentSgd] ?? null;
            if ($parentCode === null || ! isset($codeToId[$parentCode])) {
                continue;
            }

            $childId = $codeToId[$childCode] ?? null;
            if ($childId === null) {
                continue;
            }

            OrganizationalUnit::whereKey($childId)
                ->update(['parent_id' => $codeToId[$parentCode]]);
        }
    }

    private function normalizeName(string $name): string
    {
        return mb_convert_case(mb_strtolower(trim($name), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
