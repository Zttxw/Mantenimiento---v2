<x-layouts.app title="Campañas de Mantenimiento" moduleLabel="Mantenimiento" pageTitle="Campañas">

    @php
        $currentStatus = request('status', '');
    @endphp

    {{-- Encabezado Principal Ultraminimalista --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-zinc-200/80 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Mantenimiento Preventivo</span>
                <span class="text-zinc-300">/</span>
                <span class="text-[11px] font-medium text-zinc-500">Operaciones Masivas</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Campañas</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                    {{ $campaigns->total() ?? $campaigns->count() }}
                </span>
            </div>
            <p class="text-xs text-zinc-500 mt-1">Planificación y seguimiento de mantenimientos preventivos programados en sedes y dependencias.</p>
        </div>

        @can('campaign.create')
        <a href="{{ route('campaigns.create') }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm self-start sm:self-auto">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Nueva Campaña</span>
        </a>
        @endcan
    </div>

    {{-- Barra de Segmentos y Filtro Directo --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
        {{-- Pestañas de estado (Pills monocromáticas) --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
            <a href="{{ route('campaigns.index', array_merge(request()->except('status', 'page'), [])) }}"
               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ empty($currentStatus) ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                Todas
            </a>
            <a href="{{ route('campaigns.index', array_merge(request()->except('page'), ['status' => 'en_curso'])) }}"
               class="px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5 {{ $currentStatus === 'en_curso' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                En Curso
            </a>
            <a href="{{ route('campaigns.index', array_merge(request()->except('page'), ['status' => 'planificada'])) }}"
               class="px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5 {{ $currentStatus === 'planificada' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                Planificadas
            </a>
            <a href="{{ route('campaigns.index', array_merge(request()->except('page'), ['status' => 'pendiente_aprobacion'])) }}"
               class="px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5 {{ $currentStatus === 'pendiente_aprobacion' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                En Aprobación
            </a>
            <a href="{{ route('campaigns.index', array_merge(request()->except('page'), ['status' => 'completada'])) }}"
               class="px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5 {{ $currentStatus === 'completada' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span>
                Completadas
            </a>
        </div>

        {{-- Buscador compacto --}}
        <form method="GET" action="{{ route('campaigns.index') }}" class="flex items-center gap-2">
            @if (!empty($currentStatus))
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="relative w-full sm:w-64">
                <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar campaña..."
                       class="w-full pl-8 pr-3 py-1.5 bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            @if (request()->hasAny(['search', 'status']))
                <a href="{{ route('campaigns.index') }}" class="text-zinc-400 hover:text-zinc-700 text-xs transition-colors px-1" title="Limpiar filtro">
                    ✕
                </a>
            @endif
        </form>
    </div>

    {{-- Listado Ultraminimalista de Campañas --}}
    <div class="space-y-3">
        @forelse ($campaigns as $campaign)
        @php
            $totalAssets = $campaign->campaignAssets?->count() ?? 0;
            $atendidos = $campaign->campaignAssets?->where('status.value', 'atendido')->count() ?? 0;
            $progreso = $totalAssets > 0 ? round(($atendidos / $totalAssets) * 100) : 0;

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
        @endphp

        <div class="group bg-white rounded-xl border border-zinc-200/90 hover:border-zinc-400 transition-all duration-150 p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">

                {{-- Sección Izquierda: Identificador y Título --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-zinc-700">
                            <span class="w-2 h-2 rounded-full {{ $dotColor }} {{ $campaign->status->value === 'en_curso' ? 'animate-pulse' : '' }}"></span>
                            {{ $statusLabel }}
                        </span>
                        <span class="text-zinc-300">·</span>
                        <span class="font-mono text-xs text-zinc-500 font-medium">
                            {{ $campaign->code }}
                        </span>
                    </div>

                    <a href="{{ route('campaigns.show', $campaign) }}" class="text-base font-semibold text-zinc-900 group-hover:text-zinc-600 transition-colors block truncate">
                        {{ $campaign->name }}
                    </a>

                    @if ($campaign->objective)
                    <p class="text-xs text-zinc-500 mt-1 line-clamp-1">
                        {{ $campaign->objective }}
                    </p>
                    @endif
                </div>

                {{-- Sección Centro: Cronograma y Coordinador --}}
                <div class="flex items-center gap-6 text-xs text-zinc-500 flex-shrink-0">
                    <div>
                        <span class="text-[10px] uppercase font-semibold tracking-wider text-zinc-400 block mb-0.5">Vigencia</span>
                        <span class="font-mono text-zinc-800 text-xs">
                            {{ $campaign->start_date?->format('d/m/Y') ?? '—' }} <span class="text-zinc-300">→</span> {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}
                        </span>
                    </div>

                    <div class="hidden sm:block border-l border-zinc-100 pl-6">
                        <span class="text-[10px] uppercase font-semibold tracking-wider text-zinc-400 block mb-0.5">Coordinador</span>
                        <span class="text-zinc-800 truncate block max-w-[160px]">
                            {{ $campaign->coordinator?->full_name ?? 'Sin asignar' }}
                        </span>
                    </div>
                </div>

                {{-- Sección Derecha: Progreso y Acciones --}}
                <div class="flex items-center gap-6 flex-shrink-0 border-t lg:border-t-0 border-zinc-100 pt-3 lg:pt-0">
                    {{-- Barra de Progreso --}}
                    <div class="w-36">
                        <div class="flex justify-between items-center text-[11px] mb-1">
                            <span class="text-zinc-400">Avance</span>
                            <span class="font-mono font-semibold text-zinc-900">{{ $progreso }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-zinc-100 rounded-full overflow-hidden">
                            <div class="h-full bg-zinc-900 rounded-full transition-all duration-300" style="width: {{ $progreso }}%"></div>
                        </div>
                        <span class="font-mono text-[10px] text-zinc-400 mt-1 block">{{ $atendidos }} / {{ $totalAssets }} activos</span>
                    </div>

                    {{-- Botón Abrir --}}
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('campaigns.show', $campaign) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-800 transition-colors">
                            <span>Abrir</span>
                            <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        @can('campaign.edit')
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute right-0 top-full mt-1 w-32 bg-white rounded-lg shadow-lg border border-zinc-200 py-1 z-30 text-xs">
                                <a href="{{ route('campaigns.edit', $campaign) }}" class="block px-3 py-1.5 text-zinc-700 hover:bg-zinc-50">
                                    Editar
                                </a>
                                @can('campaign.delete')
                                <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('¿Eliminar campaña?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-rose-600 hover:bg-rose-50">
                                        Eliminar
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>

            </div>
        </div>
        @empty
        <div class="py-16 text-center bg-white rounded-xl border border-zinc-200">
            <p class="text-sm font-medium text-zinc-700">Sin campañas registradas</p>
            <p class="text-xs text-zinc-400 mt-1">Crea la primera campaña para programar intervenciones masivas.</p>
            @can('campaign.create')
            <div class="mt-4">
                <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Crear Campaña</span>
                </a>
            </div>
            @endcan
        </div>
        @endforelse
    </div>

    @if ($campaigns->hasPages())
    <div class="mt-4">
        {{ $campaigns->withQueryString()->links() }}
    </div>
    @endif

</x-layouts.app>
