<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeSeeder extends Seeder
{
    private const SOURCE_FILE = 'database/seeders/data/sgd/empleados.json';

    private const DEPENDENCIAS_FILE = 'database/seeders/data/sgd/dependencias.json';

    public function run(): void
    {
        $path = base_path(self::SOURCE_FILE);
        if (! is_file($path)) {
            throw new RuntimeException("No se encontro {$path}. Ejecuta export_to_json.py del proyecto SGD primero.");
        }

        $payload = json_decode(file_get_contents($path), true);
        $rows = $payload['data'] ?? [];
        if (empty($rows)) {
            $this->command?->warn('empleados.json esta vacio.');

            return;
        }

        [$cleanRows, $skipped] = $this->filterValid($rows);

        $unitsBySgd = $this->loadUnitsIndexedBySgdCode();

        DB::transaction(function () use ($cleanRows, $unitsBySgd) {
            $this->upsertEmployees($cleanRows, $unitsBySgd);
            $this->assignResponsibles($unitsBySgd);
        });

        $this->command?->info(sprintf(
            'Empleados: %d insertados/actualizados, %d descartados.',
            count($cleanRows),
            $skipped
        ));
    }

    private function filterValid(array $rows): array
    {
        $clean = [];
        $skipped = 0;
        $seenDni = [];

        foreach ($rows as $r) {
            $dni = trim((string) ($r['dni'] ?? ''));
            $nombres = trim((string) ($r['nombres'] ?? ''));
            $apePat = trim((string) ($r['apellido_paterno'] ?? ''));
            $apeMat = trim((string) ($r['apellido_materno'] ?? ''));

            if ($dni === '' || strlen($dni) !== 8 || ! ctype_digit($dni) || $dni === '00000000') {
                $skipped++;

                continue;
            }
            if ($nombres === '' || $apePat === '') {
                $skipped++;

                continue;
            }
            if (isset($seenDni[$dni])) {
                $skipped++;

                continue;
            }
            $seenDni[$dni] = true;

            $r['nombres'] = $nombres;
            $r['apellido_paterno'] = $apePat;
            $r['apellido_materno'] = $apeMat;
            $clean[] = $r;
        }

        return [$clean, $skipped];
    }

    private function loadUnitsIndexedBySgdCode(): array
    {
        $units = OrganizationalUnit::query()->get(['id', 'meta_json']);

        $map = [];
        foreach ($units as $u) {
            $sgdCode = $u->meta_json['co_dependencia'] ?? null;
            if ($sgdCode !== null) {
                $map[$sgdCode] = $u;
            }
        }

        return $map;
    }

    private function upsertEmployees(array $rows, array $unitsBySgd): void
    {
        foreach ($rows as $r) {
            $unit = $unitsBySgd[$r['co_dependencia']] ?? null;

            $name = $this->titleCase($r['nombres']);
            $lastName = $this->titleCase(trim($r['apellido_paterno'].' '.$r['apellido_materno']));
            $fullName = trim($lastName.', '.$name);

            Employee::updateOrCreate(
                ['dni' => $r['dni']],
                [
                    'name' => $name,
                    'last_name' => $lastName,
                    'full_name' => $fullName,
                    'email' => $r['email'] !== '' ? mb_strtolower($r['email']) : null,
                    'phone' => null,
                    'position' => $r['cargo'] !== '' ? $this->titleCase($r['cargo']) : null,
                    'organizational_unit_id' => $unit?->id,
                    'is_technician' => false,
                    'specialty' => null,
                    'level' => null,
                    'is_active' => (bool) ($r['is_active'] ?? 1),
                ]
            );
        }
    }

    private function assignResponsibles(array $unitsBySgd): void
    {
        $depPath = base_path(self::DEPENDENCIAS_FILE);
        if (! is_file($depPath)) {
            return;
        }
        $deps = json_decode(file_get_contents($depPath), true)['data'] ?? [];

        $employeesBySgd = $this->buildEmployeeMapBySgdCode();

        foreach ($deps as $r) {
            $unit = $unitsBySgd[$r['co_dependencia']] ?? null;
            if ($unit === null) {
                continue;
            }

            $jefeSgd = $r['co_empleado_jefe'] ?? '';
            $titularSgd = $r['co_empleado_titular'] ?? '';

            $employeeId = $employeesBySgd[$jefeSgd]
                ?? $employeesBySgd[$titularSgd]
                ?? null;

            if ($employeeId !== null && $unit->responsible_employee_id !== $employeeId) {
                $unit->responsible_employee_id = $employeeId;
                $unit->save();
            }
        }
    }

    private function buildEmployeeMapBySgdCode(): array
    {
        $empPath = base_path(self::SOURCE_FILE);
        $empRows = json_decode(file_get_contents($empPath), true)['data'] ?? [];

        $dniByCo = [];
        foreach ($empRows as $r) {
            $co = $r['co_empleado'] ?? '';
            $dni = trim((string) ($r['dni'] ?? ''));
            if ($co !== '' && strlen($dni) === 8 && ctype_digit($dni) && $dni !== '00000000') {
                $dniByCo[$co] = $dni;
            }
        }

        $idByDni = Employee::query()->pluck('id', 'dni')->all();

        $idByCo = [];
        foreach ($dniByCo as $co => $dni) {
            if (isset($idByDni[$dni])) {
                $idByCo[$co] = $idByDni[$dni];
            }
        }

        return $idByCo;
    }

    private function titleCase(string $value): string
    {
        return mb_convert_case(mb_strtolower(trim($value), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
