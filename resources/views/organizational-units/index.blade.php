<x-layouts.app title="Estructura Organizacional" moduleLabel="Organización" pageTitle="Estructura Institucional">

<div x-data="orgGraph({{ json_encode($treeData, JSON_HEX_APOS | JSON_HEX_QUOT) }})"
     x-init="init()"
     class="flex flex-col h-[calc(100vh-95px)] overflow-hidden select-none">

    {{-- Encabezado Principal Compacto --}}
    <div class="flex items-center justify-between gap-4 pb-3 mb-2 border-b border-zinc-200/80 shrink-0">
        {{-- Título y Métricas Rápidas --}}
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold tracking-tight text-zinc-900">Oficinas y Dependencias</h1>
            <div class="flex items-center gap-2 text-xs font-mono text-zinc-500">
                <span class="px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-700 border border-zinc-200">
                    {{ $stats['total'] ?? $units->total() }} dependencias
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-emerald-600 font-medium">
                    {{ $stats['active'] ?? 0 }} activas
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-blue-600 font-medium">
                    {{ $stats['with_assets'] ?? 0 }} con equipos OTI
                </span>
            </div>
        </div>

        {{-- Switch de Vistas (Grafo Visual vs Tabla) y Acciones --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="inline-flex items-center p-0.5 rounded-lg bg-zinc-100 border border-zinc-200 text-xs">
                <button type="button"
                        @click="viewMode = 'graph'"
                        :class="viewMode === 'graph' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <span>🌐</span>
                    <span>Árbol (Grafo)</span>
                </button>
                <button type="button"
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <span>📋</span>
                    <span>Tabla</span>
                </button>
            </div>

            @can('org-unit.create')
            <a href="{{ route('organizational-units.create') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Oficina</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODO 1: GRAFO INTERACTIVO DE NODOS CIRCULARES CONECTADOS                  --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'graph'" class="flex-1 relative bg-zinc-900/5 rounded-2xl border border-zinc-200/90 overflow-hidden flex flex-col">

        {{-- Barra de Controles Superior Flotante --}}
        <div class="absolute top-3 left-3 right-3 z-30 flex items-center justify-between gap-3 pointer-events-none">
            {{-- Buscador en Vivo en el Grafo --}}
            <div class="pointer-events-auto relative w-72 sm:w-80 shadow-sm">
                <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text"
                       x-model="searchQuery"
                       @input="onSearch()"
                       placeholder="Buscar oficina en el grafo..."
                       class="w-full pl-9 pr-7 py-1.5 bg-white/95 backdrop-blur-md rounded-xl border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 shadow-xs">
                <button type="button"
                        x-show="searchQuery.length > 0"
                        @click="searchQuery = ''; onSearch()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs">
                    ✕
                </button>
            </div>

            {{-- Leyenda y Controles de Zoom / Vista --}}
            <div class="pointer-events-auto flex items-center gap-1.5 bg-white/95 backdrop-blur-md p-1 rounded-xl border border-zinc-200 shadow-xs text-xs">
                {{-- Botones de Zoom --}}
                <button type="button" @click="zoomIn()" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-zinc-100 text-zinc-700 font-bold" title="Acercar (+)">
                    +
                </button>
                <button type="button" @click="zoomOut()" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-zinc-100 text-zinc-700 font-bold" title="Alejar (-)">
                    &minus;
                </button>
                <button type="button" @click="resetView()" class="px-2 py-1 rounded-lg hover:bg-zinc-100 text-zinc-700 font-medium text-[11px]" title="Centrar en Alcaldía">
                    Centrar
                </button>

                <div class="h-4 w-px bg-zinc-200 mx-0.5"></div>

                {{-- Expansión Masiva --}}
                <button type="button" @click="expandAll()" class="px-2 py-1 rounded-lg hover:bg-zinc-100 text-zinc-700 font-medium text-[11px]">
                    Expandir todo
                </button>
                <button type="button" @click="collapseAll()" class="px-2 py-1 rounded-lg hover:bg-zinc-100 text-zinc-700 font-medium text-[11px]">
                    Colapsar
                </button>
            </div>
        </div>

        {{-- Leyenda de Niveles (Esquina Inferior Izquierda) --}}
        <div class="absolute bottom-3 left-3 z-30 pointer-events-none bg-white/95 backdrop-blur-md px-3 py-2 rounded-xl border border-zinc-200 shadow-xs text-[10px] text-zinc-600 flex items-center gap-3">
            <span class="font-semibold text-zinc-800 uppercase tracking-wider">Niveles:</span>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 border border-amber-600"></span> Alcaldía</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> Alta Dirección</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Gerencias</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span> Subgerencias / OTI</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-zinc-400"></span> Áreas</div>
        </div>

        {{-- LIENZO INTERACTIVO (CANVAS DE ARRASTRE Y ZOOM) --}}
        <div class="flex-1 w-full h-full relative cursor-grab active:cursor-grabbing overflow-hidden"
             @mousedown="startDrag($event)"
             @mousemove="onDrag($event)"
             @mouseup="stopDrag()"
             @mouseleave="stopDrag()"
             @wheel.prevent="onWheel($event)">

            {{-- Patrón de Puntos de Fondo (Grid Tecnológico) --}}
            <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-30">
                <defs>
                    <pattern id="grid-dots" width="28" height="28" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="#71717a"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid-dots)"/>
            </svg>

            {{-- Contenedor Transformable (Aplica Pan y Zoom) --}}
            <div class="absolute inset-0 transition-transform duration-75 origin-top-left"
                 :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); transform-origin: 0 0;`">

                {{-- Capa SVG de Líneas Conectoras Curvas (Ramas del Árbol) --}}
                <svg class="absolute inset-0 overflow-visible pointer-events-none" style="width: 4000px; height: 8000px;">
                    <template x-for="link in visibleLinks" :key="link.id">
                        <path :d="link.path"
                              fill="none"
                              stroke="#cbd5e1"
                              stroke-width="2.5"
                              stroke-linecap="round"
                              class="transition-all duration-300"
                              :class="{
                                  'stroke-indigo-600 stroke-[3px] opacity-100': highlightedBranch.includes(link.childId),
                                  'opacity-70': !highlightedBranch.includes(link.childId)
                              }"/>
                    </template>
                </svg>

                {{-- Capa de Nodos Circulares ("Circulitos Relacionados") --}}
                <template x-for="node in visibleNodes" :key="node.id">
                    <div class="absolute node-interactive -translate-x-1/2 -translate-y-1/2 transition-all duration-300 flex flex-col items-center"
                         :style="`left: ${node.x}px; top: ${node.y}px;`"
                         @mouseenter="highlightNode(node.id)"
                         @mouseleave="clearHighlight()">

                        {{-- Contenedor del Círculo Principal --}}
                        <div class="relative group cursor-pointer"
                             @click="openInspector(node.id)">

                            {{-- Círculo según Nivel Jerárquico --}}
                            <div class="rounded-full flex items-center justify-center font-bold transition-all duration-200 shadow-sm group-hover:scale-105"
                                 :class="{
                                     // Nivel 1: Alcaldía (64px, fondo oscuro prestigioso, aro dorado)
                                     'w-16 h-16 bg-slate-950 text-amber-300 border-[3px] border-amber-400 shadow-md ring-4 ring-amber-400/20': node.depth === 1,
                                     // Nivel 2: Alta Dirección (52px, aro índigo)
                                     'w-13 h-13 bg-white text-indigo-700 border-[3px] border-indigo-600 ring-2 ring-indigo-500/20': node.depth === 2,
                                     // Nivel 3: Gerencias (46px, aro azul)
                                     'w-11 h-11 bg-white text-blue-700 border-[2.5px] border-blue-500 ring-2 ring-blue-500/15': node.depth === 3,
                                     // Nivel 4: Subgerencias / OTI (40px, aro teal)
                                     'w-10 h-10 bg-white text-teal-700 border-[2px] border-teal-500': node.depth === 4,
                                     // Nivel 5: Áreas / Unidades (36px, aro zinc)
                                     'w-9 h-9 bg-zinc-50 text-zinc-600 border-[2px] border-zinc-400': node.depth >= 5,
                                     // Selección activa
                                     'ring-4 ring-zinc-950 ring-offset-2 scale-110 shadow-lg': activeNodeId === node.id,
                                     // Coincidencia de búsqueda
                                     'ring-4 ring-amber-400 animate-pulse': isMatch(node.id)
                                 }">

                                {{-- Icono / Iniciales dentro del Círculo --}}
                                <template x-if="node.depth === 1">
                                    <span class="text-xl">🏛️</span>
                                </template>
                                <template x-if="node.depth === 2">
                                    <span class="text-sm font-extrabold" x-text="node.code || 'GM'"></span>
                                </template>
                                <template x-if="node.depth === 3">
                                    <span class="text-xs font-bold" x-text="node.code || 'GER'"></span>
                                </template>
                                <template x-if="node.depth === 4">
                                    <span class="text-[11px] font-bold" x-text="node.code || 'SUB'"></span>
                                </template>
                                <template x-if="node.depth >= 5">
                                    <span class="text-[10px] font-mono" x-text="node.code || '•'"></span>
                                </template>
                            </div>

                            {{-- Botón Flotante para Expandir / Colapsar Ramas Hijas --}}
                            <template x-if="node.hasChildren">
                                <button type="button"
                                        @click.stop="toggleBranch(node.id)"
                                        class="absolute -right-2 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-zinc-900 text-white font-bold text-[10px] flex items-center justify-center hover:scale-125 transition-transform shadow-xs z-20 border border-white"
                                        :title="node.isExpanded ? 'Colapsar subunidades' : 'Expandir subunidades'">
                                    <span x-text="node.isExpanded ? '−' : '+' + node.children.length"></span>
                                </button>
                            </template>

                            {{-- Badge de Equipos Informáticos OTI (Esquina superior) --}}
                            <template x-if="node.assets_count > 0">
                                <span class="absolute -top-1.5 -right-2 px-1.5 py-0.2 rounded-full font-mono text-[9px] font-bold bg-blue-600 text-white shadow-2xs border border-white flex items-center gap-0.5 z-20"
                                      title="Equipos OTI en custodia">
                                    <span>💻</span>
                                    <span x-text="node.assets_count"></span>
                                </span>
                            </template>
                        </div>

                        {{-- Etiqueta de Texto Debajo del Círculo --}}
                        <div class="mt-2 text-center pointer-events-auto cursor-pointer max-w-[200px]"
                             @click="openInspector(node.id)">
                            <h4 class="text-xs font-bold text-zinc-900 tracking-tight leading-snug line-clamp-2 hover:text-indigo-600 transition-colors"
                                x-text="node.name"></h4>
                            <template x-if="node.responsible">
                                <p class="text-[10px] text-zinc-500 truncate mt-0.5" x-text="node.responsible.name"></p>
                            </template>
                        </div>

                    </div>
                </template>

            </div>
        </div>

        {{-- PANEL LATERAL INSPECTOR (SLIDE-OVER AL TOCAR UN CÍRCULO) --}}
        <div x-show="inspectorOpen"
             x-cloak
             class="absolute inset-y-0 right-0 z-40 w-80 sm:w-96 bg-white/95 backdrop-blur-md border-l border-zinc-200 shadow-2xl flex flex-col transition-all duration-300"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            {{-- Encabezado del Inspector --}}
            <div class="p-4 border-b border-zinc-200 bg-zinc-50/70 flex items-start justify-between">
                <div class="min-w-0 pr-2">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-200 text-zinc-700 font-semibold"
                          x-text="selectedUnit ? selectedUnit.type_label : ''"></span>
                    <h3 class="text-base font-bold text-zinc-900 mt-1 leading-snug"
                        x-text="selectedUnit ? selectedUnit.name : 'Cargando...'"></h3>
                    <p class="text-[11px] text-zinc-500 mt-0.5 truncate" x-text="selectedUnit ? selectedUnit.full_path : ''"></p>
                </div>
                <button type="button" @click="closeInspector()" class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-200/50">
                    ✕
                </button>
            </div>

            {{-- Contenido Scrollable del Inspector --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar text-xs">
                {{-- Responsable Titular --}}
                <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block mb-1.5">Responsable Titular</span>
                    <template x-if="selectedUnit && selectedUnit.responsible">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-zinc-900 text-white font-bold text-xs flex items-center justify-center shrink-0"
                                 x-text="selectedUnit.responsible.name.charAt(0)"></div>
                            <div class="min-w-0">
                                <p class="font-semibold text-zinc-900 truncate" x-text="selectedUnit.responsible.name"></p>
                                <p class="text-[11px] text-zinc-500 truncate" x-text="selectedUnit.responsible.position || 'Responsable'"></p>
                                <p class="text-[10px] font-mono text-zinc-400" x-text="'DNI: ' + selectedUnit.responsible.dni"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedUnit && !selectedUnit.responsible">
                        <p class="text-zinc-400 italic">Sin responsable asignado actualmente.</p>
                    </template>
                </div>

                {{-- Métricas Rápidas --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-[10px] uppercase font-bold text-zinc-400 block">Personal</span>
                        <span class="text-xl font-bold font-mono text-zinc-900 mt-0.5 block"
                              x-text="selectedUnit && selectedUnit.employees ? selectedUnit.employees.length : 0"></span>
                        <span class="text-[10px] text-zinc-400">trabajadores</span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-[10px] uppercase font-bold text-zinc-400 block">Equipos OTI</span>
                        <span class="text-xl font-bold font-mono text-blue-700 mt-0.5 block"
                              x-text="selectedUnit && selectedUnit.assets ? selectedUnit.assets.length : 0"></span>
                        <span class="text-[10px] text-zinc-400">activos en custodia</span>
                    </div>
                </div>

                {{-- Subunidades directas --}}
                <template x-if="selectedUnit && selectedUnit.children && selectedUnit.children.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Subunidades Dependientes (' + selectedUnit.children.length + ')'"></span>
                        <div class="space-y-1 max-h-40 overflow-y-auto pr-1">
                            <template x-for="child in selectedUnit.children" :key="child.id">
                                <div @click="openInspector(child.id); centerOnNode(child.id)"
                                     class="p-2 rounded-lg bg-zinc-50 hover:bg-zinc-100 border border-zinc-200/60 cursor-pointer flex items-center justify-between transition-colors">
                                    <span class="font-medium text-zinc-800 truncate" x-text="child.name"></span>
                                    <span class="text-[10px] font-mono text-zinc-400" x-text="child.type_label"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Personal Adscrito --}}
                <template x-if="selectedUnit && selectedUnit.employees && selectedUnit.employees.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Personal en esta oficina (' + selectedUnit.employees.length + ')'"></span>
                        <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                            <template x-for="emp in selectedUnit.employees" :key="emp.id">
                                <div class="p-2 rounded-lg bg-zinc-50 border border-zinc-200/60 flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <p class="font-semibold text-zinc-900 truncate" x-text="emp.name"></p>
                                        <p class="text-[10px] text-zinc-400 truncate" x-text="emp.position || 'Empleado'"></p>
                                    </div>
                                    <a :href="emp.show_url" class="text-zinc-500 hover:text-zinc-900 text-[10px] font-medium shrink-0">Ver &rarr;</a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Activos OTI --}}
                <template x-if="selectedUnit && selectedUnit.assets && selectedUnit.assets.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Equipos informáticos (' + selectedUnit.assets.length + ')'"></span>
                        <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                            <template x-for="asset in selectedUnit.assets" :key="asset.id">
                                <div class="p-2 rounded-lg bg-zinc-50 border border-zinc-200/60 flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <p class="font-semibold text-zinc-900 truncate" x-text="asset.name"></p>
                                        <p class="text-[10px] font-mono text-zinc-400" x-text="(asset.internal_code || 'S/C') + ' • ' + asset.type_label"></p>
                                    </div>
                                    <a :href="asset.show_url" class="text-zinc-500 hover:text-zinc-900 text-[10px] font-medium shrink-0">Ver &rarr;</a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Pie del Inspector con Acciones --}}
            <div class="p-3 border-t border-zinc-200 bg-zinc-50/70 flex items-center gap-2">
                <a :href="selectedUnit ? selectedUnit.show_url : '#'"
                   class="flex-1 text-center py-2 px-3 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                    Ver Ficha Completa &rarr;
                </a>
                @can('org-unit.edit')
                <a :href="selectedUnit ? selectedUnit.edit_url : '#'"
                   class="py-2 px-3 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors">
                    Editar
                </a>
                @endcan
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- MODO 2: DIRECTORIO TABULAR (LISTA CON FILTROS Y PAGINACIÓN)               --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'table'" class="flex-1 overflow-y-auto space-y-4 custom-scrollbar">

        {{-- Filtros y Búsqueda Directa --}}
        <form method="GET" action="{{ route('organizational-units.index') }}" class="flex flex-wrap items-center gap-2 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o código de oficina..."
                       class="w-full pl-9 pr-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
            </div>

            <div class="w-44">
                <select name="type" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                    <option value="">Todos los tipos</option>
                    @foreach (\App\Enums\OrgUnitType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>

            <button type="submit" class="px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors">
                Filtrar
            </button>

            @if (request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route('organizational-units.index') }}"
                   class="px-2.5 py-2 text-xs font-medium text-zinc-500 hover:text-zinc-900 transition-colors" title="Restablecer filtros">
                    Limpiar
                </a>
            @endif
        </form>

        {{-- Tabla de Oficinas --}}
        <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200/80 bg-zinc-50/60 text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Oficina / Dependencia</th>
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4">Depende de</th>
                            <th class="py-3 px-4">Responsable</th>
                            <th class="py-3 px-4 text-center">Personal</th>
                            <th class="py-3 px-4 text-center">Equipos OTI</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/60 text-xs">
                        @forelse ($units as $unit)
                        <tr class="hover:bg-zinc-50/70 transition-colors group">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('organizational-units.show', $unit) }}"
                                       class="font-semibold text-zinc-900 hover:text-zinc-600 transition-colors">
                                        {{ $unit->name }}
                                    </a>
                                    @if ($unit->code)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                                            {{ $unit->code }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-700 border border-zinc-200/70">
                                    {{ $unit->type->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-zinc-600 truncate max-w-[180px] block">{{ $unit->parent?->name ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if ($unit->responsible)
                                    <span class="font-medium text-zinc-800">{{ $unit->responsible->full_name }}</span>
                                @else
                                    <span class="text-zinc-400 text-[11px]">Sin responsable</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @php $empCount = $unit->employees_count ?? $unit->empoyees?->count() ?? 0; @endphp
                                <span class="font-mono text-[11px] {{ $empCount > 0 ? 'text-zinc-800 font-medium' : 'text-zinc-300' }}">{{ $empCount }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @php $assetCount = $unit->assets_count ?? $unit->assets?->count() ?? 0; @endphp
                                <span class="font-mono text-[11px] {{ $assetCount > 0 ? 'text-blue-700 font-medium' : 'text-zinc-300' }}">{{ $assetCount }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 text-[11px] {{ $unit->is_active ? 'text-zinc-700' : 'text-zinc-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $unit->is_active ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                    {{ $unit->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('organizational-units.show', $unit) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors">
                                    Ver &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-zinc-400">
                                No se encontraron oficinas o dependencias.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($units->hasPages())
            <div class="p-4 border-t border-zinc-200/80 bg-zinc-50/40">
                {{ $units->links() }}
            </div>
            @endif
        </div>

    </div>

</div>

{{-- Script del Grafo Interactivo de Nodos y Ramas --}}
<script>
    function orgGraph(treeData) {
        return {
            viewMode: 'graph',
            rawTree: treeData,

            // Canvas Pan y Zoom
            panX: 70,
            panY: 280,
            zoom: 0.85,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,

            // Estado de Ramas y Nodos
            expanded: { 1: true, 6: true }, // Alcaldía y Gerencia Municipal expandidas inicialmente
            visibleNodes: [],
            visibleLinks: [],
            activeNodeId: 1,
            highlightedBranch: [],

            // Slide-over Inspector
            inspectorOpen: false,
            selectedUnit: null,

            // Búsqueda
            searchQuery: '',
            matchedIds: [],

            init() {
                this.expanded[1] = true;
                this.expanded[6] = true;
                this.recalculateLayout();
            },

            recalculateLayout() {
                let leafCounter = 0;
                const nodes = [];
                const links = [];

                const traverse = (node, depth, parentPos) => {
                    const isNodeExpanded = !!this.expanded[node.id];
                    const hasChildren = node.children && node.children.length > 0;
                    const showChildren = isNodeExpanded && hasChildren;

                    const x = 70 + (depth - 1) * 270;
                    let y;

                    if (showChildren) {
                        const childrenY = [];
                        for (const child of node.children) {
                            const childPos = traverse(child, depth + 1, { x, y: 0, id: node.id });
                            childrenY.push(childPos.y);
                        }
                        y = (childrenY[0] + childrenY[childrenY.length - 1]) / 2;
                    } else {
                        y = leafCounter * 115;
                        leafCounter++;
                    }

                    const nodePos = {
                        ...node,
                        x,
                        y,
                        depth,
                        isExpanded: isNodeExpanded,
                        hasChildren
                    };
                    nodes.push(nodePos);

                    if (parentPos) {
                        const x1 = parentPos.x + 32;
                        const y1 = parentPos.y;
                        const x2 = x - 32;
                        const y2 = y;
                        const cx = (x1 + x2) / 2;
                        const path = `M ${x1} ${y1} C ${cx} ${y1}, ${cx} ${y2}, ${x2} ${y2}`;
                        links.push({
                            id: `${parentPos.id}->${node.id}`,
                            parentId: parentPos.id,
                            childId: node.id,
                            path
                        });
                    }

                    return nodePos;
                };

                for (const root of this.rawTree) {
                    traverse(root, 1, null);
                }

                this.visibleNodes = nodes;
                this.visibleLinks = links;
            },

            toggleBranch(nodeId) {
                this.expanded[nodeId] = !this.expanded[nodeId];
                this.recalculateLayout();
            },

            expandAll() {
                const expandRecursive = (list) => {
                    for (const item of list) {
                        this.expanded[item.id] = true;
                        if (item.children) expandRecursive(item.children);
                    }
                };
                expandRecursive(this.rawTree);
                this.recalculateLayout();
            },

            collapseAll() {
                this.expanded = { 1: true };
                this.recalculateLayout();
                this.resetView();
            },

            // Panning y Zoom
            startDrag(e) {
                if (e.target.closest('.node-interactive')) return;
                this.isDragging = true;
                this.dragStartX = e.clientX - this.panX;
                this.dragStartY = e.clientY - this.panY;
            },

            onDrag(e) {
                if (!this.isDragging) return;
                this.panX = e.clientX - this.dragStartX;
                this.panY = e.clientY - this.dragStartY;
            },

            stopDrag() {
                this.isDragging = false;
            },

            onWheel(e) {
                const zoomFactor = e.deltaY < 0 ? 1.08 : 0.92;
                this.zoom = Math.min(Math.max(this.zoom * zoomFactor, 0.35), 1.8);
            },

            zoomIn() {
                this.zoom = Math.min(this.zoom * 1.15, 1.8);
            },

            zoomOut() {
                this.zoom = Math.max(this.zoom * 0.85, 0.35);
            },

            resetView() {
                this.centerOnNode(1);
            },

            centerOnNode(nodeId) {
                const node = this.visibleNodes.find(n => n.id === nodeId);
                if (node) {
                    this.panX = 180 - node.x * this.zoom;
                    this.panY = 280 - node.y * this.zoom;
                }
            },

            highlightNode(nodeId) {
                const branch = [nodeId];
                let current = this.visibleLinks.find(l => l.childId === nodeId);
                while (current) {
                    branch.push(current.parentId);
                    current = this.visibleLinks.find(l => l.childId === current.parentId);
                }
                this.highlightedBranch = branch;
            },

            clearHighlight() {
                this.highlightedBranch = [];
            },

            onSearch() {
                const q = this.searchQuery.trim().toLowerCase();
                if (q.length < 2) {
                    this.matchedIds = [];
                    return;
                }

                const matches = [];
                const expandParents = (list, parentChain = []) => {
                    for (const item of list) {
                        const name = (item.name || '').toLowerCase();
                        const code = (item.code || '').toLowerCase();
                        const titular = item.responsible ? (item.responsible.name || '').toLowerCase() : '';

                        if (name.includes(q) || code.includes(q) || titular.includes(q)) {
                            matches.push(item.id);
                            // Auto expande la ruta completa hasta la raíz
                            for (const pId of parentChain) {
                                this.expanded[pId] = true;
                            }
                        }

                        if (item.children && item.children.length > 0) {
                            expandParents(item.children, [...parentChain, item.id]);
                        }
                    }
                };

                expandParents(this.rawTree);
                this.matchedIds = matches;
                this.recalculateLayout();

                // Centra suavemente en el primer nodo coincidente
                if (matches.length > 0) {
                    setTimeout(() => this.centerOnNode(matches[0]), 100);
                }
            },

            isMatch(nodeId) {
                return this.matchedIds.includes(nodeId);
            },

            async openInspector(nodeId) {
                this.activeNodeId = nodeId;
                this.inspectorOpen = true;

                try {
                    const res = await fetch('/organizational-units/' + nodeId + '/quick-info');
                    if (res.ok) {
                        this.selectedUnit = await res.json();
                    }
                } catch (e) {
                    console.error('Error fetching unit info:', e);
                }
            },

            closeInspector() {
                this.inspectorOpen = false;
            }
        };
    }
</script>

</x-layouts.app>
