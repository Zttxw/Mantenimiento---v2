<x-layouts.app title="Estructura Organizacional" moduleLabel="Organización" pageTitle="Estructura y Dependencias">

<div x-data="orgWorkspace({{ json_encode($treeData, JSON_HEX_APOS | JSON_HEX_QUOT) }})"
     x-init="init()"
     class="flex flex-col h-[calc(100vh-95px)] overflow-hidden select-none">

    {{-- ========================================================================= --}}
    {{-- BARRA SUPERIOR: ENCABEZADO INSTITUCIONAL, MÉTRICAS Y SELECTOR DE VISTAS   --}}
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
                    <strong class="font-mono text-zinc-900">{{ $stats['with_responsible'] ?? 0 }}</strong> con jefatura
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-zinc-600 font-sans">
                    <strong class="font-mono text-zinc-900">{{ $stats['total_employees'] ?? 0 }}</strong> personal adscrito
                </span>
            </div>
        </div>

        {{-- Switch de Vistas (Carpetas vs Grafo en Círculos vs Tabla) y Acciones --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="inline-flex items-center p-0.5 rounded-lg bg-zinc-100 border border-zinc-200 text-xs">
                {{-- Vista 1: Carpetas y Subcarpetas (Estructurador) --}}
                <button type="button"
                        @click="viewMode = 'folders'"
                        :class="viewMode === 'folders' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all"
                        title="Estructurador como carpetas y subcarpetas">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                    <span>Carpetas</span>
                </button>

                {{-- Vista 2: Grafo en Círculos (Árbol Visual) --}}
                <button type="button"
                        @click="viewMode = 'circles'"
                        :class="viewMode === 'circles' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all"
                        title="Grafo visual en círculos interconectados">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/></svg>
                    <span>Árbol (Grafo)</span>
                </button>

                {{-- Vista 3: Tabla (Directorio) --}}
                <button type="button"
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all"
                        title="Directorio tabular">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                    <span>Tabla</span>
                </button>
            </div>

            @can('org-unit.create')
            <button type="button"
                    @click="openCreateModal(null, 'Dependencia Principal (Raíz)')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Carpeta Raíz</span>
            </button>
            @endcan
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- VISTA 1: ESTRUCTURADOR COMO CARPETAS Y SUBCARPETAS (SISTEMA DESDE 0)      --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'folders'" class="flex-1 overflow-y-auto space-y-3 custom-scrollbar pr-1">

        {{-- Barra de Herramientas del Explorador de Carpetas --}}
        <div class="flex items-center justify-between gap-3 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs">
            <div class="flex items-center gap-2 flex-1 max-w-md">
                <div class="relative w-full">
                    <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text"
                           x-model="folderFilter"
                           placeholder="Filtrar carpetas, áreas y dependencias..."
                           class="w-full pl-9 pr-3 py-1.5 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs">
                <button type="button" @click="expandAllFolders()" class="px-2.5 py-1 rounded-md text-zinc-600 hover:bg-zinc-100 text-[11px] font-medium transition-colors">
                    Expandir todo
                </button>
                <button type="button" @click="collapseAllFolders()" class="px-2.5 py-1 rounded-md text-zinc-600 hover:bg-zinc-100 text-[11px] font-medium transition-colors">
                    Colapsar todo
                </button>
            </div>
        </div>

        {{-- ESTADO VACÍO (CUANDO EL SISTEMA ESTÁ EN 0 EN UNA NUEVA INSTITUCIÓN) --}}
        <template x-if="rawTree.length === 0">
            <div class="p-12 text-center bg-white rounded-2xl border-2 border-dashed border-zinc-200 shadow-xs max-w-2xl mx-auto my-8">
                <div class="w-12 h-12 rounded-2xl bg-zinc-100 text-zinc-700 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-zinc-900">Estructura institucional sin dependencias</h3>
                <p class="text-xs text-zinc-500 mt-1 max-w-md mx-auto leading-relaxed">
                    Comienza definiendo la carpeta principal de la institución (ej. Alcaldía, Rectorado, Dirección General o Gerencia General). Luego podrás ir agregando sus sub-áreas dependientes como carpetas y subcarpetas.
                </p>
                <div class="mt-5">
                    @can('org-unit.create')
                    <button type="button"
                            @click="openCreateModal(null, 'Dependencia Principal (Raíz)')"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Crear Primera Carpeta Raíz</span>
                    </button>
                    @endcan
                </div>
            </div>
        </template>

        {{-- LISTADO JERÁRQUICO RECURSIVO DE CARPETAS Y SUBCARPETAS --}}
        <div x-show="rawTree.length > 0" class="bg-white rounded-xl border border-zinc-200/80 shadow-xs p-3 space-y-1">
            <template x-for="rootNode in rawTree" :key="rootNode.id">
                <div class="folder-tree-item">
                    {{-- Tarjeta de la Carpeta --}}
                    <div class="group flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 border border-transparent hover:border-zinc-200/80 transition-all cursor-pointer"
                         @click="toggleFolder(rootNode.id)">
                        
                        {{-- Icono de Carpeta, Chevron y Nombre --}}
                        <div class="flex items-center gap-2.5 min-w-0 pr-2">
                            {{-- Chevron de expansión --}}
                            <button type="button"
                                    class="w-5 h-5 flex items-center justify-center rounded text-zinc-400 group-hover:text-zinc-700 transition-transform"
                                    :class="isFolderExpanded(rootNode.id) ? 'rotate-90' : ''"
                                    x-show="rootNode.children && rootNode.children.length > 0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <span class="w-5 h-5" x-show="!rootNode.children || rootNode.children.length === 0"></span>

                            {{-- Icono SVG de Carpeta --}}
                            <svg class="w-4 h-4 text-zinc-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                            </svg>

                            <span class="font-bold text-xs text-zinc-900 truncate" x-text="rootNode.name"></span>
                            
                            <template x-if="rootNode.code">
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200" x-text="rootNode.code"></span>
                            </template>

                            {{-- Responsable Titular Abreviado --}}
                            <template x-if="rootNode.responsible">
                                <span class="hidden md:inline text-[11px] text-zinc-500 font-sans truncate max-w-[200px]"
                                      x-text="'• ' + rootNode.responsible.name"></span>
                            </template>
                        </div>

                        {{-- Métricas Organizacionales y Botón Rápido + Subcarpeta --}}
                        <div class="flex items-center gap-3 shrink-0" @click.stop>
                            {{-- Conteo de Subcarpetas --}}
                            <template x-if="rootNode.children && rootNode.children.length > 0">
                                <span class="text-[10px] text-zinc-400 font-mono" x-text="rootNode.children.length + ' sub-áreas'"></span>
                            </template>

                            {{-- Personal Adscrito --}}
                            <template x-if="rootNode.employees_count > 0">
                                <span class="hidden sm:inline-flex items-center gap-1 text-[10px] font-mono text-zinc-500" title="Personal adscrito">
                                    <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    <span x-text="rootNode.employees_count"></span>
                                </span>
                            </template>

                            {{-- Botón "+ Sub-área" para armar como carpetas --}}
                            @can('org-unit.create')
                            <button type="button"
                                    @click="openCreateModal(rootNode.id, rootNode.name)"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-medium bg-zinc-100 hover:bg-zinc-900 text-zinc-700 hover:text-white transition-colors border border-zinc-200"
                                    title="Añadir subcarpeta o sub-oficina dependiente">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Sub-área</span>
                            </button>
                            @endcan

                            <a :href="rootNode.show_url" class="p-1 rounded text-zinc-400 hover:text-zinc-700" title="Ver ficha">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Contenedor de Subcarpetas Recursivas (Indentación) --}}
                    <div x-show="isFolderExpanded(rootNode.id) && rootNode.children && rootNode.children.length > 0"
                         class="pl-6 border-l-2 border-zinc-100 ml-4 space-y-1 my-1">
                        <template x-for="child in rootNode.children" :key="child.id">
                            <div class="folder-child-item">
                                <div class="group flex items-center justify-between p-1.5 rounded-lg hover:bg-zinc-50 border border-transparent hover:border-zinc-200/80 transition-all cursor-pointer"
                                     @click="toggleFolder(child.id)">
                                    
                                    <div class="flex items-center gap-2 min-w-0 pr-2">
                                        <button type="button"
                                                class="w-4 h-4 flex items-center justify-center rounded text-zinc-400 group-hover:text-zinc-700 transition-transform"
                                                :class="isFolderExpanded(child.id) ? 'rotate-90' : ''"
                                                x-show="child.children && child.children.length > 0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <span class="w-4 h-4" x-show="!child.children || child.children.length === 0"></span>

                                        <svg class="w-3.5 h-3.5 text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                                        </svg>

                                        <span class="font-medium text-xs text-zinc-800 truncate" x-text="child.name"></span>
                                        <template x-if="child.code">
                                            <span class="px-1 py-0.2 rounded text-[8.5px] font-mono bg-zinc-100 text-zinc-500" x-text="child.code"></span>
                                        </template>

                                        <template x-if="child.responsible">
                                            <span class="hidden md:inline text-[10.5px] text-zinc-400 font-sans truncate max-w-[180px]"
                                                  x-text="'• ' + child.responsible.name"></span>
                                        </template>
                                    </div>

                                    <div class="flex items-center gap-2.5 shrink-0" @click.stop>
                                        <template x-if="child.children && child.children.length > 0">
                                            <span class="text-[9.5px] text-zinc-400 font-mono" x-text="child.children.length + ' sub-áreas'"></span>
                                        </template>

                                        <template x-if="child.employees_count > 0">
                                            <span class="hidden sm:inline-flex items-center gap-1 text-[9.5px] font-mono text-zinc-400">
                                                <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                                <span x-text="child.employees_count"></span>
                                            </span>
                                        </template>

                                        @can('org-unit.create')
                                        <button type="button"
                                                @click="openCreateModal(child.id, child.name)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-medium bg-zinc-100 hover:bg-zinc-900 text-zinc-700 hover:text-white transition-colors border border-zinc-200">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span>+ Sub-área</span>
                                        </button>
                                        @endcan

                                        <a :href="child.show_url" class="p-1 rounded text-zinc-400 hover:text-zinc-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>

                                </div>

                                {{-- Nivel 3 Recursivo --}}
                                <div x-show="isFolderExpanded(child.id) && child.children && child.children.length > 0"
                                     class="pl-6 border-l-2 border-zinc-100 ml-4 space-y-1 my-1">
                                    <template x-for="subchild in child.children" :key="subchild.id">
                                        <div class="flex items-center justify-between p-1.5 rounded hover:bg-zinc-50 text-xs">
                                            <div class="flex items-center gap-2 min-w-0 pr-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span>
                                                <span class="text-zinc-700 truncate" x-text="subchild.name"></span>
                                                <template x-if="subchild.code">
                                                    <span class="text-[8.5px] font-mono text-zinc-400" x-text="subchild.code"></span>
                                                </template>
                                                <template x-if="subchild.responsible">
                                                    <span class="hidden md:inline text-[10px] text-zinc-400 font-sans truncate"
                                                          x-text="'• ' + subchild.responsible.name"></span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                @can('org-unit.create')
                                                <button type="button"
                                                        @click="openCreateModal(subchild.id, subchild.name)"
                                                        class="text-[9px] px-1 py-0.5 rounded bg-zinc-100 hover:bg-zinc-900 text-zinc-600 hover:text-white">
                                                    + Sub-área
                                                </button>
                                                @endcan
                                                <a :href="subchild.show_url" class="text-zinc-400 hover:text-zinc-700 text-[10px]">Ver</a>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                            </div>
                        </template>
                    </div>

                </div>
            </template>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- VISTA 2: GRAFO / MAPA VISUAL EN CÍRCULOS (INTERCONECTADOS Y ESTÉTICOS)    --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'circles'"
         x-ref="canvasContainer"
         class="flex-1 relative bg-zinc-50/70 rounded-2xl border border-zinc-200/90 overflow-hidden flex flex-col">

        {{-- Barra de Controles Flotante (Búsqueda + Niveles + Zoom) --}}
        <div class="absolute top-3 left-3 right-3 z-30 flex items-center justify-between gap-2.5 pointer-events-none">
            {{-- Buscador en Vivo --}}
            <div class="pointer-events-auto relative w-64 sm:w-72 shadow-xs">
                <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text"
                       x-model="searchQuery"
                       @input="onSearch()"
                       placeholder="Buscar en el mapa de círculos..."
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
                <button type="button"
                        @click="expandLevel(1)"
                        :class="currentLevel === 1 ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100'"
                        class="px-2 py-1 rounded-md text-[11px] transition-colors"
                        title="Solo nivel principal">
                    Nivel 1
                </button>
                <button type="button"
                        @click="expandLevel(2)"
                        :class="currentLevel === 2 ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100'"
                        class="px-2 py-1 rounded-md text-[11px] transition-colors"
                        title="Hasta nivel 2">
                    Nivel 2
                </button>
                <button type="button"
                        @click="expandAll()"
                        class="px-2 py-1 rounded-md text-[11px] text-zinc-600 hover:bg-zinc-100 transition-colors">
                    Expandir
                </button>

                <div class="h-4 w-px bg-zinc-200 mx-0.5"></div>

                <button type="button"
                        @click="fitToScreen()"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-md bg-zinc-100 hover:bg-zinc-200 text-zinc-800 font-semibold text-[11px] transition-colors"
                        title="Ajustar árbol a la pantalla">
                    <svg class="w-3.5 h-3.5 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                    <span>Ajustar</span>
                </button>

                <button type="button" @click="zoomIn()" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-zinc-100 text-zinc-700 font-bold">+</button>
                <button type="button" @click="zoomOut()" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-zinc-100 text-zinc-700 font-bold">&minus;</button>
            </div>
        </div>

        {{-- LIENZO INTERACTIVO CON NODOS CIRCULARES --}}
        <div class="flex-1 w-full h-full relative cursor-grab active:cursor-grabbing overflow-hidden"
             @mousedown="startDrag($event)"
             @mousemove="onDrag($event)"
             @mouseup="stopDrag()"
             @mouseleave="stopDrag()"
             @wheel.prevent="onWheel($event)">

            {{-- Fondo de Puntos Tecnológico --}}
            <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-25">
                <defs>
                    <pattern id="circle-dots" width="28" height="28" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="#71717a"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#circle-dots)"/>
            </svg>

            {{-- Contenedor Transformable (Pan y Zoom) --}}
            <div class="absolute origin-top-left transition-transform duration-75"
                 :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom});`">

                {{-- Conexiones SVG Curvas entre Círculos --}}
                <svg class="absolute inset-0 overflow-visible pointer-events-none" style="width: 8000px; height: 5000px;">
                    <template x-for="link in circleLinks" :key="link.id">
                        <path :d="link.path"
                              fill="none"
                              stroke="#cbd5e1"
                              stroke-width="2"
                              stroke-linecap="round"
                              class="transition-all duration-200"
                              :class="{
                                  'stroke-blue-600 stroke-[3px] opacity-100': isBranchHighlighted(link.parentId, link.childId),
                                  'opacity-70': !isBranchHighlighted(link.parentId, link.childId)
                              }"/>
                    </template>
                </svg>

                {{-- Círculos Relacionados (Nodos del Grafo) --}}
                <template x-for="node in circleNodes" :key="node.id">
                    <div class="absolute -translate-x-1/2 -translate-y-1/2 flex flex-col items-center cursor-pointer transition-all duration-200 group"
                         :style="`left: ${node.x}px; top: ${node.y}px;`"
                         @mouseenter="highlightNode(node.id)"
                         @mouseleave="clearHighlight()"
                         @click="openInspector(node.id)">

                        {{-- Círculo Principal --}}
                        <div class="relative flex items-center justify-center rounded-full font-bold transition-transform group-hover:scale-105 shadow-sm"
                             :class="{
                                 // Nivel 1: Círculo Negro Prestigio (56px)
                                 'w-14 h-14 bg-zinc-950 text-white border-2 border-zinc-900 ring-4 ring-zinc-900/10': node.depth === 1,
                                 // Nivel 2: Círculo Blanco con Aro Definido (46px)
                                 'w-11 h-11 bg-white text-zinc-900 border-2 border-zinc-800 ring-2 ring-zinc-500/15': node.depth === 2,
                                 // Nivel 3+: Círculo Ligero (38px)
                                 'w-9 h-9 bg-white text-zinc-700 border-2 border-zinc-300': node.depth >= 3,
                                 // Seleccionado
                                 'ring-4 ring-zinc-900 ring-offset-2': activeNodeId === node.id,
                                 // Coincidencia de búsqueda
                                 'ring-4 ring-amber-500 animate-pulse': isMatch(node.id)
                             }">

                            {{-- Iniciales / Código dentro del Círculo --}}
                            <span class="font-mono uppercase tracking-tighter"
                                  :class="{
                                      'text-xs font-black': node.depth === 1,
                                      'text-[10px] font-bold': node.depth === 2,
                                      'text-[8.5px] font-semibold': node.depth >= 3
                                  }"
                                  x-text="node.code ? node.code.substring(0, 4) : node.name.substring(0, 2).toUpperCase()"></span>

                            {{-- Badge de Subcarpetas / Sub-áreas (Expandir / Colapsar) --}}
                            <template x-if="node.hasChildren">
                                <button type="button"
                                        @click.stop="toggleBranch(node.id)"
                                        class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-zinc-900 text-white font-mono font-bold text-[8.5px] flex items-center justify-center hover:scale-125 transition-transform shadow-2xs border border-white"
                                        :title="node.isExpanded ? 'Colapsar sub-áreas' : 'Expandir ' + node.childrenCount + ' sub-áreas'">
                                    <span x-text="node.isExpanded ? '−' : '+' + node.childrenCount"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Etiqueta de Nombre Debajo del Círculo --}}
                        <div class="mt-1.5 text-center max-w-[170px] pointer-events-auto">
                            <h4 class="text-[11px] font-semibold text-zinc-900 leading-tight line-clamp-2 group-hover:text-blue-700 transition-colors"
                                x-text="node.name"></h4>

                            {{-- Encargado / Titular --}}
                            <template x-if="node.responsible">
                                <p class="text-[9.5px] text-zinc-500 truncate mt-0.5" x-text="node.responsible.name"></p>
                            </template>
                            <template x-if="!node.responsible">
                                <p class="text-[9px] text-zinc-400 italic mt-0.5">Sin titular</p>
                            </template>
                        </div>

                    </div>
                </template>

            </div>
        </div>

        {{-- PANEL LATERAL INSPECTOR (SLIDE-OVER AL TOCAR UN CÍRCULO O CARPETA) --}}
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
                
                {{-- Botón Destacado: Añadir Sub-área / Subcarpeta aquí --}}
                @can('org-unit.create')
                <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-blue-950 text-xs">Crear sub-área dependiente</p>
                        <p class="text-[10px] text-blue-700 mt-0.5">Añadir una subcarpeta dentro de esta oficina</p>
                    </div>
                    <button type="button"
                            @click="openCreateModal(selectedUnit.id, selectedUnit.name)"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-2xs shrink-0">
                        + Sub-área
                    </button>
                </div>
                @endcan

                {{-- Responsable Titular --}}
                <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block mb-1.5">Responsable Titular / Encargado</span>
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

                {{-- Métricas Organizacionales --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-[10px] uppercase font-bold text-zinc-400 block">Sub-áreas</span>
                        <span class="text-xl font-bold font-mono text-zinc-900 mt-0.5 block"
                              x-text="selectedUnit && selectedUnit.children ? selectedUnit.children.length : 0"></span>
                        <span class="text-[10px] text-zinc-400">dependencias hijas</span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-[10px] uppercase font-bold text-zinc-400 block">Personal</span>
                        <span class="text-xl font-bold font-mono text-zinc-900 mt-0.5 block"
                              x-text="selectedUnit && selectedUnit.employees ? selectedUnit.employees.length : 0"></span>
                        <span class="text-[10px] text-zinc-400">servidores adscritos</span>
                    </div>
                </div>

                {{-- Subunidades directas --}}
                <template x-if="selectedUnit && selectedUnit.children && selectedUnit.children.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Subcarpetas / Dependencias Hijas (' + selectedUnit.children.length + ')'"></span>
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
                        <div class="space-y-1 max-h-44 overflow-y-auto pr-1">
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
    {{-- VISTA 3: DIRECTORIO TABULAR (LISTA CON FILTROS Y PAGINACIÓN)               --}}
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
                            <th class="py-3 px-4">Responsable / Titular</th>
                            <th class="py-3 px-4 text-center">Personal</th>
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
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 text-[11px] {{ $unit->is_active ? 'text-zinc-700' : 'text-zinc-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $unit->is_active ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                    {{ $unit->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('organizational-units.show', $unit) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors inline-flex items-center gap-1">
                                    <span>Ver</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-zinc-400">
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

    {{-- ========================================================================= --}}
    {{-- MODAL RÁPIDO PARA CREAR CARPETA / SUB-ÁREA (COMO CARPETAS Y SUBCARPETAS)   --}}
    {{-- ========================================================================= --}}
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-zinc-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-2xl border border-zinc-200 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="closeCreateModal()">
            
            <div class="p-4 border-b border-zinc-100 bg-zinc-50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900" x-text="modalParentId ? 'Nueva Sub-área Dependiente' : 'Nueva Dependencia Principal (Raíz)'"></h3>
                    <p class="text-[11px] text-zinc-500 mt-0.5 truncate max-w-xs" x-text="modalParentId ? 'Dentro de: ' + modalParentName : 'Se creará en la raíz de la institución'"></p>
                </div>
                <button type="button" @click="closeCreateModal()" class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-200/50">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('organizational-units.store') }}" class="p-4 space-y-3.5">
                @csrf
                <input type="hidden" name="parent_id" :value="modalParentId">

                {{-- Nombre de la Dependencia --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-800 mb-1">Nombre de la Oficina o Área <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           x-model="modalName"
                           @input="onNameInput()"
                           required
                           placeholder="Ej. Gerencia de Infraestructura, Subgerencia de Rentas..."
                           class="w-full px-3 py-2 bg-zinc-50 focus:bg-white rounded-xl border border-zinc-200 text-xs text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                </div>

                {{-- Código y Tipo en dos columnas --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-800 mb-1">Sigla / Código</label>
                        <input type="text"
                               name="code"
                               x-model="modalCode"
                               placeholder="Auto o ej. SGR"
                               class="w-full px-3 py-2 bg-zinc-50 focus:bg-white rounded-xl border border-zinc-200 text-xs font-mono text-zinc-900 uppercase focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        <span class="text-[9.5px] text-zinc-400 mt-0.5 block">Se genera solo si se deja vacío</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-800 mb-1">Tipo de Órgano</label>
                        <select name="type"
                                x-model="modalType"
                                class="w-full px-3 py-2 bg-zinc-50 focus:bg-white rounded-xl border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            @foreach ($unitTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Responsable Titular (Opcional) --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-800 mb-1">Responsable Titular (Opcional)</label>
                    <select name="responsible_employee_id"
                            class="w-full px-3 py-2 bg-zinc-50 focus:bg-white rounded-xl border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        <option value="">Sin asignar por ahora</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} {{ $emp->position ? '(' . $emp->position . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Botones de Acción --}}
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button type="button"
                            @click="closeCreateModal()"
                            class="px-3 py-2 rounded-xl text-xs font-medium text-zinc-600 hover:bg-zinc-100 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                        Crear Dependencia
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

{{-- SCRIPT: WORKSPACE INTEGRADO (CARPETAS + GRAFO EN CÍRCULOS + INSPECTOR) --}}
<script>
    function orgWorkspace(treeData) {
        return {
            viewMode: 'folders', // 'folders' | 'circles' | 'table'
            rawTree: treeData || [],

            // Estado de Carpetas (Explorador)
            folderExpanded: {},
            folderFilter: '',

            // Estado del Grafo en Círculos
            panX: 60,
            panY: 50,
            zoom: 0.85,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            circleNodes: [],
            circleLinks: [],
            currentLevel: 2,
            activeNodeId: null,
            highlightedBranch: [],

            // Slide-over Inspector
            inspectorOpen: false,
            selectedUnit: null,

            // Búsqueda en Grafo
            searchQuery: '',
            matchedIds: [],

            // Modal Rápido de Creación de Subcarpeta
            createModalOpen: false,
            modalParentId: null,
            modalParentName: '',
            modalName: '',
            modalCode: '',
            modalType: 'oficina',

            init() {
                // Iniciar con las primeras carpetas abiertas
                if (this.rawTree.length > 0) {
                    for (const root of this.rawTree) {
                        this.folderExpanded[root.id] = true;
                    }
                }

                // Generar layout de círculos
                this.recalculateCircleLayout();
            },

            // ==========================================
            // MÉTODOS DE VISTA DE CARPETAS (ESTRUCTURADOR)
            // ==========================================
            isFolderExpanded(id) {
                return !!this.folderExpanded[id];
            },

            toggleFolder(id) {
                this.folderExpanded[id] = !this.folderExpanded[id];
            },

            expandAllFolders() {
                const mark = (list) => {
                    for (const item of list) {
                        this.folderExpanded[item.id] = true;
                        if (item.children) mark(item.children);
                    }
                };
                mark(this.rawTree);
            },

            collapseAllFolders() {
                this.folderExpanded = {};
            },

            // ==========================================
            // MÉTODOS DE VISTA GRAFO EN CÍRCULOS
            // ==========================================
            recalculateCircleLayout() {
                let slotCounter = 0;
                const nodes = [];
                const links = [];

                const slotWidth = 190;
                const levelHeight = 125;

                const traverse = (node, depth, parentPos = null) => {
                    const isExpanded = !!this.folderExpanded[node.id];
                    const hasChildren = node.children && node.children.length > 0;
                    const showChildren = isExpanded && hasChildren;

                    const y = 50 + (depth - 1) * levelHeight;
                    let x = 0;

                    if (showChildren) {
                        const childrenX = [];
                        for (const child of node.children) {
                            const childPos = traverse(child, depth + 1, { id: node.id, x: 0, y: y });
                            childrenX.push(childPos.x);
                        }
                        x = (childrenX[0] + childrenX[childrenX.length - 1]) / 2;
                    } else {
                        x = 60 + slotCounter * slotWidth;
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

                    if (parentPos) {
                        const x1 = parentPos.x;
                        const y1 = parentPos.y + 26;
                        const x2 = x;
                        const y2 = y - 26;
                        const cy = (y1 + y2) / 2;
                        const path = `M ${x1} ${y1} C ${x1} ${cy}, ${x2} ${cy}, ${x2} ${y2}`;

                        links.push({
                            id: `link-${parentPos.id}->${node.id}`,
                            parentId: parentPos.id,
                            childId: node.id,
                            path
                        });
                    }

                    return pos;
                };

                for (const root of this.rawTree) {
                    traverse(root, 1, null);
                }

                this.circleNodes = nodes;
                this.circleLinks = links;
            },

            expandLevel(maxLevel) {
                this.currentLevel = maxLevel;
                const newExp = {};
                const mark = (list, depth) => {
                    for (const item of list) {
                        if (depth < maxLevel) newExp[item.id] = true;
                        if (item.children) mark(item.children, depth + 1);
                    }
                };
                mark(this.rawTree, 1);
                this.folderExpanded = newExp;
                this.recalculateCircleLayout();
                this.$nextTick(() => this.fitToScreen());
            },

            toggleBranch(nodeId) {
                this.folderExpanded[nodeId] = !this.folderExpanded[nodeId];
                this.recalculateCircleLayout();
            },

            expandAll() {
                this.expandAllFolders();
                this.recalculateCircleLayout();
                this.$nextTick(() => this.fitToScreen());
            },

            fitToScreen() {
                if (!this.circleNodes || this.circleNodes.length === 0) return;
                const container = this.$refs.canvasContainer;
                if (!container) return;

                let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
                for (const node of this.circleNodes) {
                    if (node.x < minX) minX = node.x;
                    if (node.x > maxX) maxX = node.x;
                    if (node.y < minY) minY = node.y;
                    if (node.y > maxY) maxY = node.y;
                }

                const pad = 80;
                const contentW = (maxX - minX) + pad * 2;
                const contentH = (maxY - minY) + pad * 2;

                const cWidth = container.clientWidth || 1100;
                const cHeight = container.clientHeight || 650;

                const scaleX = cWidth / contentW;
                const scaleY = cHeight / contentH;
                this.zoom = Math.min(Math.max(Math.min(scaleX, scaleY), 0.25), 1.15);

                this.panX = (cWidth - contentW * this.zoom) / 2 - (minX - pad) * this.zoom;
                this.panY = (cHeight - contentH * this.zoom) / 2 - (minY - pad) * this.zoom;
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
                const factor = e.deltaY < 0 ? 1.08 : 0.92;
                this.zoom = Math.min(Math.max(this.zoom * factor, 0.25), 2.0);
            },

            highlightNode(nodeId) {
                const branch = [nodeId];
                let cur = this.circleLinks.find(l => l.childId === nodeId);
                while (cur) {
                    branch.push(cur.parentId);
                    cur = this.circleLinks.find(l => l.childId === cur.parentId);
                }
                this.highlightedBranch = branch;
            },

            clearHighlight() {
                this.highlightedBranch = [];
            },

            isBranchHighlighted(parentId, childId) {
                return this.highlightedBranch.includes(parentId) && this.highlightedBranch.includes(childId);
            },

            onSearch() {
                const q = (this.searchQuery || '').trim().toLowerCase();
                if (q.length < 2) {
                    this.matchedIds = [];
                    return;
                }

                const matches = [];
                const search = (list, chain = []) => {
                    for (const item of list) {
                        const name = (item.name || '').toLowerCase();
                        const code = (item.code || '').toLowerCase();
                        if (name.includes(q) || code.includes(q)) {
                            matches.push(item.id);
                            for (const p of chain) this.folderExpanded[p] = true;
                        }
                        if (item.children) search(item.children, [...chain, item.id]);
                    }
                };
                search(this.rawTree);
                this.matchedIds = matches;
                this.recalculateCircleLayout();
                if (matches.length > 0) {
                    setTimeout(() => this.centerOnNode(matches[0]), 100);
                }
            },

            isMatch(id) {
                return this.matchedIds.includes(id);
            },

            centerOnNode(id) {
                const node = this.circleNodes.find(n => n.id === id);
                const container = this.$refs.canvasContainer;
                if (node && container) {
                    this.panX = (container.clientWidth / 2) - node.x * this.zoom;
                    this.panY = (container.clientHeight / 2) - node.y * this.zoom;
                }
            },

            // ==========================================
            // INSPECTOR SLIDE-OVER
            // ==========================================
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
            },

            // ==========================================
            // MODAL RÁPIDO DE CREACIÓN DE SUB-ÁREA
            // ==========================================
            openCreateModal(parentId = null, parentName = '') {
                this.modalParentId = parentId;
                this.modalParentName = parentName;
                this.modalName = '';
                this.modalCode = '';
                this.modalType = parentId ? 'subgerencia' : 'alcaldia';
                this.createModalOpen = true;
            },

            closeCreateModal() {
                this.createModalOpen = false;
            },

            onNameInput() {
                const n = this.modalName.trim().toUpperCase();
                // Auto sugerir tipo según nombre
                if (n.startsWith('GERENCIA MUNICIPAL')) this.modalType = 'gerencia_municipal';
                else if (n.startsWith('GERENCIA')) this.modalType = 'gerencia';
                else if (n.startsWith('SUBGERENCIA') || n.startsWith('SUB GERENCIA')) this.modalType = 'subgerencia';
                else if (n.startsWith('OFICINA GENERAL')) this.modalType = 'oficina_general';
                else if (n.startsWith('OFICINA')) this.modalType = 'oficina';
                else if (n.startsWith('SECRETAR')) this.modalType = 'secretaria';
                else if (n.startsWith('AREA')) this.modalType = 'area';
                else if (n.startsWith('UNIDAD')) this.modalType = 'unidad';
                else if (n.startsWith('PROYECTO')) this.modalType = 'proyecto';

                // Auto sugerir código desde iniciales si no ha sido editado
                const words = n.split(/\s+/).filter(w => w.length > 2 && !['DE', 'LA', 'LOS', 'LAS', 'DEL', 'Y', 'EN', 'PARA'].includes(w));
                if (words.length > 0) {
                    this.modalCode = words.map(w => w[0]).join('');
                }
            }
        };
    }
</script>

</x-layouts.app>
