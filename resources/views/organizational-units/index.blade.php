<x-layouts.app title="Estructura Organizacional" moduleLabel="Organización" pageTitle="Estructura Institucional">

<div x-data="orgSingleScreen()" x-init="init()" class="flex flex-col h-[calc(100vh-95px)] overflow-hidden">

    {{-- Encabezado Compacto de Una Sola Fila --}}
    <div class="flex items-center justify-between gap-4 pb-3 mb-3 border-b border-zinc-200/80 shrink-0">
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

        {{-- Switch de Vistas y Botón de Nueva Unidad --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- Toggle Árbol / Tabla --}}
            <div class="inline-flex items-center p-0.5 rounded-lg bg-zinc-100 border border-zinc-200 text-xs">
                <button type="button"
                        @click="viewMode = 'tree'"
                        :class="viewMode === 'tree' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                    <span>Árbol</span>
                </button>
                <button type="button"
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 011.875 1.875v11.25a1.875 1.875 0 01-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V6.375A1.875 1.875 0 015.625 4.5z"/></svg>
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
    {{-- MODO 1: ESPACIO DE TRABAJO EN UNA SOLA PANTALLA (ÁRBOL + FICHA EN VIVO)   --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'tree'" class="flex-1 flex gap-4 overflow-hidden">

        {{-- PANEL IZQUIERDO: Explorador de Árbol Jerárquico (370px) --}}
        <div class="w-[370px] shrink-0 bg-white rounded-xl border border-zinc-200/80 shadow-xs flex flex-col overflow-hidden">

            {{-- Buscador y Botones de Control --}}
            <div class="p-2.5 border-b border-zinc-200/80 space-y-2 bg-zinc-50/50 shrink-0">
                <div class="relative">
                    <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text"
                           x-model="searchTree"
                           @input="onSearchTree()"
                           placeholder="Filtrar por oficina, código o titular..."
                           class="w-full pl-8 pr-7 py-1.5 bg-white rounded-md border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                    <button type="button"
                            x-show="searchTree.length > 0"
                            @click="searchTree = ''; onSearchTree()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs">
                        ✕
                    </button>
                </div>

                {{-- Acciones Rápidas del Árbol --}}
                <div class="flex items-center justify-between text-[11px] text-zinc-600">
                    <div class="flex items-center gap-1">
                        <button type="button" @click="expandAll()" class="px-1.5 py-0.5 rounded hover:bg-zinc-200/70 transition-colors">
                            Expandir
                        </button>
                        <span class="text-zinc-300">&bull;</span>
                        <button type="button" @click="collapseAll()" class="px-1.5 py-0.5 rounded hover:bg-zinc-200/70 transition-colors">
                            Colapsar
                        </button>
                        <span class="text-zinc-300">&bull;</span>
                        <button type="button" @click="expandToLevel(2)" class="px-1.5 py-0.5 rounded hover:bg-zinc-200/70 transition-colors" title="Hasta Gerencias">
                            Gerencias
                        </button>
                    </div>

                    <span x-show="matchesCount > 0" class="text-[10px] font-mono text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                        <span x-text="matchesCount"></span> encontradas
                    </span>
                </div>
            </div>

            {{-- Lista del Árbol Compacto con Scroll Independiente --}}
            <div class="flex-1 overflow-y-auto p-2 space-y-0.5 custom-scrollbar text-xs">
                @foreach ($treeData as $rootNode)
                    @include('organizational-units.partials.compact-tree-item', ['node' => $rootNode, 'depth' => 1])
                @endforeach
            </div>

        </div>

        {{-- PANEL DERECHO: Ficha en Vivo de la Unidad Seleccionada (flex-1) --}}
        <div class="flex-1 bg-white rounded-xl border border-zinc-200/80 shadow-xs flex flex-col overflow-hidden">

            {{-- Estado de Carga --}}
            <div x-show="loading" class="flex-1 flex items-center justify-center text-zinc-400 text-xs gap-2">
                <svg class="w-4 h-4 animate-spin text-zinc-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Cargando datos de la dependencia...</span>
            </div>

            {{-- Vista de Detalle de la Oficina --}}
            <div x-show="!loading && currentUnit" class="flex-1 flex flex-col overflow-hidden">

                {{-- Barra Superior de la Unidad Seleccionada --}}
                <div class="p-4 border-b border-zinc-200/80 bg-zinc-50/50 flex items-center justify-between gap-4 shrink-0">
                    <div class="min-w-0">
                        {{-- Ruta jerárquica / Breadcrumbs --}}
                        <div class="flex items-center gap-1.5 text-[11px] text-zinc-400 mb-1 truncate">
                            <span x-text="currentUnit ? currentUnit.full_path : ''" class="truncate"></span>
                        </div>

                        {{-- Nombre y Badges --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-bold text-zinc-900 tracking-tight truncate" x-text="currentUnit ? currentUnit.name : ''"></h2>
                            <template x-if="currentUnit && currentUnit.code">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-zinc-100 text-zinc-700 border border-zinc-200"
                                      x-text="currentUnit.code"></span>
                            </template>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-200/70 text-zinc-700 font-semibold"
                                  x-text="currentUnit ? currentUnit.type_label : ''"></span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium"
                                  :class="currentUnit && currentUnit.is_active ? 'text-emerald-700' : 'text-zinc-400'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="currentUnit && currentUnit.is_active ? 'bg-emerald-500' : 'bg-zinc-400'"></span>
                                <span x-text="currentUnit && currentUnit.is_active ? 'Activa' : 'Inactiva'"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Botones de Acción --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <a :href="currentUnit ? currentUnit.show_url : '#'"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                            <span>Ver Ficha Completa</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        @can('org-unit.edit')
                        <a :href="currentUnit ? currentUnit.edit_url : '#'"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                            <span>Editar</span>
                        </a>
                        @endcan
                    </div>
                </div>

                {{-- Contenido de la Ficha (Scrollable con diseño minimalista) --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-5 custom-scrollbar text-xs">

                    {{-- Ficha del Titular y Métricas Rápidas --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {{-- Responsable Titular --}}
                        <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/70 md:col-span-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block mb-2">Responsable Asignado</span>
                            <template x-if="currentUnit && currentUnit.responsible">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-zinc-900 text-white font-bold text-xs flex items-center justify-center shrink-0"
                                         x-text="currentUnit.responsible.name.charAt(0)"></div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-zinc-900 truncate" x-text="currentUnit.responsible.name"></p>
                                        <p class="text-[11px] text-zinc-500 truncate" x-text="currentUnit.responsible.position || 'Funcionario a cargo'"></p>
                                        <p class="text-[10px] text-zinc-400 font-mono" x-text="'DNI ' + currentUnit.responsible.dni"></p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="currentUnit && !currentUnit.responsible">
                                <p class="text-xs italic text-zinc-400 py-1">Sin responsable asignado actualmente.</p>
                            </template>
                        </div>

                        {{-- Personal Adscrito --}}
                        <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/70 flex flex-col justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Personal Registrado</span>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold font-mono text-zinc-900" x-text="currentUnit && currentUnit.employees ? currentUnit.employees.length : 0"></span>
                                <span class="text-xs text-zinc-500">trabajadores adscritos</span>
                            </div>
                        </div>

                        {{-- Activos OTI --}}
                        <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/70 flex flex-col justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Activos Tecnológicos</span>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold font-mono text-blue-700" x-text="currentUnit && currentUnit.assets ? currentUnit.assets.length : 0"></span>
                                <span class="text-xs text-zinc-500">equipos en custodia OTI</span>
                            </div>
                        </div>
                    </div>

                    {{-- Subunidades Dependientes Directas --}}
                    <div x-show="currentUnit && currentUnit.children && currentUnit.children.length > 0">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                                Subunidades Dependientes (<span x-text="currentUnit ? currentUnit.children.length : 0"></span>)
                            </h3>
                            <span class="text-[10px] text-zinc-400">Haz clic en cualquiera para navegar en el árbol</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            <template x-for="child in (currentUnit ? currentUnit.children : [])" :key="child.id">
                                <div @click="selectUnit(child.id)"
                                     class="p-2.5 rounded-lg border border-zinc-200/70 bg-white hover:bg-zinc-50 hover:border-zinc-300 transition-all cursor-pointer flex items-center justify-between gap-2 group">
                                    <div class="min-w-0">
                                        <p class="font-medium text-zinc-800 group-hover:text-zinc-950 truncate" x-text="child.name"></p>
                                        <p class="text-[10px] font-mono text-zinc-400" x-text="child.type_label"></p>
                                    </div>
                                    <span class="text-zinc-300 group-hover:text-zinc-700 text-xs">&rarr;</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Personal Adscrito a la Dependencia --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                                Directorio de Personal (<span x-text="currentUnit && currentUnit.employees ? currentUnit.employees.length : 0"></span>)
                            </h3>
                        </div>

                        <template x-if="currentUnit && currentUnit.employees && currentUnit.employees.length > 0">
                            <div class="rounded-xl border border-zinc-200/80 overflow-hidden bg-white">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-zinc-50/70 border-b border-zinc-200/80 text-[10px] font-semibold uppercase text-zinc-400 tracking-wider">
                                        <tr>
                                            <th class="py-2 px-3">Trabajador</th>
                                            <th class="py-2 px-3">Cargo</th>
                                            <th class="py-2 px-3">DNI</th>
                                            <th class="py-2 px-3 text-right">Ficha</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200/60">
                                        <template x-for="emp in currentUnit.employees" :key="emp.id">
                                            <tr class="hover:bg-zinc-50/70 transition-colors">
                                                <td class="py-2 px-3 font-medium text-zinc-900">
                                                    <span x-text="emp.name"></span>
                                                    <template x-if="emp.is_technician">
                                                        <span class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] bg-amber-50 text-amber-700 border border-amber-200">Técnico OTI</span>
                                                    </template>
                                                </td>
                                                <td class="py-2 px-3 text-zinc-500" x-text="emp.position || '—'"></td>
                                                <td class="py-2 px-3 font-mono text-zinc-600" x-text="emp.dni"></td>
                                                <td class="py-2 px-3 text-right">
                                                    <a :href="emp.show_url" class="text-zinc-500 hover:text-zinc-900 text-[11px] font-medium">Ver &rarr;</a>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="currentUnit && (!currentUnit.employees || currentUnit.employees.length === 0)">
                            <div class="p-4 rounded-xl bg-zinc-50 border border-dashed border-zinc-200 text-center text-zinc-400 text-xs">
                                No hay personal registrado directamente en esta oficina.
                            </div>
                        </template>
                    </div>

                    {{-- Equipos y Activos OTI --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                                Equipos Informáticos y Activos (<span x-text="currentUnit && currentUnit.assets ? currentUnit.assets.length : 0"></span>)
                            </h3>
                        </div>

                        <template x-if="currentUnit && currentUnit.assets && currentUnit.assets.length > 0">
                            <div class="rounded-xl border border-zinc-200/80 overflow-hidden bg-white">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-zinc-50/70 border-b border-zinc-200/80 text-[10px] font-semibold uppercase text-zinc-400 tracking-wider">
                                        <tr>
                                            <th class="py-2 px-3">Código</th>
                                            <th class="py-2 px-3">Equipo</th>
                                            <th class="py-2 px-3">Tipo / Marca</th>
                                            <th class="py-2 px-3">Custodio</th>
                                            <th class="py-2 px-3 text-right">Ficha</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200/60">
                                        <template x-for="asset in currentUnit.assets" :key="asset.id">
                                            <tr class="hover:bg-zinc-50/70 transition-colors">
                                                <td class="py-2 px-3 font-mono font-medium text-zinc-900" x-text="asset.internal_code || '—'"></td>
                                                <td class="py-2 px-3 font-medium text-zinc-800" x-text="asset.name"></td>
                                                <td class="py-2 px-3 text-zinc-500" x-text="(asset.type_label || '') + ' ' + (asset.brand || '')"></td>
                                                <td class="py-2 px-3 text-zinc-600" x-text="asset.responsible_name || 'Sin custodio'"></td>
                                                <td class="py-2 px-3 text-right">
                                                    <a :href="asset.show_url" class="text-zinc-500 hover:text-zinc-900 text-[11px] font-medium">Ver &rarr;</a>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="currentUnit && (!currentUnit.assets || currentUnit.assets.length === 0)">
                            <div class="p-4 rounded-xl bg-zinc-50 border border-dashed border-zinc-200 text-center text-zinc-400 text-xs">
                                Sin activos informáticos registrados en esta dependencia.
                            </div>
                        </template>
                    </div>

                </div>

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

{{-- Alpine Controller para Workspace en Una Sola Pantalla --}}
<script>
    function orgSingleScreen() {
        return {
            viewMode: '{{ request()->hasAny(['search', 'type', 'status']) ? 'table' : 'tree' }}',
            searchTree: '',
            matchesCount: 0,
            matchedIds: [],
            expanded: { 1: true, 6: true },
            selectedId: 1, // Por defecto Alcaldía
            currentUnit: null,
            loading: false,

            init() {
                this.expanded[1] = true;
                this.expanded[6] = true;
                this.selectUnit(1);
            },

            toggleExpand(id) {
                this.expanded[id] = !this.expanded[id];
            },

            isExpanded(id) {
                if (this.searchTree.trim().length > 1) {
                    return true;
                }
                return !!this.expanded[id];
            },

            expandAll() {
                const nodes = document.querySelectorAll('.tree-item-wrapper');
                nodes.forEach(el => {
                    const id = parseInt(el.getAttribute('data-node-id'));
                    if (id) this.expanded[id] = true;
                });
            },

            collapseAll() {
                this.expanded = { 1: true };
            },

            expandToLevel(maxLevel) {
                // Expande niveles superiores
                this.expanded = { 1: true, 6: true };
            },

            onSearchTree() {
                const q = this.searchTree.trim().toLowerCase();
                if (q.length < 2) {
                    this.matchedIds = [];
                    this.matchesCount = 0;
                    return;
                }
                const matches = [];
                const nodes = document.querySelectorAll('.tree-item-wrapper');
                nodes.forEach(el => {
                    const name = el.getAttribute('data-node-name') || '';
                    const code = el.getAttribute('data-node-code') || '';
                    const titular = el.getAttribute('data-node-titular') || '';
                    const id = parseInt(el.getAttribute('data-node-id'));
                    if (name.includes(q) || code.includes(q) || titular.includes(q)) {
                        matches.push(id);
                    }
                });
                this.matchedIds = matches;
                this.matchesCount = matches.length;
            },

            isMatch(id) {
                return this.matchedIds.includes(id);
            },

            async selectUnit(id) {
                if (!id) return;
                this.selectedId = id;
                this.loading = true;

                try {
                    const res = await fetch('/organizational-units/' + id + '/quick-info');
                    if (res.ok) {
                        this.currentUnit = await res.json();
                    }
                } catch (e) {
                    console.error('Error loading unit info:', e);
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>

</x-layouts.app>
