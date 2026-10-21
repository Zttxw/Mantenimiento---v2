<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navegación Principal SIGAT - 4 Macro-Módulos
    |--------------------------------------------------------------------------
    | Estructura unificada para la barra lateral y accesos rápidos.
    */

    'modules' => [
        'soporte' => [
            'key' => 'soporte',
            'label' => 'Soporte y Tickets',
            'icon' => 'clipboard',
            'description' => 'Mesa de ayuda e incidencias',
            'items' => [
                ['label' => 'Cola de Trabajo', 'route' => 'tecnico.work-queue', 'description' => 'Casos asignados y pendientes'],
                ['label' => 'Atender Equipo', 'route' => 'tecnico.attend-asset', 'description' => 'Diagnóstico y atención técnica'],
                ['label' => 'Todos los Casos', 'route' => 'maintenance-cases.index', 'description' => 'Historial completo de tickets'],
            ],
        ],
        'campanas' => [
            'key' => 'campanas',
            'label' => 'Campañas Preventivas',
            'icon' => 'wrench',
            'description' => 'Mantenimiento preventivo masivo',
            'items' => [
                ['label' => 'Campañas y Cronograma', 'route' => 'campaigns.index', 'description' => 'Planificación y seguimiento'],
                ['label' => 'Plantillas de Tareas', 'route' => 'maintenance-templates.index', 'description' => 'Checklists y paquetes de servicio'],
            ],
        ],
        'inventario' => [
            'key' => 'inventario',
            'label' => 'Inventario y Recursos',
            'icon' => 'cube',
            'description' => 'Equipos, agentes y personal',
            'items' => [
                ['label' => 'Inventario de Activos', 'route' => 'assets.index', 'description' => 'Computadoras, laptops y equipos'],
                ['label' => 'Monitor de PCs (Agentes)', 'route' => 'assets.pc-monitor', 'description' => 'Telemetría y software en tiempo real'],
                ['label' => 'Movimientos y Traslados', 'route' => 'asset-movements.index', 'description' => 'Custodias y transferencias'],
                ['label' => 'Oficinas y Unidades', 'route' => 'organizational-units.index', 'description' => 'Estructura institucional'],
                ['label' => 'Personal / Empleados', 'route' => 'employees.index', 'description' => 'Directorio de trabajadores'],
            ],
        ],
        'gestion' => [
            'key' => 'gestion',
            'label' => 'Reportes y Control',
            'icon' => 'document',
            'description' => 'Documentación oficial y accesos',
            'items' => [
                ['label' => 'Reportes y Actas Oficiales', 'route' => 'documents.index', 'description' => 'Informes técnicos y actas firmadas'],
                ['label' => 'Usuarios del Sistema', 'route' => 'users.index', 'description' => 'Cuentas y personal autorizado', 'role' => 'coordinador_oti|admin'],
                ['label' => 'Roles y Permisos', 'route' => 'admin.roles.index', 'description' => 'Seguridad y niveles de acceso', 'role' => 'coordinador_oti|admin'],
                ['label' => 'Configuración', 'route' => 'settings.index', 'description' => 'Parámetros del sistema', 'permission' => 'setting.view'],
                ['label' => 'Agentes de Monitoreo', 'route' => 'agents.index', 'description' => 'Dispositivos cliente vinculados', 'permission' => 'setting.view'],
            ],
        ],
    ],

    'command_palette_actions' => [
        ['label' => 'Ir al Dashboard', 'desc' => 'Panel de control principal', 'icon' => 'squares', 'route' => 'dashboard'],
        ['label' => 'Cola de trabajo', 'desc' => 'Casos y tickets pendientes', 'icon' => 'clipboard', 'route' => 'tecnico.work-queue'],
        ['label' => 'Atender equipo', 'desc' => 'Diagnóstico rápido de PC', 'icon' => 'wrench', 'route' => 'tecnico.attend-asset'],
        ['label' => 'Nuevo activo', 'desc' => 'Registrar equipo informático', 'icon' => 'cube', 'route' => 'assets.create'],
        ['label' => 'Nueva campaña', 'desc' => 'Crear campaña preventiva', 'icon' => 'wrench', 'route' => 'campaigns.create'],
        ['label' => 'Buscar en inventario', 'desc' => 'Consultar activos y PCs', 'icon' => 'cube', 'route' => 'assets.index'],
        ['label' => 'Oficinas y dependencias', 'desc' => 'Directorio de unidades institucionales', 'icon' => 'cube', 'route' => 'organizational-units.index'],
        ['label' => 'Personal y responsables', 'desc' => 'Directorio de trabajadores y custodios', 'icon' => 'clipboard', 'route' => 'employees.index'],
        ['label' => 'Ver actas e informes', 'desc' => 'Centro de documentos oficiales', 'icon' => 'document', 'route' => 'documents.index'],
    ],
];
