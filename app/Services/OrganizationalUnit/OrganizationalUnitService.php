<?php

namespace App\Services\OrganizationalUnit;

use App\DTOs\OrganizationalUnitDTO;
use App\Exceptions\OrganizationalUnitException;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrganizationalUnitService
{
    public function getAll(): Collection
    {
        return OrganizationalUnit::with('parent')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTreeArray(): array
    {
        $all = OrganizationalUnit::with(['responsible:id,full_name,position,dni,email', 'parent:id,name'])
            ->withCount(['empoyees as employees_count', 'assets as assets_count'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $grouped = $all->groupBy('parent_id');

        $formatNode = function ($unit, $depth = 1) use (&$formatNode, $grouped) {
            $children = ($grouped->get($unit->id) ?? collect())->map(fn ($child) => $formatNode($child, $depth + 1))->values()->all();

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'type' => $unit->type->value,
                'type_label' => $unit->type->label(),
                'level' => $depth,
                'parent_id' => $unit->parent_id,
                'parent_name' => $unit->parent?->name,
                'is_active' => (bool) $unit->is_active,
                'employees_count' => (int) $unit->employees_count,
                'assets_count' => (int) $unit->assets_count,
                'responsible' => $unit->responsible ? [
                    'id' => $unit->responsible->id,
                    'name' => $unit->responsible->full_name,
                    'position' => $unit->responsible->position,
                    'dni' => $unit->responsible->dni,
                    'email' => $unit->responsible->email,
                ] : null,
                'show_url' => route('organizational-units.show', $unit),
                'edit_url' => route('organizational-units.edit', $unit),
                'children' => $children,
            ];
        };

        $roots = $grouped->get(null) ?? collect();

        return $roots->map(fn ($root) => $formatNode($root, 1))->values()->all();
    }

    public function getOfficialOrganigram(): array
    {
        $all = OrganizationalUnit::with(['responsible:id,full_name,position,dni,email', 'parent:id,name'])
            ->withCount(['empoyees as employees_count', 'assets as assets_count', 'children as children_count'])
            ->get()
            ->keyBy('id');

        $formatItem = function (?OrganizationalUnit $unit, string $officialLabel, string $colorGroup = 'default', ?string $note = null) {
            if (!$unit) {
                return [
                    'id' => null,
                    'name' => $officialLabel,
                    'db_name' => $officialLabel,
                    'code' => null,
                    'color_group' => $colorGroup,
                    'note' => $note,
                    'is_registered' => false,
                    'employees_count' => 0,
                    'assets_count' => 0,
                    'children_count' => 0,
                    'responsible' => null,
                    'show_url' => null,
                    'edit_url' => null,
                ];
            }

            return [
                'id' => $unit->id,
                'name' => $officialLabel,
                'db_name' => $unit->name,
                'code' => $unit->code,
                'color_group' => $colorGroup,
                'note' => $note,
                'is_registered' => true,
                'is_active' => (bool) $unit->is_active,
                'employees_count' => (int) $unit->employees_count,
                'assets_count' => (int) $unit->assets_count,
                'children_count' => (int) $unit->children_count,
                'responsible' => $unit->responsible ? [
                    'id' => $unit->responsible->id,
                    'name' => $unit->responsible->full_name,
                    'position' => $unit->responsible->position,
                    'dni' => $unit->responsible->dni,
                    'email' => $unit->responsible->email,
                ] : null,
                'show_url' => route('organizational-units.show', $unit),
                'edit_url' => route('organizational-units.edit', $unit),
            ];
        };

        return [
            'meta' => [
                'title' => 'ORGANIGRAMA DE LA MUNICIPALIDAD DISTRITAL DE SAN JERONIMO',
                'subtitle' => 'Aprobado mediante Ordenanza Municipal N° 015-2023-MDSJ/C',
            ],
            'top' => [
                'concejo' => [
                    'id' => null,
                    'name' => 'CONCEJO MUNICIPAL',
                    'db_name' => 'Concejo Municipal',
                    'code' => 'CM',
                    'color_group' => 'cobalt',
                    'note' => 'Órgano de Gobierno y Normativo',
                    'is_registered' => false,
                    'employees_count' => 0,
                    'assets_count' => 0,
                    'children_count' => 0,
                    'responsible' => null,
                    'show_url' => null,
                ],
                'control_left' => [
                    $formatItem($all->get(8), 'ÓRGANO DE CONTROL INSTITUCIONAL', 'slate', 'Control Gubernamental'),
                    $formatItem($all->get(9), 'PROCURADURÍA PÚBLICA MUNICIPAL', 'slate', 'Defensa Jurídica'),
                ],
                'coordination_right' => [
                    [
                        'id' => null,
                        'name' => 'CONSEJO DE COORDINACIÓN LOCAL DISTRITAL',
                        'db_name' => 'Consejo de Coordinación Local Distrital',
                        'code' => 'CCLD',
                        'color_group' => 'peach',
                        'note' => 'Órgano de Coordinación y Concertación',
                        'is_registered' => false,
                        'employees_count' => 0,
                        'assets_count' => 0,
                        'children_count' => 0,
                        'responsible' => null,
                        'show_url' => null,
                    ],
                    [
                        'id' => null,
                        'name' => 'JUNTA DE DELEGADOS VECINALES COMUNALES',
                        'db_name' => 'Junta de Delegados Vecinales Comunales',
                        'code' => 'JDVC',
                        'color_group' => 'peach',
                        'note' => 'Participación Vecinal y Comunal',
                        'is_registered' => false,
                        'employees_count' => 0,
                        'assets_count' => 0,
                        'children_count' => 0,
                        'responsible' => null,
                        'show_url' => null,
                    ],
                ],
                'alcaldia' => $formatItem($all->get(1), 'ALCALDÍA', 'cobalt', 'Máxima Autoridad Ejecutiva'),
                'secretaria_general' => $formatItem($all->get(7), 'OFICINA DE SECRETARÍA GENERAL Y GESTIÓN DOCUMENTARIA', 'mint', 'Órgano de Apoyo Directo'),
                'gerencia_municipal' => $formatItem($all->get(6), 'GERENCIA MUNICIPAL', 'cobalt', 'Máxima Autoridad Administrativa'),
            ],
            'direct_offices' => [
                $formatItem($all->get(22), 'OFICINA DE IMAGEN INSTITUCIONAL Y COMUNICACIONES', 'mint'),
                $formatItem($all->get(20), 'OFICINA DE EJECUCIÓN COACTIVA', 'mint'),
                $formatItem($all->get(21), 'OFICINA DE GESTIÓN DEL RIESGO DE DESASTRES', 'mint'),
            ],
            'middle_groups' => [
                'apoyo' => [
                    [
                        'header' => $formatItem($all->get(23), 'OFICINA GENERAL DE ADMINISTRACIÓN', 'forest_header'),
                        'sub_offices' => [
                            $formatItem($all->get(46), 'OFICINA DE RECURSOS HUMANOS', 'forest_sub'),
                            $formatItem($all->get(37), 'OFICINA DE ABASTECIMIENTO', 'forest_sub'),
                            $formatItem($all->get(42), 'OFICINA DE GESTIÓN PATRIMONIAL', 'forest_sub'),
                            $formatItem($all->get(38), 'OFICINA DE CONTABILIDAD', 'forest_sub'),
                            $formatItem($all->get(48), 'OFICINA DE TESORERÍA', 'forest_sub'),
                        ],
                    ],
                    [
                        'header' => $formatItem($all->get(27), 'OFICINA GENERAL DE SUPERVISIÓN Y LIQUIDACIÓN DE INVERSIONES', 'forest_header'),
                        'sub_offices' => [],
                    ],
                ],
                'asesoramiento' => [
                    [
                        'header' => $formatItem($all->get(26), 'OFICINA GENERAL DE PLANEAMIENTO Y PRESUPUESTO', 'amber_header'),
                        'sub_offices' => [
                            $formatItem($all->get(44), 'OFICINA DE PRESUPUESTO', 'amber_sub'),
                            $formatItem($all->get(43), 'OFICINA DE PLANEAMIENTO Y MODERNIZACIÓN', 'amber_sub'),
                            $formatItem($all->get(47), 'OFICINA DE TECNOLOGÍAS DE LA INFORMACIÓN', 'amber_sub_oti'),
                            $formatItem($all->get(39), 'OFICINA DE COOPERACIÓN TÉCNICA NACIONAL E INTERNACIONAL', 'amber_sub'),
                            $formatItem($all->get(45), 'OFICINA DE PROGRAMACIÓN MULTIANUAL DE INVERSIONES', 'amber_sub'),
                        ],
                    ],
                    [
                        'header' => $formatItem($all->get(24), 'OFICINA GENERAL DE ASESORÍA JURÍDICA', 'amber_header'),
                        'sub_offices' => [],
                    ],
                    [
                        'header' => $formatItem($all->get(25), 'OFICINA GENERAL DE GESTIÓN DE INVERSIONES', 'amber_header'),
                        'sub_offices' => [
                            $formatItem($all->get(41), 'OFICINA DE FORMULACIÓN Y EVALUACIÓN DE INVERSIONES', 'amber_sub'),
                            $formatItem($all->get(40), 'OFICINA DE ELABORACIÓN DE EXPEDIENTES TÉCNICOS', 'amber_sub'),
                        ],
                    ],
                ],
            ],
            'line_gerencias' => [
                [
                    'header' => $formatItem($all->get(14), 'GERENCIA DE ADMINISTRACIÓN TRIBUTARIA', 'sky_header'),
                    'sub_gerencias' => [
                        $formatItem($all->get(66), 'SUBGERENCIA DE RENTAS', 'sky_sub'),
                        $formatItem($all->get(65), 'SUBGERENCIA DE REGISTRO CIVIL Y CEMENTERIOS', 'sky_sub'),
                        $formatItem($all->get(58), 'SUBGERENCIA DE FISCALIZACIÓN TRIBUTARIA', 'sky_sub'),
                    ],
                ],
                [
                    'header' => $formatItem($all->get(19), 'GERENCIA DE INFRAESTRUCTURA', 'sky_header'),
                    'sub_gerencias' => [
                        $formatItem($all->get(56), 'SUBGERENCIA DE EJECUCIÓN DE INVERSIONES', 'sky_sub'),
                        $formatItem($all->get(61), 'SUBGERENCIA DE MANTENIMIENTO', 'sky_sub'),
                        $formatItem($all->get(57), 'SUBGERENCIA DE EQUIPO MECÁNICO', 'sky_sub'),
                    ],
                ],
                [
                    'header' => $formatItem($all->get(17), 'GERENCIA DE DESARROLLO URBANO Y RURAL', 'sky_header'),
                    'sub_gerencias' => [
                        $formatItem($all->get(63), 'SUBGERENCIA DE ORDENAMIENTO TERRITORIAL Y TRÁNSITO', 'sky_sub'),
                        $formatItem($all->get(59), 'SUBGERENCIA DE GESTIÓN DE EDIFICACIONES', 'sky_sub'),
                        $formatItem($all->get(51), 'SUBGERENCIA DE CATASTRO Y SANEAMIENTO FÍSICO LEGAL', 'sky_sub'),
                    ],
                ],
                [
                    'header' => $formatItem($all->get(15), 'GERENCIA DE DESARROLLO ECONÓMICO LOCAL', 'sky_header'),
                    'sub_gerencias' => [
                        $formatItem($all->get(53), 'SUBGERENCIA DE COMPETITIVIDAD EMPRESARIAL, AGRARIA Y TURISMO', 'sky_sub'),
                        $formatItem($all->get(52), 'SUBGERENCIA DE COMERCIO, MERCADOS Y POLICÍA MUNICIPAL', 'sky_sub'),
                        $formatItem($all->get(54), 'SUBGERENCIA DE CONTROL SANITARIO Y CAMAL MUNICIPAL', 'sky_sub'),
                        $formatItem($all->get(68), 'SUBGERENCIA DE SEGURIDAD CIUDADANA', 'sky_sub'),
                    ],
                ],
                [
                    'header' => $formatItem($all->get(16), 'GERENCIA DE DESARROLLO SOCIAL', 'sky_header'),
                    'sub_gerencias' => [
                        $formatItem($all->get(55), 'SUBGERENCIA DE EDUCACIÓN, CULTURA Y DEPORTE', 'sky_sub'),
                        $formatItem($all->get(64), 'SUBGERENCIA DE PROGRAMAS SOCIALES Y PARTICIPACIÓN CIUDADANA', 'sky_sub'),
                    ],
                ],
                [
                    'header' => $formatItem($all->get(18), 'GERENCIA DE GESTIÓN AMBIENTAL', 'sky_header'),
                    'sub_gerencias' => [
                        $formatItem($all->get(60), 'SUBGERENCIA DE GESTIÓN DE RESIDUOS SÓLIDOS', 'sky_sub'),
                        $formatItem($all->get(67), 'SUBGERENCIA DE SANEAMIENTO BÁSICO Y ÁREA TÉCNICA MUNICIPAL', 'sky_sub'),
                        $formatItem($all->get(62), 'SUBGERENCIA DE MEDIO AMBIENTE', 'sky_sub'),
                    ],
                ],
            ],
        ];
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return OrganizationalUnit::with(['parent', 'responsible'])
            ->withCount(['empoyees as employees_count', 'assets as assets_count'])
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('code', 'like', "%{$s}%");
                });
            })
            ->when(!empty($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    public function findOrFail(int $id): OrganizationalUnit
    {
        return OrganizationalUnit::findOr($id, fn () => throw OrganizationalUnitException::notFound($id));
    }

    public function create(OrganizationalUnitDTO $dto): OrganizationalUnit
    {
        return OrganizationalUnit::create($dto->toArray());
    }

    public function update(OrganizationalUnit $unit, OrganizationalUnitDTO $dto): OrganizationalUnit
    {
        if ($dto->parent_id && $this->wouldCreateCircularReference($unit, $dto->parent_id)) {
            throw OrganizationalUnitException::circularReference($unit->name);
        }

        $unit->update($dto->toArray());

        return $unit->fresh();
    }

    public function delete(OrganizationalUnit $unit): void
    {
        if ($unit->children()->exists()) {
            throw OrganizationalUnitException::hasChildren($unit->name);
        }

        if ($unit->empoyees()->exists()) {
            throw OrganizationalUnitException::hasEmployees($unit->name);
        }

        if ($unit->assets()->exists()) {
            throw OrganizationalUnitException::hasAssets($unit->name);
        }

        $unit->delete();
    }

    private function wouldCreateCircularReference(OrganizationalUnit $unit, int $newParentId): bool
    {
        $current = OrganizationalUnit::find($newParentId);

        while ($current) {
            if ($current->id === $unit->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }
}
