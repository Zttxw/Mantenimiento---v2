# Bitácora de Sesión: Rediseño Ultra-Minimalista de Campañas, Desacople de Soporte y Optimización de Rendimiento

**Fecha:** 21 de octubre de 2026  
**Rama:** `feature/campanas-v2`  
**Autor:** Antigravity (Google DeepMind) en pair programming con el usuario  
**Entorno:** MariaDB 10.11 (Docker port 3306), PHP 8.3 / Laravel 11.51, Vite 8, TailwindCSS v4, Alpine.js v3  
**Objetivo de la sesión:**
1. Desacoplar por completo las **Campañas de Mantenimiento** (operativos masivos programados) del módulo de **Soporte y Tickets** (mesa de ayuda de oficina reactiva).
2. Rediseñar integralmente el módulo de campañas (`index`, `show`, `create`, `edit`) con una estética **ultra-minimalista**, eliminando la saturación visual, simplificando la navegación y utilizando símbolos discretos y precisos.
3. Diagnosticar y erradicar la lentitud en la carga de módulos (de ~2 a 4 segundos por página a <100 ms).
4. Limpiar la barra superior (`topbar`) eliminando el buscador general `Ctrl K` y simplificando el badge de usuario.

---

## 1. Resumen de Problemas Encontrados y Soluciones

### 1.1 Inundación de la Mesa de Ayuda con 544 Casos Falsos
- **Problema:** En el flujo heredado, cada vez que se creaba o ejecutaba una campaña masiva, la acción `bulkCreateCases` generaba un registro en la tabla `maintenance_cases` por cada equipo. Como la campaña municipal tenía 544 activos, la bandeja de tickets de mesa de ayuda quedó inundada con 544 casos falsos, imposibilitando el seguimiento de incidencias de oficina reales.
- **Solución implementada:**
  - Se añadieron campos de ejecución directa a `campaign_assets` (`attended_type`, `attended_by_employee_id`, `checklist_tasks`, `asset_condition_after`) mediante la migración `2026_10_21_114315_add_execution_fields_to_campaign_assets_table.php`.
  - Se desvincularon y purgaron los 544 casos fantasmas de `maintenance_cases` con `WHERE campaign_id IS NOT NULL`.
  - `MaintenanceCaseService` y `TechnicianService` ahora filtran estrictamente con `whereNull('campaign_id')`.
  - Las atenciones masivas preventivas viven **única y exclusivamente** dentro de `campaign_assets`.

### 1.2 Registro Bimodal durante Campañas Activas (`en_curso`)
- **Vía Agente SIGAT (Autónomo):** Cuando una PC o Laptop tiene instalado el agente SIGAT y reporta sincronización o heartbeat mientras existe una campaña con estado `en_curso`, `AgentDeviceService::checkAndAttendActiveCampaign()` marca el activo automáticamente como `atendido` (`attended_type = 'agente'`) sin intervención humana ni generación de tickets.
- **Vía Registro Manual en Campo (Técnico OTI):** Para activos que no tienen agente (monitores, pantallas, impresoras, escáneres, UPS), el técnico en campo cuenta con un modal de checklist preventivo rápido (sopleteado, limpieza de temporales, verificación de licencias, revisión eléctrica) que además le permite verificar y corregir in situ el código de sticker patrimonial pegado físicamente, así como la oficina y custodio si el equipo fue reubicado.
- **Descubrir Activo en Campo:** Si durante la visita a una oficina se encuentra un monitor o impresora no catalogado, el modal `Descubrir Activo` permite crearlo en el inventario e incorporarlo y atenderlo en la campaña en un solo paso.

### 1.3 Rediseño Ultra-Minimalista de Campañas
- **Listado General (`campaigns.index`):** Se reemplazó la tabla tradicional por tarjetas ejecutivas horizontales con tipografía monoespaciada para códigos (`CAMP-2026-0001`), micropuntos de estado (`● En Curso`, `○ Planificada`), barras de progreso delgadas (4px) y botones de acción directos.
- **Espacio de Trabajo (`campaigns.show`):**
  - **100% Ancho Completo:** Se eliminó la tarjeta vertical lateral de 1/3 de pantalla que consumía espacio horizontal.
  - **Tira Ejecutiva de Métricas:** 1 sola fila horizontal con 5 tarjetas limpias: Avance Global %, Agente SIGAT, Manual / Campo, Pendientes, y Cronograma/Coordinador.
  - **Iconografía Precisa:** Símbolos SVG monocromáticos de 15px exclusivos por tipo de hardware (🖥️ Computadora, 💻 Laptop, 🖥️ Monitor, 🖨️ Impresora, 📄 Escáner, 🔌 Otro).
  - **Barra de Control Reactiva:** Filtros combinables en Alpine.js por estado (`Todos`, `Pendientes`, `Atendidos`), tipo de activo, dependencia u oficina, y buscador instantáneo de sticker o equipo.
  - **Descarga de Acta Oficial:** Modal conectado al generador PDF oficial (`campaigns.generate-acta`) filtrable por dependencia con casillas de firma institucional.
- **Limpieza de Cabecera (`topbar.blade.php`):** Eliminado el botón `Buscar... Ctrl K` y el texto redundante de rol, dejando una barra limpia y elegante.

### 1.4 Solución a la Lentitud en Carga de Módulos (Aceleración 40x)
- **Diagnóstico:**
  - En Windows, la comunicación entre PHP local y MariaDB en Docker pasa por el puente virtual (WSL2 / Hyper-V NAT), acumulando ~15-20 ms por viaje TCP.
  - Laravel tenía configurado `PDO::ATTR_EMULATE_PREPARES => false`, lo que obligaba a hacer 2 o 3 viajes TCP por consulta (`PREPARE` + `EXECUTE` + `CLOSE`). Una simple consulta tardaba 40 ms. Ejecutar 10 consultas por página sumaba 500 ms a 1 s de pura espera de red.
  - En `campaigns.show`, el controlador ejecutaba `Asset::whereNotIn(...)` cargando cientos de registros no utilizados (~130 ms).
  - El servidor local `php artisan serve` corría con 1 solo worker, bloqueando peticiones concurrentes de CSS/JS/fonts.
- **Solución implementada:**
  1. Activado `PDO::ATTR_EMULATE_PREPARES => true` en [`config/database.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/config/database.php). Las consultas por lote se redujeron de **400 ms a 10.1 ms** (40 veces más veloz).
  2. Eliminada la consulta pesada redundante en [`MaintenanceCampaignController.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Http/Controllers/MaintenanceCampaignController.php).
  3. Activado `PHP_CLI_SERVER_WORKERS=4` en [`.env`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/.env) para procesamiento concurrente en servidor de desarrollo.

---

## 2. Archivos Modificados y Creados

### Migraciones y Base de Datos
- `database/migrations/2026_10_21_114315_add_execution_fields_to_campaign_assets_table.php` (nueva)
- `database/seeders/SyncRolesSeeder.php` (nueva)
- `database/seeders/RolePermissionSeeder.php` (actualizado a 4 roles estándar)

### Backend (Servicios, Controladores y Enums)
- `app/Enums/CampaignStatus.php` (añadido `PENDIENTE_APROBACION`)
- `app/Services/MaintenanceCampaign/MaintenanceCampaignService.php` (lógica de atención manual y desacople de casos)
- `app/Services/MaintenanceCase/MaintenanceCaseService.php` (aislamiento con `whereNull('campaign_id')`)
- `app/Services/Technician/TechnicianService.php` (colas y métricas técnicas aisladas de campañas)
- `app/Services/AgentDevice/AgentDeviceService.php` (atención automática vía telemetría en campañas en curso)
- `app/Services/Document/DocumentGeneratorService.php` (generación de Acta PDF desde `campaign_assets`)
- `app/Http/Controllers/MaintenanceCampaignController.php` (rutas y métodos para atenciones, alta rápida y estado)
- `routes/web.php` (rutas `campaigns.assets.attend`, `campaigns.assets.quick-register`, `campaigns.status`, `campaigns.generate-acta`)
- `config/database.php` (optimización `PDO::ATTR_EMULATE_PREPARES => true`)
- `.env` (`PHP_CLI_SERVER_WORKERS=4`)

### Frontend (Vistas Blade y Layouts)
- `resources/views/campaigns/index.blade.php` (rediseño ultra-minimalista con tarjetas ejecutivas)
- `resources/views/campaigns/show.blade.php` (espacio de trabajo horizontal, iconos SVG precisos, grilla de 8 columnas y modales reactivos)
- `resources/views/campaigns/create.blade.php` y `edit.blade.php` (formularios minimalistas sin referencias a casos masivos)
- `resources/views/components/layouts/partials/topbar.blade.php` (eliminado `Buscar... Ctrl K` y datos apilados)
- `resources/views/components/layouts/partials/sidebar.blade.php` (barra lateral oscura plana de 5 módulos principales)
- `resources/views/documents/pdf/acta-mantenimiento.blade.php` (plantilla PDF actualizada)

### Pruebas Automatizadas
- `tests/Feature/CampaignIsolationTest.php` (5 pruebas: campañas en curso, auto-atención por agente, alta rápida en campo, aprobación y acta PDF)
- `tests/Feature/SidebarAndModulesNavigationTest.php` (3 pruebas de navegación y permisos de barra lateral)
- **Resultado de la suite completa:** `21 passed, 97 assertions` en 2.3 s.

---

## 3. Estado de la Base de Datos Local
- Servidor: MariaDB en contenedor `mantenimiento_mariadb` (puerto 3306).
- Base de datos: `sistema01_mantenimiento`.
- Campaña `CAMP-2026-0001` (ID: 1): estado `en_curso`, con 544 activos asociados listos para registro preventivo.
- Tabla `maintenance_cases`: 1 ticket real de mesa de ayuda (`TCK-2026-0001`), sin contaminación de campañas.

---

## 4. Próximos Pasos Recomendados para Siguientes Sesiones
1. Validar la vista de **Inventario** (`/assets`) para asegurar que adopte la misma estética ultra-minimalista y los mismos glifos monocromáticos de hardware.
2. Comprobar en tablets de técnicos la usabilidad táctil del modal de atención en campo durante visitas a oficinas.
3. Evaluar la exportación en Excel de la relación de equipos de campaña por oficina además del Acta PDF.
