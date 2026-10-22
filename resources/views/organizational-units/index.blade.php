<x-layouts.app title="Estructura Organizacional" moduleLabel="Organización" pageTitle="Estructura y Dependencias">

<div x-data="dynamicOrganigram({{ json_encode($treeData, JSON_HEX_APOS | JSON_HEX_QUOT) }})"
     x-init="init()"
     class="flex flex-col h-[calc(100vh-95px)] overflow-hidden select-none">

    {{-- ========================================================================= --}}
    {{-- BARRA SUPERIOR: ENCABEZADO MINIMALISTA, MÉTRICAS Y VISTA                  --}}
    {{-- ========================================================================= --}}
    <div class="flex items-center justify-between gap-3 pb-2.5 mb-2 border-b border-zinc-200/80 shrink-0">
        {{-- Título y Métricas Rápidas --}}
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                <h1 class="text-base sm:text-lg font-bold tracking-tight text-zinc-900">Oficinas y Dependencias</h1>
            </div>
            <div class="flex items-center gap-2 text-xs font-mono text-zinc-500">
                <span class="px-2 py-0.5 rounded-md bg-zinc-100 text-zinc-700 border border-zinc-200 font-sans">
                    <strong class="font-mono text-zinc-900">{{ $stats['total'] ?? $units->total() }}</strong> dependencias
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-zinc-600 font-sans">
                    <strong class="font-mono text-emerald-600">{{ $stats['active'] ?? 0 }}</strong> activas
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-zinc-600 font-sans">
                    <strong class="font-mono text-blue-600">{{ $stats['with_assets'] ?? 0 }}</strong> con activos OTI
                </span>
            </div>
        </div>

        {{-- Switch de Vistas (Árbol Dinámico vs Tabla) y Acciones --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="inline-flex items-center p-0.5 rounded-lg bg-zinc-100 border border-zinc-200 text-xs">
                <button type="button"
                        @click="viewMode = 'tree'"
                        :class="viewMode === 'tree' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM8.25 10.5v3M10.5 8.25h3m0 7.5h3"/></svg>
                    <span>Árbol</span>
                </button>
                <button type="button"
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
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
    {{-- MODO 1: ORGANIGRAMA ULTRA-MINIMALISTA Y 100% DINÁMICO (CUALQUIER ENTIDAD)  --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'tree'"
         x-ref="canvasContainer"
         class="flex-1 relative bg-zinc-50/70 rounded-2xl border border-zinc-200/90 overflow-hidden flex flex-col">

        {{-- Barra de Controles Superior Flotante (Búsqueda + Niveles + Zoom) --}}
        <div class="absolute top-3 left-3 right-3 z-30 flex items-center justify-between gap-2.5 pointer-events-none">
            {{-- Buscador en Vivo --}}
            <div class="pointer-events-auto relative w-64 sm:w-72 shadow-xs">
                <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text"
                       x-model="searchQuery"
                       @input="onSearch()"
                       placeholder="Buscar oficina o responsable..."
                       class="w-full pl-9 pr-7 py-1.5 bg-white/95 backdrop-blur-md rounded-xl border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs">
                <button type="button"
                        x-show="searchQuery.length > 0"
                        @click="searchQuery = ''; onSearch()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs">
                    ✕
                </button>
            </div>

            {{-- Niveles y Controles de Zoom --}}
            <div class="pointer-events-auto flex items-center gap-1 bg-white/95 backdrop-blur-md p-1 rounded-xl border border-zinc-200 shadow-xs text-xs">
                {{-- Selectores Rápidos de Profundidad --}}
                <button type="button"
                        @click="expandLevel(1)"
                        :class="currentLevel === 1 ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100'"
                        class="px-2 py-1 rounded-md text-[11px] transition-colors"
                        title="Mostrar solo nivel 1 (Dirección)">
                    Nivel 1
                </button>
                <button type="button"
                        @click="expandLevel(2)"
                        :class="currentLevel === 2 ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100'"
                        class="px-2 py-1 rounded-md text-[11px] transition-colors"
                        title="Mostrar hasta nivel 2 (Gerencias / Órganos Generales)">
                    Nivel 2
                </button>
                <button type="button"
                        @click="expandAll()"
                        class="px-2 py-1 rounded-md text-[11px] text-zinc-600 hover:bg-zinc-100 transition-colors"
                        title="Expandir todas las oficinas">
                    Expandir todo
                </button>
                <button type="button"
                        @click="collapseAll()"
                        class="px-2 py-1 rounded-md text-[11px] text-zinc-600 hover:bg-zinc-100 transition-colors"
                        title="Colapsar dependencias">
                    Colapsar
                </button>

                <div class="h-4 w-px bg-zinc-200 mx-0.5"></div>

                {{-- Ajustar a una sola pantalla --}}
                <button type="button"
                        @click="fitToScreen()"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-md bg-zinc-100 hover:bg-zinc-200 text-zinc-800 font-semibold text-[11px] transition-colors"
                        title="Ajustar árbol a la pantalla">
                    <svg class="w-3.5 h-3.5 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                    <span>Ajustar</span>
                </button>

                {{-- Zoom In / Out / Reset --}}
                <button type="button" @click="zoomIn()" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-zinc-100 text-zinc-700 font-bold" title="Acercar (+)">
                    +
                </button>
                <button type="button" @click="zoomOut()" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-zinc-100 text-zinc-700 font-bold" title="Alejar (-)">
                    &minus;
                </button>
                <button type="button" @click="reset100()" class="px-1.5 py-1 rounded-md hover:bg-zinc-100 text-zinc-600 text-[10px] font-mono" title="Restablecer a 100%">
                    100%
                </button>
            </div>
        </div>

        {{-- Leyenda Minimalista (Esquina Inferior Izquierda) --}}
        <div class="absolute bottom-3 left-3 z-30 pointer-events-none bg-white/95 backdrop-blur-md px-3 py-1.5 rounded-xl border border-zinc-200 shadow-2xs text-[10px] text-zinc-500 hidden sm:flex items-center gap-3">
            <span class="font-bold text-zinc-700 uppercase tracking-wider text-[9px]">Jerarquía:</span>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-xs bg-zinc-950"></span> Dirección (N1)</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-xs bg-white border border-zinc-400"></span> Gerencias (N2)</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-xs bg-zinc-50 border border-zinc-200"></span> Subgerencias / Áreas (N3+)</div>
            <div class="h-3 w-px bg-zinc-200 mx-1"></div>
            <div class="flex items-center gap-1"><span class="font-mono text-zinc-700 font-bold" x-text="visibleNodes.length"></span> nodos visibles</div>
        </div>

        {{-- LIENZO INTERACTIVO CON PAN (ARRASTRE) Y ZOOM --}}
        <div class="flex-1 w-full h-full relative cursor-grab active:cursor-grabbing overflow-hidden"
             @mousedown="startDrag($event)"
             @mousemove="onDrag($event)"
             @mouseup="stopDrag()"
             @mouseleave="stopDrag()"
             @wheel.prevent="onWheel($event)">

            {{-- Fondo con Cuadrícula Tecnológica Minimalista --}}
            <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-25">
                <defs>
                    <pattern id="minimal-dots" width="28" height="28" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="#71717a"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#minimal-dots)"/>
            </svg>

            {{-- Contenedor Transformable (Aplica Pan y Zoom) --}}
            <div class="absolute origin-top-left transition-transform duration-75"
                 :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom});`">

                {{-- CAPA SVG: LÍNEAS CONECTORAS ORTOGONALES ESTILO ORGANIGRAMA --}}
                <svg class="absolute inset-0 overflow-visible pointer-events-none" style="width: 8000px; height: 5000px;">
                    <template x-for="link in visibleLinks" :key="link.id">
                        <path :d="link.path"
                              fill="none"
                              stroke="#cbd5e1"
                              stroke-width="1.5"
                              stroke-linecap="round"
                              class="transition-all duration-200"
                              :class="{
                                  'stroke-blue-600 stroke-[2.5px] opacity-100': isBranchHighlighted(link.parentId, link.childId),
                                  'opacity-75': !isBranchHighlighted(link.parentId, link.childId)
                              }"/>
                    </template>
                </svg>

                {{-- CAPA DE NODOS (MICRO-TARJETAS MINIMALISTAS) --}}
                <template x-for="node in visibleNodes" :key="node.id">
                    <div class="absolute transition-all duration-200 flex flex-col cursor-pointer"
                         :style="`left: ${node.x}px; top: ${node.y}px; width: ${nodeWidth}px; height: ${nodeHeight}px;`"
                         @mouseenter="highlightNode(node.id)"
                         @mouseleave="clearHighlight()"
                         @click="openInspector(node.id)">

                        {{-- Estructura de la Tarjeta --}}
                        <div class="w-full h-full rounded-lg p-2 flex flex-col justify-between transition-all duration-150 border select-none group"
                             :class="{
                                 // Nivel 1: Dirección Ejecutiva (fondo oscuro neutro, borde sólido)
                                 'bg-zinc-950 text-white border-zinc-950 shadow-sm': node.depth === 1,
                                 // Nivel 2: Órganos Generales / Gerencias (fondo blanco, borde zinc-300)
                                 'bg-white text-zinc-900 border-zinc-300 shadow-2xs hover:border-zinc-900': node.depth === 2,
                                 // Nivel 3+: Subgerencias y Áreas (fondo blanco, borde ligero)
                                 'bg-white text-zinc-900 border-zinc-200/90 shadow-2xs hover:border-zinc-400': node.depth >= 3,
                                 // Seleccionado actualmente
                                 'ring-2 ring-zinc-900 ring-offset-2': activeNodeId === node.id,
                                 // Coincidencia de búsqueda
                                 'ring-2 ring-amber-500 ring-offset-1 border-amber-500 bg-amber-50/40': isMatch(node.id)
                             }">

                            {{-- Fila Superior: Tipo/Código y Botón de Expansión --}}
                            <div class="flex items-center justify-between gap-1 w-full">
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono uppercase tracking-wider font-semibold truncate max-w-[130px]"
                                      :class="{
                                          'bg-zinc-800 text-zinc-200': node.depth === 1,
                                          'bg-zinc-100 text-zinc-600': node.depth > 1
                                      }"
                                      x-text="node.code || node.type_label"></span>

                                {{-- Botón de Expansión / Colapso si tiene hijos --}}
                                <template x-if="node.hasChildren">
                                    <button type="button"
                                            @click.stop="toggleBranch(node.id)"
                                            class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[9px] font-mono font-bold transition-transform hover:scale-110 shrink-0 border"
                                            :class="{
                                                'bg-zinc-800 text-white border-zinc-700': node.depth === 1,
                                                'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 border-zinc-200': node.depth > 1
                                            }"
                                            :title="node.isExpanded ? 'Colapsar dependencias' : 'Expandir dependencias'">
                                        <span x-text="node.isExpanded ? '−' : '+' + node.childrenCount"></span>
                                    </button>
                                </template>
                            </div>

                            {{-- Fila Media: Nombre de la Dependencia --}}
                            <div class="my-0.5">
                                <h4 class="text-[11px] font-semibold leading-tight line-clamp-2"
                                    :class="node.depth === 1 ? 'text-white' : 'text-zinc-900 group-hover:text-blue-700'"
                                    :title="node.name"
                                    x-text="node.name"></h4>
                            </div>

                            {{-- Fila Inferior: Métricas Rápidas usando SÍMBOLOS SVG (CERO EMOJIS) --}}
                            <div class="flex items-center justify-between text-[9px] font-mono pt-1 border-t"
                                 :class="node.depth === 1 ? 'border-zinc-800 text-zinc-400' : 'border-zinc-100 text-zinc-500'">
                                <div class="flex items-center gap-2.5">
                                    {{-- Personal --}}
                                    <span class="inline-flex items-center gap-1" title="Personal adscrito">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                        <span x-text="node.employees_count"></span>
                                    </span>
                                    {{-- Activos OTI --}}
                                    <span class="inline-flex items-center gap-1"
                                          :class="node.assets_count > 0 ? (node.depth === 1 ? 'text-blue-300 font-bold' : 'text-blue-600 font-bold') : ''"
                                          title="Equipos OTI asignados">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/></svg>
                                        <span x-text="node.assets_count"></span>
                                    </span>
                                </div>

                                {{-- Titular abreviado si existe --}}
                                <template x-if="node.responsible">
                                    <span class="truncate max-w-[80px] font-sans text-[8.5px]"
                                          :title="'Responsable: ' + node.responsible.name"
                                          x-text="node.responsible.name.split(' ')[0]"></span>
                                </template>
                            </div>

                        </div>

                    </div>
                </template>

            </div>
        </div>

        {{-- PANEL LATERAL INSPECTOR (SLIDE-OVER AL TOCAR CUALQUIER TARJETA) --}}
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
                          x-text="selectedUnit ? selectedUnit.type_label : 'Dependencia'"></span>
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
                              x-text="'Subunidades y Dependencias (' + selectedUnit.children.length + ')'"></span>
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
                                        <p class="text-[10px] text-zinc-400 truncate" x-text="emp.position || 'Servidor'"></p>
                                    </div>
                                    <a :href="emp.show_url" class="text-zinc-600 hover:text-zinc-950 text-[10px] font-medium shrink-0 flex items-center gap-0.5">
                                        <span>Ficha</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
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
                                    <a :href="asset.show_url" class="text-zinc-600 hover:text-zinc-950 text-[10px] font-medium shrink-0 flex items-center gap-0.5">
                                        <span>Ver</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Pie del Inspector con Acciones --}}
            <div class="p-3 border-t border-zinc-200 bg-zinc-50/70 flex items-center gap-2">
                <template x-if="selectedUnit && selectedUnit.show_url">
                    <a :href="selectedUnit.show_url"
                       class="flex-1 text-center py-2 px-3 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                        Ver Ficha Completa
                    </a>
                </template>
                <template x-if="selectedUnit && selectedUnit.edit_url">
                    @can('org-unit.edit')
                    <a :href="selectedUnit.edit_url"
                       class="py-2 px-3 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors">
                        Editar
                    </a>
                    @endcan
                </template>
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
                                <a href="{{ route('organizational-units.show', $unit) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors flex items-center justify-end gap-1">
                                    <span>Ver</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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

{{-- SCRIPT: MOTOR DE DISTRIBUCIÓN JERÁRQUICA DINÁMICA 100% PARA CUALQUIER ENTIDAD --}}
<script>
    function dynamicOrganigram(treeData) {
        return {
            viewMode: 'tree',
            rawTree: treeData || [],

            // Dimensiones fijas de tarjeta para cálculo de grafo
            nodeWidth: 210,
            nodeHeight: 74,
            gapX: 24,
            gapY: 64,

            // Panning y Zoom
            panX: 40,
            panY: 30,
            zoom: 0.85,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,

            // Estado del árbol
            expanded: {},
            currentLevel: 2,
            visibleNodes: [],
            visibleLinks: [],
            activeNodeId: null,
            highlightedBranch: [],

            // Slide-over Inspector
            inspectorOpen: false,
            selectedUnit: null,

            // Búsqueda en vivo
            searchQuery: '',
            matchedIds: [],

            init() {
                // Iniciar expandiendo hasta nivel 2 (Dirección y Gerencias)
                this.expandLevel(2);

                // Auto-ajustar a la pantalla después del render
                this.$nextTick(() => {
                    this.fitToScreen();
                });
            },

            // Cálculo del layout top-down con líneas ortogonales estilo organigrama
            recalculateLayout() {
                let slotCounter = 0;
                const nodes = [];
                const parentGroups = {};

                const traverse = (node, depth, parentId = null) => {
                    const isExpanded = !!this.expanded[node.id];
                    const hasChildren = node.children && node.children.length > 0;
                    const showChildren = isExpanded && hasChildren;

                    const y = 40 + (depth - 1) * (this.nodeHeight + this.gapY);
                    let x = 0;

                    if (showChildren) {
                        const childrenX = [];
                        for (const child of node.children) {
                            const childPos = traverse(child, depth + 1, node.id);
                            childrenX.push(childPos.x);
                        }
                        x = (childrenX[0] + childrenX[childrenX.length - 1]) / 2;
                    } else {
                        x = 40 + slotCounter * (this.nodeWidth + this.gapX);
                        slotCounter++;
                    }

                    const pos = {
                        ...node,
                        x,
                        y,
                        depth,
                        hasChildren,
                        isExpanded,
                        childrenCount: node.children ? node.children.length : 0
                    };
                    nodes.push(pos);

                    if (parentId) {
                        if (!parentGroups[parentId]) parentGroups[parentId] = [];
                        parentGroups[parentId].push(pos);
                    }

                    return pos;
                };

                for (const root of this.rawTree) {
                    traverse(root, 1, null);
                }

                // Generar líneas conectoras ortogonales (Bus-Bar organigram)
                const links = [];
                for (const [pId, children] of Object.entries(parentGroups)) {
                    const parent = nodes.find(n => n.id == pId);
                    if (!parent || children.length === 0) continue;

                    const pCenterX = parent.x + this.nodeWidth / 2;
                    const pBottomY = parent.y + this.nodeHeight;
                    const midY = pBottomY + this.gapY / 2;

                    const childXs = children.map(c => c.x + this.nodeWidth / 2);
                    const minX = Math.min(...childXs, pCenterX);
                    const maxX = Math.max(...childXs, pCenterX);

                    // Trazo SVG: bajada de padre -> barra horizontal colectora -> bajada a cada hijo
                    let path = `M ${pCenterX} ${pBottomY} L ${pCenterX} ${midY} `;
                    path += `M ${minX} ${midY} L ${maxX} ${midY} `;
                    for (const child of children) {
                        const cCenterX = child.x + this.nodeWidth / 2;
                        path += `M ${cCenterX} ${midY} L ${cCenterX} ${child.y} `;
                    }

                    links.push({
                        id: `link-${parent.id}`,
                        parentId: parent.id,
                        childIds: children.map(c => c.id),
                        path
                    });
                }

                this.visibleNodes = nodes;
                this.visibleLinks = links;
            },

            // Control de niveles
            expandLevel(maxLevel) {
                this.currentLevel = maxLevel;
                const newExpanded = {};
                const markLevel = (list, depth) => {
                    for (const item of list) {
                        if (depth < maxLevel) {
                            newExpanded[item.id] = true;
                        }
                        if (item.children) {
                            markLevel(item.children, depth + 1);
                        }
                    }
                };
                markLevel(this.rawTree, 1);
                this.expanded = newExpanded;
                this.recalculateLayout();
                this.$nextTick(() => this.fitToScreen());
            },

            toggleBranch(nodeId) {
                this.expanded[nodeId] = !this.expanded[nodeId];
                this.recalculateLayout();
            },

            expandAll() {
                this.currentLevel = 99;
                const newExpanded = {};
                const markAll = (list) => {
                    for (const item of list) {
                        newExpanded[item.id] = true;
                        if (item.children) markAll(item.children);
                    }
                };
                markAll(this.rawTree);
                this.expanded = newExpanded;
                this.recalculateLayout();
                this.$nextTick(() => this.fitToScreen());
            },

            collapseAll() {
                this.currentLevel = 1;
                this.expanded = {};
                this.recalculateLayout();
                this.$nextTick(() => this.fitToScreen());
            },

            // Ajustar a una sola pantalla matemáticamente
            fitToScreen() {
                if (!this.visibleNodes || this.visibleNodes.length === 0) return;
                const container = this.$refs.canvasContainer;
                if (!container) return;

                let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
                for (const node of this.visibleNodes) {
                    if (node.x < minX) minX = node.x;
                    if (node.x + this.nodeWidth > maxX) maxX = node.x + this.nodeWidth;
                    if (node.y < minY) minY = node.y;
                    if (node.y + this.nodeHeight > maxY) maxY = node.y + this.nodeHeight;
                }

                const pad = 50;
                const contentW = (maxX - minX) + pad * 2;
                const contentH = (maxY - minY) + pad * 2;

                const cWidth = container.clientWidth || 1200;
                const cHeight = container.clientHeight || 700;

                const scaleX = cWidth / contentW;
                const scaleY = cHeight / contentH;
                const fitScale = Math.min(scaleX, scaleY);

                this.zoom = Math.min(Math.max(fitScale, 0.25), 1.05);
                this.panX = (cWidth - contentW * this.zoom) / 2 - (minX - pad) * this.zoom;
                this.panY = (cHeight - contentH * this.zoom) / 2 - (minY - pad) * this.zoom;
            },

            reset100() {
                this.zoom = 1.0;
                this.centerOnFirstRoot();
            },

            centerOnFirstRoot() {
                const root = this.visibleNodes[0];
                const container = this.$refs.canvasContainer;
                if (root && container) {
                    this.panX = (container.clientWidth / 2) - (root.x + this.nodeWidth / 2) * this.zoom;
                    this.panY = 40;
                }
            },

            zoomIn() {
                this.zoom = Math.min(this.zoom * 1.15, 2.0);
            },

            zoomOut() {
                this.zoom = Math.max(this.zoom * 0.85, 0.25);
            },

            startDrag(e) {
                if (e.target.closest('button, a, input, select, .cursor-pointer')) return;
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
                this.zoom = Math.min(Math.max(this.zoom * zoomFactor, 0.20), 2.0);
            },

            // Resaltado de ruta hacia la raíz al pasar el ratón
            highlightNode(nodeId) {
                const branch = [nodeId];
                let currentId = nodeId;

                const findParentId = (id) => {
                    const node = this.visibleNodes.find(n => n.id === id);
                    return node ? node.parent_id : null;
                };

                let parentId = findParentId(currentId);
                while (parentId) {
                    branch.push(parentId);
                    currentId = parentId;
                    parentId = findParentId(currentId);
                }

                this.highlightedBranch = branch;
            },

            clearHighlight() {
                this.highlightedBranch = [];
            },

            isBranchHighlighted(parentId, childId) {
                return this.highlightedBranch.includes(parentId) && this.highlightedBranch.includes(childId);
            },

            // Búsqueda en vivo
            onSearch() {
                const q = (this.searchQuery || '').trim().toLowerCase();
                if (q.length < 2) {
                    this.matchedIds = [];
                    return;
                }

                const matches = [];
                const searchRecursive = (list, parentChain = []) => {
                    for (const item of list) {
                        const name = (item.name || '').toLowerCase();
                        const code = (item.code || '').toLowerCase();
                        const titular = item.responsible ? (item.responsible.name || '').toLowerCase() : '';

                        if (name.includes(q) || code.includes(q) || titular.includes(q)) {
                            matches.push(item.id);
                            for (const pId of parentChain) {
                                this.expanded[pId] = true;
                            }
                        }

                        if (item.children && item.children.length > 0) {
                            searchRecursive(item.children, [...parentChain, item.id]);
                        }
                    }
                };

                searchRecursive(this.rawTree);
                this.matchedIds = matches;
                this.recalculateLayout();

                if (matches.length > 0) {
                    setTimeout(() => this.centerOnNode(matches[0]), 100);
                }
            },

            isMatch(nodeId) {
                return this.matchedIds.includes(nodeId);
            },

            centerOnNode(nodeId) {
                const node = this.visibleNodes.find(n => n.id === nodeId);
                const container = this.$refs.canvasContainer;
                if (node && container) {
                    this.panX = (container.clientWidth / 2) - (node.x + this.nodeWidth / 2) * this.zoom;
                    this.panY = (container.clientHeight / 2) - (node.y + this.nodeHeight / 2) * this.zoom;
                }
            },

            // Inspector Slide-over
            async openInspector(unitId) {
                this.activeNodeId = unitId;
                this.inspectorOpen = true;

                try {
                    const res = await fetch('/organizational-units/' + unitId + '/quick-info');
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
