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
        };

        // Extract unique offices and types for client filters
        $campaignUnits = $campaign->campaignAssets
            ->map(fn($ca) => $ca->asset->organizationalUnit)
            ->filter()
            ->unique('id')
            ->sortBy('name');

        $campaignTypes = $campaign->campaignAssets
            ->map(fn($ca) => $ca->asset->asset_type?->value)
            ->filter()
            ->unique()
            ->values();

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
        statusFilter: 'all', // 'all', 'pending', 'attended'
        typeFilter: 'all',
        unitFilter: 'all',
        page: 1,
        perPage: 50,
        assets: @js($assetsData),
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
            return this.assets.filter(item => {
                if (this.statusFilter === 'pending' && item.status === 'atendido') return false;
                if (this.statusFilter === 'attended' && item.status !== 'atendido') return false;
                if (this.typeFilter !== 'all' && item.type !== this.typeFilter) return false;
                if (this.unitFilter !== 'all' && String(item.unit_id) !== String(this.unitFilter)) return false;

                if (!q) return true;
                const text = (item.patrimonial_code + ' ' + item.internal_code + ' ' + item.name + ' ' + item.unit_name + ' ' + item.responsible_name + ' ' + item.technician).toLowerCase();
                return text.includes(q);
            });
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

        {{-- 2. Tira Ejecutiva de Métricas (1 sola fila horizontal, alta densidad, borde limpio) --}}
        <div class="mb-6 bg-white rounded-xl border border-zinc-200/90 shadow-xs divide-y sm:divide-y-0 sm:divide-x divide-zinc-100 grid grid-cols-2 lg:grid-cols-5 text-xs">
            {{-- Métrica 1: Avance Global --}}
            <div class="p-4 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-baseline mb-1">
                        <span class="text-zinc-400 text-[11px] font-medium">Avance Global</span>
                        <span class="font-mono font-bold text-zinc-900 text-sm">{{ $progress }}%</span>
                    </div>
                    <div class="h-1.5 w-full bg-zinc-100 rounded-full overflow-hidden">
                        <div class="h-full bg-zinc-900 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
                <span class="font-mono text-[10px] text-zinc-400 mt-2 block">{{ $atendidos }} de {{ $totalAssets }} atendidos</span>
            </div>

            {{-- Métrica 2: Canal Agente SIGAT --}}
            <div class="p-4 flex flex-col justify-between">
                <div>
                    <span class="text-zinc-400 text-[11px] font-medium block mb-1">Agente SIGAT</span>
                    <span class="font-mono font-bold text-zinc-900 text-lg block">{{ $atendidosAgente }}</span>
                </div>
                <span class="text-[10px] text-zinc-400 block mt-1">Detección autónoma</span>
            </div>

            {{-- Métrica 3: Canal Manual en Campo --}}
            <div class="p-4 flex flex-col justify-between">
                <div>
                    <span class="text-zinc-400 text-[11px] font-medium block mb-1">Manual / Campo</span>
                    <span class="font-mono font-bold text-zinc-900 text-lg block">{{ $atendidosManual }}</span>
                </div>
                <span class="text-[10px] text-zinc-400 block mt-1">Monitores, impresoras, etc.</span>
            </div>

            {{-- Métrica 4: Pendientes --}}
            <div class="p-4 flex flex-col justify-between">
                <div>
                    <span class="text-zinc-400 text-[11px] font-medium block mb-1">Por Atender</span>
                    <span class="font-mono font-bold text-zinc-900 text-lg block">{{ $pendientes }}</span>
                </div>
                <span class="text-[10px] text-zinc-400 block mt-1">Equipos en espera</span>
            </div>

            {{-- Métrica 5: Vigencia & Coordinación --}}
            <div class="p-4 col-span-2 lg:col-span-1 flex flex-col justify-between">
                <div>
                    <span class="text-zinc-400 text-[11px] font-medium block mb-1">Cronograma</span>
                    <span class="font-mono text-zinc-800 text-xs block font-medium">
                        {{ $campaign->start_date?->format('d/m/Y') ?? '—' }} <span class="text-zinc-300">→</span> {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
                <span class="text-[10px] text-zinc-400 block truncate mt-1">
                    {{ $campaign->coordinator?->full_name ?? 'Sede Central MDSJ' }}
                </span>
            </div>
        </div>

        {{-- 3. Barra de Control del Listado de Activos --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-3">
            {{-- Segmentos de Estado --}}
            <div class="flex items-center gap-1 text-xs">
                <button @click="setFilter('statusFilter', 'all')"
                        :class="statusFilter === 'all' ? 'bg-zinc-900 text-white font-medium' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">
                    Todos ({{ $totalAssets }})
                </button>
                <button @click="setFilter('statusFilter', 'pending')"
                        :class="statusFilter === 'pending' ? 'bg-zinc-900 text-white font-medium' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">
                    Pendientes ({{ $pendientes }})
                </button>
                <button @click="setFilter('statusFilter', 'attended')"
                        :class="statusFilter === 'attended' ? 'bg-zinc-900 text-white font-medium' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">
                    Atendidos ({{ $atendidos }})
                </button>
            </div>

            {{-- Filtros Select y Buscador --}}
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                {{-- Filtro Tipo de Hardware --}}
                <select x-model="typeFilter" @change="page = 1"
                        class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    <option value="all">Todos los tipos</option>
                    @foreach ($campaignTypes as $cType)
                        <option value="{{ $cType }}">{{ ucfirst($cType) }}</option>
                    @endforeach
                </select>

                {{-- Filtro Oficina --}}
                <select x-model="unitFilter" @change="page = 1"
                        class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-900 max-w-[160px] truncate">
                    <option value="all">Todas las oficinas</option>
                    @foreach ($campaignUnits as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>

                {{-- Buscador Instantáneo --}}
                <div class="relative w-full sm:w-64">
                    <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @input="page = 1" placeholder="Sticker, equipo u oficina..."
                           class="w-full pl-8 pr-3 py-1.5 bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                </div>
            </div>
        </div>

        {{-- 4. Grilla de Activos Ultraminimalista (Ancho completo) --}}
        <div class="bg-white rounded-xl border border-zinc-200/90 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200/80 bg-zinc-50/75 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Sticker / Código</th>
                            <th class="py-3 px-4">Activo / Hardware</th>
                            <th class="py-3 px-4">Oficina</th>
                            <th class="py-3 px-4">Custodio</th>
                            <th class="py-3 px-4">Canal</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4">Técnico</th>
                            <th class="py-3 px-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <template x-for="item in pagedAssets" :key="item.id">
                            <tr class="hover:bg-zinc-50/75 transition-colors">
                                {{-- 1. Sticker Patrimonial --}}
                                <td class="py-3 px-4">
                                    <template x-if="item.patrimonial_code">
                                        <span class="font-mono text-xs font-semibold text-zinc-900 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200" x-text="item.patrimonial_code"></span>
                                    </template>
                                    <template x-if="!item.patrimonial_code">
                                        <span class="font-mono text-[11px] text-zinc-400 italic">Sin sticker</span>
                                    </template>
                                </td>

                                {{-- 2. Activo y Hardware (Con símbolo preciso) --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <template x-if="item.type === 'impresora'">
                                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 6.75h10.5V3.75H6.75v3zm10.5 4.5h.008v.008h-.008v-.008zM4.5 9h15a1.5 1.5 0 011.5 1.5v6a1.5 1.5 0 01-1.5 1.5H4.5A1.5 1.5 0 013 16.5v-6A1.5 1.5 0 014.5 9zm2.25 5.25h10.5v6H6.75v-6z"/></svg>
                                        </template>
                                        <template x-if="item.type === 'monitor'">
                                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 5.25h16.5A1.5 1.5 0 0121.75 6.75v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5zm6 12v3m4.5-3v3m-6.75 0h9"/></svg>
                                        </template>
                                        <template x-if="item.type === 'laptop'">
                                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l1.5-9A2.25 2.25 0 016 4.5h12a2.25 2.25 0 012.25 2.25l1.5 9m-19.5 0h19.5m-19.5 0A2.25 2.25 0 004.5 18h15a2.25 2.25 0 002.25-2.25m-19.5 0v.75A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25v-.75"/></svg>
                                        </template>
                                        <template x-if="item.type === 'escaner'">
                                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 17.25h16.5m-16.5 0a2.25 2.25 0 002.25 2.25h12a2.25 2.25 0 002.25-2.25m-16.5 0V9.75m16.5 7.5V9.75m0 0a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25v7.5m16.5-7.5H3.75"/></svg>
                                        </template>
                                        <template x-if="item.type !== 'impresora' && item.type !== 'monitor' && item.type !== 'laptop' && item.type !== 'escaner'">
                                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/></svg>
                                        </template>

                                        <div class="min-w-0">
                                            <a :href="'{{ url('assets') }}/' + item.asset_id"
                                               class="font-medium text-zinc-900 hover:text-zinc-600 transition-colors block truncate max-w-[220px]"
                                               x-text="item.name"></a>
                                            <span class="font-mono text-[10px] text-zinc-400 block" x-text="item.internal_code"></span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. Oficina --}}
                                <td class="py-3 px-4">
                                    <span class="text-zinc-700 block truncate max-w-[190px]" :title="item.unit_name" x-text="item.unit_name"></span>
                                </td>

                                {{-- 4. Custodio --}}
                                <td class="py-3 px-4">
                                    <span class="text-zinc-600 block truncate max-w-[170px]" :title="item.responsible_name" x-text="item.responsible_name"></span>
                                </td>

                                {{-- 5. Canal de Atención --}}
                                <td class="py-3 px-4">
                                    <template x-if="item.status === 'atendido'">
                                        <span class="font-mono text-[11px]"
                                              :class="item.attended_type === 'agente' ? 'text-zinc-900 font-medium' : 'text-zinc-700'"
                                              x-text="item.attended_type === 'agente' ? 'Agente' : 'Manual'"></span>
                                    </template>
                                    <template x-if="item.status !== 'atendido'">
                                        <span class="text-zinc-300 font-mono text-[11px]">—</span>
                                    </template>
                                </td>

                                {{-- 6. Estado --}}
                                <td class="py-3 px-4">
                                    <template x-if="item.status === 'atendido'">
                                        <span class="inline-flex items-center gap-1.5 text-zinc-900 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Atendido</span>
                                            <span class="font-mono text-[10px] text-zinc-400" x-text="item.attended_date ? '· ' + item.attended_date : ''"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.status !== 'atendido'">
                                        <span class="inline-flex items-center gap-1.5 text-zinc-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span>
                                            <span>Pendiente</span>
                                        </span>
                                    </template>
                                </td>

                                {{-- 7. Técnico --}}
                                <td class="py-3 px-4">
                                    <span class="text-zinc-600 block truncate max-w-[140px]" :title="item.technician" x-text="item.technician"></span>
                                </td>

                                {{-- 8. Acción Directa --}}
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($isActive)
                                        <button
                                            @click="openAttendModal(item)"
                                            type="button"
                                            :class="item.status === 'atendido' ? 'text-zinc-600 hover:bg-zinc-100' : 'bg-zinc-900 text-white hover:bg-zinc-800 shadow-xs'"
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
                            <td colspan="8" class="py-16 text-center text-zinc-400">
                                No se encontraron activos con los filtros seleccionados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Controles de Paginación Reactiva --}}
            <div class="px-4 py-3 border-t border-zinc-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-zinc-500 bg-zinc-50/50">
                <div class="flex items-center gap-2">
                    <span>Mostrando <span class="font-semibold text-zinc-800" x-text="filteredAssets.length === 0 ? 0 : ((page - 1) * perPage + 1)"></span> a <span class="font-semibold text-zinc-800" x-text="Math.min(page * perPage, filteredAssets.length)"></span> de <span class="font-semibold text-zinc-800" x-text="filteredAssets.length"></span> activos</span>
                    <span x-show="filteredAssets.length !== assets.length" class="text-zinc-400 font-mono text-[11px]">
                        (filtrados de <span x-text="assets.length"></span> totales)
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-zinc-400">Por página:</span>
                        <select x-model.number="perPage" @change="page = 1"
                                class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer">
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="250">250</option>
                            <option :value="10000">Todos</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1">
                        <button type="button" @click="page--" :disabled="page <= 1"
                                :class="page <= 1 ? 'opacity-40 cursor-not-allowed text-zinc-300' : 'text-zinc-700 hover:bg-zinc-200 cursor-pointer'"
                                class="p-1.5 rounded-lg border border-zinc-200 bg-white transition-colors" title="Página anterior">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="px-2 font-mono text-[11px]">
                            Pág. <span class="font-semibold text-zinc-800" x-text="page"></span> de <span class="font-semibold text-zinc-800" x-text="totalPages || 1"></span>
                        </span>
                        <button type="button" @click="page++" :disabled="page >= totalPages"
                                :class="page >= totalPages ? 'opacity-40 cursor-not-allowed text-zinc-300' : 'text-zinc-700 hover:bg-zinc-200 cursor-pointer'"
                                class="p-1.5 rounded-lg border border-zinc-200 bg-white transition-colors" title="Página siguiente">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
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
