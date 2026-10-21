<?php

namespace App\Enums;

enum OrgUnitType: string
{
    case ALCALDIA = 'alcaldia';
    case GERENCIA_MUNICIPAL = 'gerencia_municipal';
    case GERENCIA = 'gerencia';
    case OFICINA_GENERAL = 'oficina_general';
    case SUBGERENCIA = 'subgerencia';
    case OFICINA = 'oficina';
    case SECRETARIA = 'secretaria';
    case PROCURADURIA = 'procuraduria';
    case ORGANO_CONTROL = 'organo_control';
    case COMITE = 'comite';
    case UNIDAD = 'unidad';
    case AREA = 'area';
    case DIVISION = 'division';
    case CENTRO = 'centro';
    case PROGRAMA = 'programa';
    case CAJA = 'caja';
    case PROYECTO = 'proyecto';
    case SEDE = 'sede';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::ALCALDIA => 'Alcaldia',
            self::GERENCIA_MUNICIPAL => 'Gerencia Municipal',
            self::GERENCIA => 'Gerencia',
            self::OFICINA_GENERAL => 'Oficina General',
            self::SUBGERENCIA => 'Subgerencia',
            self::OFICINA => 'Oficina',
            self::SECRETARIA => 'Secretaria',
            self::PROCURADURIA => 'Procuraduria',
            self::ORGANO_CONTROL => 'Organo de Control',
            self::COMITE => 'Comite',
            self::UNIDAD => 'Unidad',
            self::AREA => 'Area',
            self::DIVISION => 'Division',
            self::CENTRO => 'Centro',
            self::PROGRAMA => 'Programa',
            self::CAJA => 'Caja',
            self::PROYECTO => 'Proyecto',
            self::SEDE => 'Sede',
            self::OTRO => 'Otro',
        };
    }

    public static function fromName(string $name): self
    {
        $upper = mb_strtoupper(self::stripAccents(trim($name)), 'UTF-8');

        return match (true) {
            str_starts_with($upper, 'ALCALD') => self::ALCALDIA,
            str_starts_with($upper, 'GERENCIA MUNICIPAL') => self::GERENCIA_MUNICIPAL,
            str_starts_with($upper, 'GERENCIA ') => self::GERENCIA,
            str_starts_with($upper, 'SUBGERENCIA') || str_starts_with($upper, 'SUB GERENCIA') => self::SUBGERENCIA,
            str_starts_with($upper, 'OFICINA GENERAL') => self::OFICINA_GENERAL,
            str_starts_with($upper, 'OFICINA ') => self::OFICINA,
            str_starts_with($upper, 'SECRETAR') => self::SECRETARIA,
            str_starts_with($upper, 'PROCURADUR') => self::PROCURADURIA,
            str_contains($upper, 'ORGANO DE CONTROL') => self::ORGANO_CONTROL,
            str_starts_with($upper, 'COMITE') || str_starts_with($upper, 'COMISION') => self::COMITE,
            str_starts_with($upper, 'UNIDAD') => self::UNIDAD,
            str_starts_with($upper, 'AREA ') => self::AREA,
            str_starts_with($upper, 'DIVISION') => self::DIVISION,
            str_starts_with($upper, 'CENTRO ') => self::CENTRO,
            str_starts_with($upper, 'PROGRAMA') => self::PROGRAMA,
            str_starts_with($upper, 'CAJA ') => self::CAJA,
            str_starts_with($upper, 'PROYECTO') || str_starts_with($upper, 'OBRA ') || str_starts_with($upper, 'ACTIVIDAD ') || str_starts_with($upper, 'IOARR ') => self::PROYECTO,
            default => self::OTRO,
        };
    }

    private static function stripAccents(string $value): string
    {
        $from = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ', 'á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'];
        $to = ['A', 'E', 'I', 'O', 'U', 'U', 'N', 'a', 'e', 'i', 'o', 'u', 'u', 'n'];

        return str_replace($from, $to, $value);
    }
}
