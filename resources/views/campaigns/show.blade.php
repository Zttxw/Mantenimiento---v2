<x-layouts.app title="{{ $campaign->name }}" moduleLabel="Mantenimiento" pageTitle="Campaña">

    @php
        $totalAssets     = $campaign->campaignAssets->count();
        $atendidosAgente = $campaign->campaignAssets->where('status.value', 'atendido')->where('attended_type', 'agente')->count();
        $atendidosManual = $campaign->campaignAssets->where('status.value', 'atendido')->where('attended_type', 'manual')->count();
        $atendidos       = $atendidosAgente + $atendidosManual;
        $pendientes      = $totalAssets - $atendidos;
        $progress        = $totalAssets > 0 ? round(($atendidos / $totalAssets) * 100) : 0;
        $isActive        = $campaign->status->value === 'en_curso';

        $statusLabel = match($campaign->status->value) {
            'pendiente_aprobacion' => 'Pendiente Aprobación',
            'planificada' => 'Planificada',
            'en_curso' => 'En Curso',
            'pausada' => 'Pausada',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            default => $campaign->status->label(),
        };

        $dotColor = match($campaign->status->value) {
            'en_curso' => 'bg-emerald-500',
            'pendiente_aprobacion' => 'bg-amber-500',
            'planificada' => 'bg-zinc-400',
            'completada' => 'bg-zinc-300',
            default => 'bg-zinc-400',
        };        // Extract unique offices and types for client filters
        $campaignUnits = $campaign->campaignAssets
            ->map(fn($ca) => $ca->asset->organizationalUnit)
            ->filter()
            ->unique('id')
            ->sortBy('name');

        // Metadatos y etiquetas para cada tipo de hardware
        $typeMeta = [
            'computadora' => ['label' => 'Computadoras / PCs', 'short' => 'PCs', 'icon' => 'desktop', 'order' => 1],
            'laptop' => ['label' => 'Laptops y Portátiles', 'short' => 'Laptops', 'icon' => 'laptop', 'order' => 2],
            'monitor' => ['label' => 'Monitores y Pantallas', 'short' => 'Monitores', 'icon' => 'monitor', 'order' => 3],
            'impresora' => ['label' => 'Impresoras y Multifuncionales', 'short' => 'Impresoras', 'icon' => 'printer', 'order' => 4],
            'escaner' => ['label' => 'Escáneres', 'short' => 'Escáneres', 'icon' => 'scanner', 'order' => 5],
            'servidor' => ['label' => 'Servidores', 'short' => 'Servidores', 'icon' => 'server', 'order' => 6],
            'tablet' => ['label' => 'Tablets', 'short' => 'Tablets', 'icon' => 'tablet', 'order' => 7],
            'ups' => ['label' => 'UPS / Reguladores', 'short' => 'UPS', 'icon' => 'other', 'order' => 8],
            'otro' => ['label' => 'Otros Equipos', 'short' => 'Otros', 'icon' => 'other', 'order' => 99],
        ];

        // Resumen agrupado por tipo de hardware con conteos y porcentajes
        $categoriesStats = $campaign->campaignAssets->groupBy(function ($ca) {
            return $ca->asset->asset_type?->value ?? 'otro';
        })->map(function ($items, $typeKey) use ($typeMeta) {
            $total = $items->count();
            $attended = $items->where('status.value', 'atendido')->count();
            $pending = $total - $attended;
            $percent = $total > 0 ? round(($attended / $total) * 100) : 0;
            $meta = $typeMeta[$typeKey] ?? [
                'label' => ucfirst(str_replace('_', ' ', $typeKey)),
                'short' => ucfirst(str_replace('_', ' ', $typeKey)),
                'icon' => 'other',
                'order' => 50,
            ];

            return [
                'key' => $typeKey,
                'label' => $meta['label'],
                'short' => $meta['short'],
                'icon' => $meta['icon'],
                'order' => $meta['order'],
                'total' => $total,
                'attended' => $attended,
                'pending' => $pending,
                'percent' => $percent,
            ];
        })->sortBy('order')->values();

        $campaignTypes = $categoriesStats->pluck('key')->values();

        $assetsData = $campaign->campaignAssets->map(function ($ca) {
            return [
                'id' => $ca->id,
                'asset_id' => $ca->asset_id,
                'status' => $ca->status->value,
                'attended_type' => $ca->attended_type,
                'attended_date' => $ca->attended_date?->format('d/m'),
                'full_date' => $ca->attended_date?->format('Y-m-d'),
                'technician' => $ca->attendedByEmployee?->full_name ?? $ca->assignedTechnician?->full_name ?? '—',
                'technician_id' => $ca->attended_by_employee_id ?? $ca->assigned_technician_id,
                'patrimonial_code' => $ca->asset->patrimonial_code ?? '',
                'internal_code' => $ca->asset->internal_code ?? '',
                'name' => $ca->asset->name,
                'type' => $ca->asset->asset_type?->value ?? 'otro',
                'type_label' => $ca->asset->asset_type?->label() ?? 'Otro',
                'brand' => $ca->asset->brand ?? '',
                'model' => $ca->asset->model ?? '',
                'unit_id' => $ca->asset->organizational_unit_id ?? 0,
                'unit_name' => $ca->asset->organizationalUnit?->name ?? '—',
                'responsible_id' => $ca->asset->responsible_employee_id ?? '',
                'responsible_name' => $ca->asset->responsible?->full_name ?? '—',
                'condition' => $ca->asset_condition_after ?? $ca->asset->condition?->value ?? 'bueno',
                'tasks' => $ca->checklist_tasks ?? ['sopleteado_limpieza', 'temporales_optimizacion', 'licenciamiento_software'],
                'notes' => $ca->notes ?? '',
            ];
        })->values();
    @endphp

    <div x-data="{
        search: '',
        statusFilter: 'all',
        typeFilter: 'all',
        unitFilter: 'all',
        sortBy: 'unit',
        page: 1,
        perPage: 50,
        assets: @js($assetsData),
        categories: @js($categoriesStats),
        attendModalOpen: false,
        quickAssetModalOpen: false,
        bulkUnitModalOpen: false,
        addAssetModalOpen: false,
        actaModalOpen: false,
        activeCa: {
            id: null,
            name: '',
            internalCode: '',
            patrimonialCode: '',
            unitId: '',
            responsibleId: '',
            condition: 'bueno',
            technicianId: '{{ auth()->user()->employee?->id }}',
            date: '{{ now()->toDateString() }}',
            tasks: [],
            notes: ''
        },
        openAttendModal(item) {
            this.activeCa.id = item.id;
            this.activeCa.name = item.name;
            this.activeCa.internalCode = item.internal_code || '';
            this.activeCa.patrimonialCode = item.patrimonial_code || '';
            this.activeCa.unitId = item.unit_id || '';
            this.activeCa.responsibleId = item.responsible_id || '';
            this.activeCa.condition = item.condition || 'bueno';
            this.activeCa.technicianId = item.technician_id || '{{ auth()->user()->employee?->id }}';
            this.activeCa.date = item.full_date || '{{ now()->toDateString() }}';
            this.activeCa.tasks = Array.isArray(item.tasks) ? [...item.tasks] : ['sopleteado_limpieza', 'temporales_optimizacion', 'licenciamiento_software'];
            this.activeCa.notes = item.notes || '';
            this.attendModalOpen = true;
        },
        toggleTask(task) {
            const idx = this.activeCa.tasks.indexOf(task);
            if (idx > -1) {
                this.activeCa.tasks.splice(idx, 1);
            } else {
                this.activeCa.tasks.push(task);
            }
        },
        get filteredAssets() {
            const q = this.search.trim().toLowerCase();
            let result = this.assets.filter(item => {
                if (this.statusFilter === 'pending' && item.status === 'atendido') return false;
                if (this.statusFilter === 'attended' && item.status !== 'atendido') return false;
                if (this.typeFilter !== 'all' && item.type !== this.typeFilter) return false;
                if (this.unitFilter !== 'all' && String(item.unit_id) !== String(this.unitFilter)) return false;

                if (!q) return true;
                const text = (item.patrimonial_code + ' ' + item.internal_code + ' ' + item.name + ' ' + item.unit_name + ' ' + item.responsible_name + ' ' + item.technician + ' ' + item.brand + ' ' + item.model).toLowerCase();
                return text.includes(q);
            });

            if (this.sortBy === 'unit') {
                result.sort((a, b) => (a.unit_name || '').localeCompare(b.unit_name || '') || (a.name || '').localeCompare(b.name || ''));
            } else if (this.sortBy === 'code') {
                result.sort((a, b) => (a.patrimonial_code || a.internal_code || '').localeCompare(b.patrimonial_code || b.internal_code || ''));
            } else if (this.sortBy === 'status') {
                result.sort((a, b) => (a.status === 'atendido' ? 1 : -1) - (b.status === 'atendido' ? 1 : -1));
            } else if (this.sortBy === 'name') {
                result.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            }

            return result;
        },
        setCategoryFilter(key) {
            this.typeFilter = key;
            this.page = 1;
        },
        get totalPages() {
            return Math.ceil(this.filteredAssets.length / this.perPage) || 1;
        },
        get pagedAssets() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredAssets.slice(start, start + this.perPage);
        },
        setFilter(key, val) {
            this[key] = val;
            this.page = 1;
        }
    }">

        {{-- 1. Encabezado de Navegación y Acciones --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-5 border-b border-zinc-200/80 mb-5">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-zinc-400 mb-1.5">
                    <a href="{{ route('campaigns.index') }}" class="hover:text-zinc-700 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Campañas</span>
                    </a>
                    <span>/</span>
                    <span class="font-mono text-zinc-600 font-medium">{{ $campaign->code }}</span>
                </nav>

                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ $campaign->name }}</h1>

                    {{-- Status Switcher / Pill Interactivo --}}
                    <div x-data="{ statusMenu: false }" class="relative">
                        <button @click="statusMenu = !statusMenu"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-800 transition-colors cursor-pointer">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} {{ $isActive ? 'animate-pulse' : '' }}"></span>
                            <span>{{ $statusLabel }}</span>
                            <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="statusMenu" @click.outside="statusMenu = false" x-cloak
                             class="absolute left-0 top-full mt-1.5 w-48 bg-white rounded-xl shadow-xl border border-zinc-200 py-1.5 z-50 text-xs">
                            <p class="px-3 py-1 text-[10px] font-semibold text-zinc-400 uppercase tracking-wider">Cambiar Estado</p>

                            @if ($campaign->status->value === 'pendiente_aprobacion')
                                @can('campaign.approve')
                                <form method="POST" action="{{ route('campaigns.status', $campaign) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="en_curso">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-emerald-600 hover:bg-emerald-50 font-medium">
                                        ✓ Aprobar y Activar
                                    </button>
                                </form>
                                @endcan
                            @elseif ($campaign->status->value === 'planificada')
                                @can('campaign.edit')
                                <form method="POST" action="{{ route('campaigns.status', $campaign) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="en_curso">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-zinc-900 hover:bg-zinc-50 font-medium">
                                        ▶ Iniciar Campaña
                                    </button>
                                </form>
                                @endcan
                            @elseif ($campaign->status->value === 'en_curso')
                                @can('campaign.edit')
                                <form method="POST" action="{{ route('campaigns.status', $campaign) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="pausada">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-amber-600 hover:bg-amber-50">
                                        ⏸ Pausar Campaña
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('campaigns.status', $campaign) }}" onsubmit="return confirm('¿Marcar campaña como completada?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="completada">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-zinc-700 hover:bg-zinc-50">
                                        ✓ Completar Campaña
                                    </button>
                                </form>
                                @endcan
                            @elseif ($campaign->status->value === 'pausada')
                                @can('campaign.edit')
                                <form method="POST" action="{{ route('campaigns.status', $campaign) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="en_curso">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-zinc-900 hover:bg-zinc-50 font-medium">
                                        ▶ Reanudar Campaña
                                    </button>
                                </form>
                                @endcan
                            @elseif ($campaign->status->value === 'completada')
                                @can('campaign.edit')
                                <form method="POST" action="{{ route('campaigns.status', $campaign) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="en_curso">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-zinc-900 hover:bg-zinc-50 font-medium">
                                        ↺ Reabrir Campaña
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botones de Acción en Cabecera --}}
            <div class="flex items-center gap-2 flex-wrap">
                @if ($isActive)
                <button @click="quickAssetModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Descubrir Activo</span>
                </button>
                @endif

                <button @click="actaModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors shadow-xs">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <span>Acta PDF</span>
                </button>

                @can('campaign.edit')
                <button @click="bulkUnitModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors shadow-xs">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                    <span>Cargar Oficina</span>
                </button>

                <a href="{{ route('campaigns.edit', $campaign) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-xs font-medium text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100 transition-colors" title="Editar Parámetros">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                </a>
                @endcan
            </div>
        </div>

        {{-- 2. Tira Minimalista de Métricas --}}
        <div class="mb-4 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-5 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <span class="text-zinc-400 font-medium text-[11px]">Avance</span>
                    <span class="font-mono font-bold text-zinc-900 text-sm">{{ $progress }}%</span>
                    <div class="w-24 h-1.5 bg-zinc-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                    </div>
                    <span class="font-mono text-[11px] text-zinc-400">({{ $atendidos }}/{{ $totalAssets }})</span>
                </div>

                <div class="h-3.5 w-px bg-zinc-200 hidden sm:block"></div>

                <div class="flex items-center gap-4">
                    <div>
                        <span class="text-zinc-400">Pendientes:</span>
                        <span class="font-mono font-semibold text-zinc-900 ml-1">{{ $pendientes }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400">Atendidos:</span>
                        <span class="font-mono font-semibold text-emerald-600 ml-1">{{ $atendidos }}</span>
                    </div>
                </div>
            </div>

            <div class="text-[11px] font-mono text-zinc-400">
                {{ $campaign->start_date?->format('d/m/Y') ?? '—' }} → {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}
            </div>
        </div>

        {{-- 3. Navegación por Categorías (Tabs Ultra-Limpios) --}}
        <div class="flex items-center gap-1 border-b border-zinc-200/80 mb-3 overflow-x-auto pb-px scrollbar-none text-xs">
            {{-- Tab Todos --}}
            <button type="button"
                    @click="setCategoryFilter('all')"
                    :class="typeFilter === 'all'
                        ? 'border-zinc-900 text-zinc-900 font-semibold'
                        : 'border-transparent text-zinc-500 hover:text-zinc-800 hover:border-zinc-300'"
                    class="px-3 py-2 border-b-2 transition-colors flex items-center gap-1.5 cursor-pointer shrink-0">
                <span>Todos</span>
                <span class="font-mono text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="typeFilter === 'all' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600'"
                      x-text="assets.length"></span>
            </button>

            {{-- Tab por cada categoría presente (PCs, Monitores, Impresoras, etc.) --}}
            <template x-for="cat in categories" :key="cat.key">
                <button type="button"
                        @click="setCategoryFilter(cat.key)"
                        :class="typeFilter === cat.key
                            ? 'border-zinc-900 text-zinc-900 font-semibold'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 hover:border-zinc-300'"
                        class="px-3 py-2 border-b-2 transition-colors flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap">
                    <span x-text="cat.short"></span>
                    <span class="font-mono text-[10px] px-1.5 py-0.2 rounded-full"
                          :class="typeFilter === cat.key ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600'"
                          x-text="cat.total"></span>
                </button>
            </template>
        </div>

        {{-- 4. Controles y Filtros Ultra-Simples --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3 text-xs">
            {{-- Estados: Todos / Pendientes / Atendidos --}}
            <div class="inline-flex items-center p-0.5 bg-zinc-100 rounded-lg text-xs shrink-0 self-start">
                <button @click="setFilter('statusFilter', 'all')"
                        :class="statusFilter === 'all' ? 'bg-white text-zinc-900 font-medium shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer">
                    Todos
                </button>
                <button @click="setFilter('statusFilter', 'pending')"
                        :class="statusFilter === 'pending' ? 'bg-white text-zinc-900 font-medium shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer">
                    Pendientes ({{ $pendientes }})
                </button>
                <button @click="setFilter('statusFilter', 'attended')"
                        :class="statusFilter === 'attended' ? 'bg-white text-zinc-900 font-medium shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer">
                    Atendidos ({{ $atendidos }})
                </button>
            </div>

            {{-- Filtro de Dependencia y Buscador Rápido --}}
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select x-model="unitFilter" @change="page = 1"
                        class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-900 max-w-[170px] truncate cursor-pointer">
                    <option value="all">Todas las oficinas</option>
                    @foreach ($campaignUnits as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>

                <div class="relative flex-1 sm:w-56">
                    <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @input="page = 1" placeholder="Buscar..."
                           class="w-full pl-8 pr-6 py-1.5 bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    <button type="button" x-show="search.length > 0" @click="search = ''; page = 1" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs">
                        ✕
                    </button>
                </div>
            </div>
        </div>

                {{-- 5. Tabla Única, Nítida y Minimalista --}}
        <div class="bg-white rounded-xl border border-zinc-200/80 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200/80 bg-zinc-50/70 text-[11px] font-medium text-zinc-500 uppercase tracking-wider">
                            <th class="py-2.5 px-3.5">Sticker / Código</th>
                            <th class="py-2.5 px-3.5">Equipo</th>
                            <th class="py-2.5 px-3.5">Oficina</th>
                            <th class="py-2.5 px-3.5">Custodio</th>
                            <th class="py-2.5 px-3.5">Estado</th>
                            <th class="py-2.5 px-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <template x-for="item in pagedAssets" :key="item.id">
                            <tr class="hover:bg-zinc-50/60 transition-colors">
                                {{-- 1. Sticker / Código --}}
                                <td class="py-2.5 px-3.5 whitespace-nowrap">
                                    <template x-if="item.patrimonial_code">
                                        <span class="font-mono text-xs font-semibold text-zinc-900" x-text="item.patrimonial_code"></span>
                                    </template>
                                    <template x-if="!item.patrimonial_code">
                                        <span class="font-mono text-[11px] text-zinc-400" x-text="item.internal_code || '—'"></span>
                                    </template>
                                </td>

                                {{-- 2. Equipo (Nombre + tipo si está en 'Todos') --}}
                                <td class="py-2.5 px-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-medium text-zinc-900" x-text="item.name"></span>
                                        <template x-if="typeFilter === 'all'">
                                            <span class="text-[10px] font-mono text-zinc-400 px-1 py-0.2 bg-zinc-100 rounded" x-text="item.type_label"></span>
                                        </template>
                                    </div>
                                    <template x-if="item.brand || item.model">
                                        <div class="text-[11px] text-zinc-400 truncate max-w-[220px]" x-text="(item.brand + ' ' + (item.model || '')).trim()"></div>
                                    </template>
                                </td>

                                {{-- 3. Oficina --}}
                                <td class="py-2.5 px-3.5">
                                    <span class="text-zinc-600 block truncate max-w-[190px]" :title="item.unit_name" x-text="item.unit_name"></span>
                                </td>

                                {{-- 4. Custodio --}}
                                <td class="py-2.5 px-3.5">
                                    <span class="text-zinc-600 block truncate max-w-[170px]" :title="item.responsible_name" x-text="item.responsible_name"></span>
                                </td>

                                {{-- 5. Estado --}}
                                <td class="py-2.5 px-3.5 whitespace-nowrap">
                                    <template x-if="item.status === 'atendido'">
                                        <span class="inline-flex items-center gap-1.5 text-zinc-900 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Atendido</span>
                                            <span class="text-[10px] text-zinc-400 font-mono" x-text="item.attended_date ? '· ' + item.attended_date : ''"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.status !== 'atendido'">
                                        <span class="inline-flex items-center gap-1.5 text-zinc-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span>
                                            <span>Pendiente</span>
                                        </span>
                                    </template>
                                </td>

                                {{-- 6. Acción --}}
                                <td class="py-2.5 px-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($isActive)
                                        <button
                                            @click="openAttendModal(item)"
                                            type="button"
                                            :class="item.status === 'atendido' ? 'text-zinc-600 hover:bg-zinc-100' : 'bg-zinc-900 text-white hover:bg-zinc-800 shadow-2xs'"
                                            class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors cursor-pointer"
                                            x-text="item.status === 'atendido' ? 'Editar' : 'Atender'">
                                        </button>
                                        @endif

                                        @can('campaign.edit')
                                        <form method="POST" :action="'{{ url('campaigns/' . $campaign->id . '/assets') }}/' + item.asset_id"
                                              onsubmit="return confirm('¿Quitar equipo de la campaña?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 rounded text-zinc-300 hover:text-rose-600 transition-colors cursor-pointer" title="Quitar de campaña">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredAssets.length === 0">
                            <td colspan="6" class="py-12 text-center text-zinc-400">
                                No se encontraron equipos.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Paginación Minimalista --}}
            <div class="px-3.5 py-2.5 border-t border-zinc-100 flex items-center justify-between text-xs text-zinc-500 bg-zinc-50/40">
                <div>
                    <span class="font-medium text-zinc-800" x-text="filteredAssets.length"></span> equipos
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="page--" :disabled="page <= 1"
                            :class="page <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-zinc-200 cursor-pointer text-zinc-700'"
                            class="p-1 rounded border border-zinc-200 bg-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="font-mono text-[11px] text-zinc-600">
                        <span x-text="page"></span> / <span x-text="totalPages || 1"></span>
                    </span>
                    <button type="button" @click="page++" :disabled="page >= totalPages"
                            :class="page >= totalPages ? 'opacity-30 cursor-not-allowed' : 'hover:bg-zinc-200 cursor-pointer text-zinc-700'"
                            class="p-1 rounded border border-zinc-200 bg-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────────────────
             MODAL 1: REGISTRAR ATENCIÓN EN CAMPO (CHECKLIST FÍSICO)
             ───────────────────────────────────────────────────────────── --}}
        <div x-show="attendModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-xs"
             @keydown.escape.window="attendModalOpen = false">
            <div @click.outside="attendModalOpen = false"
                 class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-zinc-200 overflow-hidden text-xs">

                <div class="px-5 py-4 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/60">
                    <div>
                        <h3 class="font-bold text-zinc-900 text-sm">Registro de Atención en Campo</h3>
                        <p class="text-zinc-500 font-mono text-[11px]" x-text="activeCa.name + ' · ' + activeCa.internalCode"></p>
                    </div>
                    <button @click="attendModalOpen = false" class="text-zinc-400 hover:text-zinc-700 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('campaigns/' . $campaign->id . '/assets') }}/' + activeCa.id + '/attend'" method="POST" class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                    @csrf

                    {{-- 1. Verificación Física y Sticker --}}
                    <div class="space-y-3">
                        <p class="font-semibold text-zinc-900 uppercase tracking-wider text-[10px]">1. Datos Físicos del Equipo</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-zinc-700 mb-1">Sticker Patrimonial</label>
                                <input type="text" name="patrimonial_code" x-model="activeCa.patrimonialCode" placeholder="Ej. 740880370113"
                                       class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono font-semibold">
                            </div>
                            <div>
                                <label class="block font-medium text-zinc-700 mb-1">Condición Física</label>
                                <select name="asset_condition_after" x-model="activeCa.condition"
                                        class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                    <option value="bueno">Bueno / Operativo</option>
                                    <option value="regular">Regular</option>
                                    <option value="malo">Malo / Requiere Baja</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-zinc-700 mb-1">Oficina Actual</label>
                                <select name="organizational_unit_id" x-model="activeCa.unitId"
                                        class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900 truncate">
                                    <option value="">Sin oficina asignada</option>
                                    @foreach ($organizationalUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-zinc-700 mb-1">Responsable / Custodio</label>
                                <select name="responsible_employee_id" x-model="activeCa.responsibleId"
                                        class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900 truncate">
                                    <option value="">Sin custodio asignado</option>
                                    @foreach ($technicians as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Checklist Técnico de Campo --}}
                    <div class="pt-2 border-t border-zinc-100 space-y-2">
                        <p class="font-semibold text-zinc-900 uppercase tracking-wider text-[10px]">2. Actividades de Mantenimiento Ejecutadas</p>

                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2.5 p-2 rounded-lg border border-zinc-200/80 hover:bg-zinc-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="checklist_tasks[]" value="sopleteado_limpieza"
                                       :checked="activeCa.tasks.includes('sopleteado_limpieza')"
                                       @change="toggleTask('sopleteado_limpieza')"
                                       class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                <div>
                                    <span class="font-medium text-zinc-800 block">Sopleteado y limpieza física</span>
                                    <span class="text-[10px] text-zinc-400">Eliminación de polvo de ventiladores, fuente y carcasa.</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2 rounded-lg border border-zinc-200/80 hover:bg-zinc-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="checklist_tasks[]" value="temporales_optimizacion"
                                       :checked="activeCa.tasks.includes('temporales_optimizacion')"
                                       @change="toggleTask('temporales_optimizacion')"
                                       class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                <div>
                                    <span class="font-medium text-zinc-800 block">Limpieza de temporales y optimización</span>
                                    <span class="text-[10px] text-zinc-400">Liberación de espacio en disco y desfragmentación/revisión.</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2 rounded-lg border border-zinc-200/80 hover:bg-zinc-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="checklist_tasks[]" value="licenciamiento_software"
                                       :checked="activeCa.tasks.includes('licenciamiento_software')"
                                       @change="toggleTask('licenciamiento_software')"
                                       class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                <div>
                                    <span class="font-medium text-zinc-800 block">Verificación de licencias de software</span>
                                    <span class="text-[10px] text-zinc-400">Validación de Office / SO municipal y antivirus institucional.</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2 rounded-lg border border-zinc-200/80 hover:bg-zinc-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="checklist_tasks[]" value="cables_red_perifericos"
                                       :checked="activeCa.tasks.includes('cables_red_perifericos')"
                                       @change="toggleTask('cables_red_perifericos')"
                                       class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                <div>
                                    <span class="font-medium text-zinc-800 block">Inspección de cables y periféricos</span>
                                    <span class="text-[10px] text-zinc-400">Verificación de patch cord, tomacorrientes y estabilizador.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 3. Responsable y Observaciones --}}
                    <div class="pt-2 border-t border-zinc-100 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-zinc-700 mb-1">Técnico que Atendió</label>
                                <select name="attended_by_employee_id" x-model="activeCa.technicianId"
                                        class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-zinc-700 mb-1">Fecha de Atención</label>
                                <input type="date" name="attended_date" x-model="activeCa.date" required
                                       class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Observaciones / Hallazgos</label>
                            <textarea name="notes" x-model="activeCa.notes" rows="2" placeholder="Notas sobre el estado físico, accesorios o incidencias..."
                                      class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900"></textarea>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-zinc-100 flex justify-end gap-2">
                        <button type="button" @click="attendModalOpen = false"
                                class="px-3.5 py-1.5 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white font-medium hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                            Guardar Atención
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────────────────
             MODAL 2: DESCUBRIR ACTIVO EN OFICINA (ALTA RÁPIDA)
             ───────────────────────────────────────────────────────────── --}}
        <div x-show="quickAssetModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-xs"
             @keydown.escape.window="quickAssetModalOpen = false">
            <div @click.outside="quickAssetModalOpen = false"
                 class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-zinc-200 overflow-hidden text-xs">

                <div class="px-5 py-4 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/60">
                    <div>
                        <h3 class="font-bold text-zinc-900 text-sm">Descubrir Activo en Oficina</h3>
                        <p class="text-zinc-500 text-[11px]">Registrar un equipo hallado en campo e incorporarlo a la campaña.</p>
                    </div>
                    <button @click="quickAssetModalOpen = false" class="text-zinc-400 hover:text-zinc-700 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('campaigns.assets.quick-register', $campaign) }}" method="POST" class="p-5 space-y-3.5 max-h-[80vh] overflow-y-auto">
                    @csrf

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Tipo de Hardware *</label>
                            <select name="asset_type" required
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                @foreach ($assetTypes as $type)
                                    <option value="{{ $type->value }}" {{ $type->value === 'monitor' ? 'selected' : '' }}>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Sticker Patrimonial</label>
                            <input type="text" name="patrimonial_code" placeholder="Ej. 740880370999"
                                   class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono font-semibold">
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-zinc-700 mb-1">Nombre / Identificador del Equipo *</label>
                        <input type="text" name="name" required placeholder="Ej: Monitor Dell 24 pulgadas o Impresora EPSON L3150"
                               class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Marca</label>
                            <input type="text" name="brand" placeholder="Ej: Dell, HP, Epson"
                                   class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Modelo / Serie</label>
                            <input type="text" name="model" placeholder="Ej: P2419H o S/N..."
                                   class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Oficina donde se halla</label>
                            <select name="organizational_unit_id"
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900 truncate">
                                <option value="">Seleccionar oficina...</option>
                                @foreach ($organizationalUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Custodio / Responsable</label>
                            <select name="responsible_employee_id"
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900 truncate">
                                <option value="">Seleccionar custodio...</option>
                                @foreach ($technicians as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Marcar atendido inmediatamente --}}
                    <div x-data="{ markAttended: true }" class="pt-2 border-t border-zinc-100">
                        <label class="flex items-center gap-2 mb-2 font-medium text-zinc-800 cursor-pointer">
                            <input type="checkbox" name="mark_attended" value="1" x-model="markAttended"
                                   class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                            <span>Marcar como atendido preventivamente ahora mismo</span>
                        </label>

                        <div x-show="markAttended" class="space-y-1.5 pl-6 border-l-2 border-zinc-200 mt-2">
                            <label class="flex items-center gap-2 text-zinc-600">
                                <input type="checkbox" name="checklist_tasks[]" value="sopleteado_limpieza" checked class="rounded border-zinc-300 text-zinc-900">
                                <span>Sopleteado / Limpieza física</span>
                            </label>
                            <label class="flex items-center gap-2 text-zinc-600">
                                <input type="checkbox" name="checklist_tasks[]" value="temporales_optimizacion" checked class="rounded border-zinc-300 text-zinc-900">
                                <span>Optimización / Pruebas de funcionamiento</span>
                            </label>
                            <label class="flex items-center gap-2 text-zinc-600">
                                <input type="checkbox" name="checklist_tasks[]" value="licenciamiento_software" class="rounded border-zinc-300 text-zinc-900">
                                <span>Revisión de conexiones y voltaje</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-zinc-100 flex justify-end gap-2">
                        <button type="button" @click="quickAssetModalOpen = false"
                                class="px-3.5 py-1.5 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white font-medium hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                            Registrar en Campaña
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────────────────
             MODAL 3: CARGAR POR OFICINA (ASIGNACIÓN MASIVA)
             ───────────────────────────────────────────────────────────── --}}
        <div x-show="bulkUnitModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-xs"
             @keydown.escape.window="bulkUnitModalOpen = false">
            <div @click.outside="bulkUnitModalOpen = false"
                 class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-zinc-200 overflow-hidden text-xs">

                <div class="px-5 py-4 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/60">
                    <div>
                        <h3 class="font-bold text-zinc-900 text-sm">Cargar Activos por Oficina</h3>
                        <p class="text-zinc-500 text-[11px]">Incorpora todos los equipos de una dependencia a la campaña.</p>
                    </div>
                    <button @click="bulkUnitModalOpen = false" class="text-zinc-400 hover:text-zinc-700 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('campaigns.assets.bulk-unit', $campaign) }}" method="POST" class="p-5 space-y-4">
                    @csrf

                    <div>
                        <label class="block font-medium text-zinc-700 mb-1">Unidad Organizacional / Oficina *</label>
                        <select name="unit_id" required
                                class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            <option value="">Seleccionar oficina...</option>
                            @foreach ($organizationalUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-zinc-700 mb-1">Técnico Asignado (Opcional)</label>
                        <select name="assigned_technician_id"
                                class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            <option value="">Sin técnico asignado</option>
                            @foreach ($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-zinc-700 mb-1">Fecha Programada (Opcional)</label>
                        <input type="date" name="scheduled_date" value="{{ now()->toDateString() }}"
                               class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    </div>

                    <div class="pt-3 border-t border-zinc-100 flex justify-end gap-2">
                        <button type="button" @click="bulkUnitModalOpen = false"
                                class="px-3.5 py-1.5 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white font-medium hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                            Cargar Equipos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────────────────
             MODAL 4: DESCARGAR ACTA DE MANTENIMIENTO OFICIAL (PDF)
             ───────────────────────────────────────────────────────────── --}}
        <div x-show="actaModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-xs"
             @keydown.escape.window="actaModalOpen = false">
            <div @click.outside="actaModalOpen = false"
                 class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-zinc-200 overflow-hidden text-xs">

                <div class="px-5 py-4 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/60">
                    <div>
                        <h3 class="font-bold text-zinc-900 text-sm">Descargar Acta de Mantenimiento</h3>
                        <p class="text-zinc-500 text-[11px]">Generación de documento oficial con firmas institucionales.</p>
                    </div>
                    <button @click="actaModalOpen = false" class="text-zinc-400 hover:text-zinc-700 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('campaigns.generate-acta', $campaign) }}" method="POST" target="_blank" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block font-medium text-zinc-700 mb-1">Dependencia / Oficina *</label>
                        <select name="unit_id" required
                                class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            <option value="">Seleccionar oficina...</option>
                            @foreach ($campaignUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-zinc-400 mt-1">Puedes emitir el acta para una oficina individual al concluir su visita.</p>
                    </div>

                    <div class="pt-3 border-t border-zinc-100 flex justify-end gap-2">
                        <button type="button" @click="actaModalOpen = false"
                                class="px-3.5 py-1.5 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors cursor-pointer">
                            Cerrar
                        </button>
                        <button type="submit" @click="actaModalOpen = false"
                                class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white font-medium hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Generar PDF</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</x-layouts.app>
