<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Acta de Mantenimiento {{ $campaign->code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; background: #fff; }
        .page { padding: 16mm 14mm 14mm 14mm; }

        .header { display: table; width: 100%; border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; margin-bottom: 10px; }
        .header-left { display: table-cell; vertical-align: middle; width: 60%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }
        .entity-name { font-size: 10pt; font-weight: bold; color: #1e3a5f; text-transform: uppercase; }
        .entity-sub { font-size: 8pt; color: #475569; margin-top: 2px; }
        .doc-badge { display: inline-block; background: #0f4c81; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 8pt; font-weight: bold; margin-bottom: 3px; }
        .doc-code { font-size: 8pt; color: #64748b; }

        .doc-title { text-align: center; margin: 8px 0 6px; }
        .doc-title h1 { font-size: 12pt; font-weight: bold; color: #1e3a5f; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title p { font-size: 8pt; color: #64748b; margin-top: 2px; }

        .section { margin-bottom: 9px; }
        .section-title { background: #1e3a5f; color: #fff; font-size: 8pt; font-weight: bold; padding: 3px 8px; margin-bottom: 5px; }

        .data-grid { display: table; width: 100%; }
        .data-row { display: table-row; }
        .data-label { display: table-cell; width: 28%; padding: 2px 6px; font-size: 7.5pt; color: #64748b; font-weight: bold; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .data-value { display: table-cell; padding: 2px 6px; font-size: 8pt; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: top; }

        .two-col { display: table; width: 100%; }
        .col-half { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
        .col-half:last-child { padding-right: 0; padding-left: 8px; }

        /* Tabla principal de activos */
        table.assets { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-top: 4px; }
        table.assets thead tr { background: #1e3a5f; color: #fff; }
        table.assets thead th { padding: 4px 5px; text-align: left; font-weight: bold; }
        table.assets tbody tr:nth-child(even) { background: #f8fafc; }
        table.assets tbody tr.unit-header { background: #e2e8f0; }
        table.assets tbody tr.unit-header td { font-weight: bold; font-size: 7.5pt; color: #334155; padding: 3px 5px; }
        table.assets tbody td { padding: 3px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.assets tbody td.no-data { text-align: center; color: #94a3b8; font-style: italic; }

        /* Tabla de casos por activo */
        table.cases { width: 100%; border-collapse: collapse; font-size: 7pt; margin-top: 2px; }
        table.cases thead tr { background: #334155; color: #fff; }
        table.cases thead th { padding: 2px 4px; text-align: left; }
        table.cases tbody td { padding: 2px 4px; border-bottom: 1px dotted #e2e8f0; }
        table.cases tbody tr:last-child td { border-bottom: none; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 2px; font-size: 7pt; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-neutral { background: #f1f5f9; color: #475569; }

        .text-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 3px; padding: 5px 7px; font-size: 8pt; color: #334155; min-height: 20px; margin-bottom: 5px; }

        .signatures { display: table; width: 100%; margin-top: 16px; }
        .sig-cell { display: table-cell; width: 33%; text-align: center; padding: 0 6px; }
        .sig-line { border-top: 1px solid #334155; margin-top: 28px; padding-top: 3px; font-size: 7.5pt; font-weight: bold; color: #334155; }
        .sig-label { font-size: 7pt; color: #64748b; }

        .footer { margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 4px; display: table; width: 100%; }
        .footer-left { display: table-cell; font-size: 7pt; color: #94a3b8; }
        .footer-right { display: table-cell; text-align: right; font-size: 7pt; color: #94a3b8; }

        .fw-bold { font-weight: bold; }
        .text-muted { color: #64748b; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="entity-name">Municipalidad Distrital de San Jerónimo</div>
            <div class="entity-sub">Oficina de Tecnologías de la Información</div>
        </div>
        <div class="header-right">
            <div class="doc-badge">ACTA DE MANTENIMIENTO</div>
            <div class="doc-code">Campaña: {{ $campaign->code }}</div>
            <div class="doc-code">Fecha: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- Título --}}
    <div class="doc-title">
        <h1>Acta de Mantenimiento de Equipos TI</h1>
        <p>Unidad: {{ $unit->name }}{{ $unit->parent ? ' — ' . $unit->parent->name : '' }}</p>
    </div>

    {{-- Datos de la campaña --}}
    <div class="section">
        <div class="section-title">1. Datos de la Campaña</div>
        <div class="two-col">
            <div class="col-half">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Campaña</div>
                        <div class="data-value fw-bold">{{ $campaign->name }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Código</div>
                        <div class="data-value">{{ $campaign->code }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Estado</div>
                        <div class="data-value">{{ $campaign->status->label() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-half">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Inicio</div>
                        <div class="data-value">{{ $campaign->start_date?->format('d/m/Y') ?? '—' }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Fin</div>
                        <div class="data-value">{{ $campaign->end_date?->format('d/m/Y') ?? '—' }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Coordinador</div>
                        <div class="data-value fw-bold">{{ $campaign->coordinator?->full_name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @if ($campaign->objective)
        <div style="margin-top:5px">
            <div class="data-label" style="display:block;width:auto;padding:0 0 2px 0;">Objetivo</div>
            <div class="text-box">{{ $campaign->objective }}</div>
        </div>
        @endif
    </div>

    {{-- Unidad orgánica --}}
    <div class="section">
        <div class="section-title">2. Unidad Orgánica</div>
        <div class="data-grid">
            <div class="data-row">
                <div class="data-label">Unidad</div>
                <div class="data-value fw-bold">{{ $unit->name }}</div>
            </div>
            @if ($unit->parent)
            <div class="data-row">
                <div class="data-label">Pertenece a</div>
                <div class="data-value">{{ $unit->parent->name }}</div>
            </div>
            @endif
            <div class="data-row">
                <div class="data-label">Total activos</div>
                <div class="data-value">{{ $assets->count() }}</div>
            </div>
        </div>
    </div>

    {{-- Tabla de activos --}}
    <div class="section">
        <div class="section-title">3. Activos y Mantenimiento Preventivo Ejecutado</div>

        @if ($assets->isEmpty())
        <div class="text-box text-center text-muted">No se encontraron activos de esta unidad en la campaña.</div>
        @else
        <table class="assets">
            <thead>
                <tr>
                    <th style="width:14%">Código / Patr.</th>
                    <th style="width:24%">Nombre / Equipo</th>
                    <th style="width:12%">Tipo</th>
                    <th style="width:14%">Estado Atención</th>
                    <th style="width:16%">Técnico / Fecha</th>
                    <th style="width:20%">Actividades / Checklist</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assets as $asset)
                @php
                    $ca = $asset->campaignAssets->first();
                    $isAttended = $ca && $ca->status->value === 'atendido';
                    $attendedType = $ca?->attended_type === 'agente' ? 'Agente SIGAT' : ($ca?->attended_type === 'manual' ? 'Manual en Campo' : '—');
                    $technician = $ca?->attendedByEmployee ?? $ca?->assignedTechnician;
                @endphp
                <tr>
                    <td>
                        <span class="fw-bold">{{ $asset->internal_code }}</span>
                        @if ($asset->patrimonial_code)
                        <br><span class="text-muted" style="font-size:6.5pt">Pat: {{ $asset->patrimonial_code }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-bold">{{ $asset->name }}</span>
                        @if ($asset->brand || $asset->model)
                        <br><span class="text-muted">{{ $asset->brand }} {{ $asset->model }}</span>
                        @endif
                    </td>
                    <td>{{ $asset->asset_type->label() }}</td>
                    <td>
                        @if ($isAttended)
                            <span class="badge badge-success">Atendido</span>
                            <br><span class="text-muted" style="font-size:6.5pt">({{ $attendedType }})</span>
                        @else
                            <span class="badge badge-neutral">{{ $ca?->status?->label() ?? 'Pendiente' }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($ca?->attended_date)
                            {{ $ca->attended_date->format('d/m/Y') }}
                            <br><span class="text-muted" style="font-size:6.5pt">{{ $technician?->full_name ?? '—' }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($ca && !empty($ca->checklist_tasks))
                            <span style="font-size:6.5pt; color:#334155;">
                                {{ implode(', ', array_map(fn($t) => ucwords(str_replace('_', ' ', $t)), (array)$ca->checklist_tasks)) }}
                            </span>
                        @elseif ($ca?->notes)
                            <span style="font-size:6.5pt; color:#475569;">{{ Str::limit($ca->notes, 60) }}</span>
                        @else
                            <span class="text-muted" style="font-size:6.5pt; font-style:italic">Sin tareas registradas</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Resumen --}}
    @php
        $totalIncluded = $assets->count();
        $attendedAgent = $assets->filter(fn ($a) => $a->campaignAssets->first()?->status?->value === 'atendido' && $a->campaignAssets->first()?->attended_type === 'agente')->count();
        $attendedManual = $assets->filter(fn ($a) => $a->campaignAssets->first()?->status?->value === 'atendido' && $a->campaignAssets->first()?->attended_type === 'manual')->count();
        $totalAttended = $attendedAgent + $attendedManual;
        $totalPending = $totalIncluded - $totalAttended;
    @endphp
    <div class="section">
        <div class="section-title">4. Resumen de Ejecución</div>
        <div class="two-col">
            <div class="col-half">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Total Activos Unidad</div>
                        <div class="data-value fw-bold">{{ $totalIncluded }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Total Atendidos</div>
                        <div class="data-value fw-bold" style="color: #166534;">{{ $totalAttended }}</div>
                    </div>
                </div>
            </div>
            <div class="col-half">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Vía Agente / Manual</div>
                        <div class="data-value">{{ $attendedAgent }} agente / {{ $attendedManual }} manual</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Pendientes</div>
                        <div class="data-value">{{ $totalPending }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Firmas --}}
    <div class="signatures">
        <div class="sig-cell">
            <div class="sig-line">{{ $campaign->coordinator?->full_name ?? '___________________' }}</div>
            <div class="sig-label">Coordinador de campaña</div>
        </div>
        <div class="sig-cell">
            <div class="sig-line">{{ $unit->name }}</div>
            <div class="sig-label">Responsable de unidad</div>
        </div>
        <div class="sig-cell">
            <div class="sig-line">Jefe OTI</div>
            <div class="sig-label">Visto bueno</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">SIGAT — Sistema de Gestión de Activos y Mantenimiento · MDSJ</div>
        <div class="footer-right">Generado el {{ now()->format('d/m/Y H:i') }} · {{ $campaign->code }}</div>
    </div>

</div>
</body>
</html>
