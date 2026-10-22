# Bitácora de Sesión: Rediseño Ultra-Minimalista de Estructura Orgánica (Organigrama Oficial MDSJ), Oficinas y Personal

**Fecha:** 21 de octubre de 2026  
**Rama:** `feature/campanas-v2`  
**Autor:** Antigravity (Google DeepMind) en pair programming con el usuario  
**Entorno:** MariaDB 10.11 (Docker port 3306), PHP 8.3 / Laravel 11.51, Vite 8, TailwindCSS v4, Alpine.js v3  
**Objetivo de la sesión:**
1. Solucionar el parpadeo (`flicker`) de modales y optimizar el rendimiento reactivo en la vista de detalle de campañas.
2. Restaurar y rediseñar los módulos de **Estructura Orgánica / Oficinas** (`organizational-units`) y **Personal** (`employees`) bajo los estándares ultra-minimalistas del sistema.
3. Diseñar e implementar el **Organigrama Visual Oficial** de la Municipalidad Distrital de San Juan de Miraflores conforme a la **Ordenanza Municipal N° 015-2023-MDSJ/C**, organizado en 7 niveles de gobierno y ajustado dinámicamente a la pantalla.
4. Construir un explorador interactivo en pantalla dividida (*split-view*) con árbol colapsable, búsqueda reactiva, visualizador de grafos en círculos con conectores curvos SVG y creación jerárquica de carpetas/subcarpetas (unidades hijas).
5. **Decisión arquitectónica clave:** Purificar el Organigrama como esquema maestro institucional de la municipalidad, retirando los activos de TI de este visor para focalizarlo 100% en dependencias administrativas, jerarquía funcional y personal asignado/responsable.

---

## 1. Resumen de Implementaciones y Decisiones de Diseño

### 1.1 Corrección de Parpadeo (`x-cloak`) y Paginación en Campañas
- **Problema:** Al cargar la vista de detalle de campaña (`campaigns.show`), los modales de atención rápida y registro de activos parpadeaban brevemente antes de ocultarse por Alpine.js. Asimismo, campañas con cientos de equipos generaban un scroll vertical excesivo.
- **Solución implementada:**
  - Se configuró la directiva CSS global `[x-cloak] { display: none !important; }` en [`resources/views/components/layouts/partials/head.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/components/layouts/partials/head.blade.php) y en [`resources/css/app.css`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/css/app.css).
  - Se implementó paginación reactiva en cliente en Alpine.js con selector de tamaño de página (20, 50, 100 o todos) y contador de resultados filtrados en vivo.

### 1.2 Restauración y Rediseño de Personal (`employees`)
- **Listado General (`employees.index`):**
  - Métrica superior limpia: Total Empleados, Asignados con Cargo, En Estructura Orgánica, y Sin Oficina Asignada.
  - Tabla ultra-minimalista con avatares tipográficos sutiles, número de documento, correo institucional, dependencia orgánica y estado activo/inactivo.
  - Buscador reactivo instantáneo por nombre, apellido, DNI o cargo.
- **Ficha de Detalle (`employees.show`):**
  - Resumen ejecutivo del custodio/funcionario: datos personales, cargo, unidad orgánica a la que pertenece y fecha de registro.
  - Relación limpia de activos de hardware actualmente en custodia o asignados a su cargo.

### 1.3 Explorador Jerárquico en Pantalla Dividida (*Split-View*)
- Se reimplementó [`resources/views/organizational-units/index.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/organizational-units/index.blade.php) transformándolo en una consola de trabajo unificada de pantalla completa:
  - **Panel Izquierdo (Explorador en Árbol):** Navegador jerárquico multinivel con carpetas expandibles/colapsables, búsqueda instantánea, indicadores de nivel y badges con conteo de personal y subunidades.
  - **Panel Derecho (Inspector Interactivo):** Al hacer clic en cualquier oficina o gerencia, el panel derecho carga dinámicamente sus datos: código institucional, nivel jerárquico oficial, unidad superior (padre), personal asignado y subcarpetas/oficinas dependientes.

### 1.4 Organigrama Visual Oficial (Ordenanza Municipal N° 015-2023-MDSJ/C)
- Se programó en [`OrganizationalUnitService.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Services/OrganizationalUnit/OrganizationalUnitService.php) la categorización oficial de las dependencias municipales:
  1. **Nivel 1 - Alta Dirección:** Alcaldía, Concejo Municipal, Gerencia Municipal.
  2. **Nivel 2 - Control Institucional y Defensa:** Órgano de Control Institucional (OCI), Procuraduría Pública Municipal.
  3. **Nivel 3 - Asesoramiento:** Gerencia de Asesoría Jurídica, Gerencia de Planeamiento y Presupuesto, etc.
  4. **Nivel 4 - Apoyo:** Secretaría General, Gerencia de Administración y Finanzas, Subgerencia de Tecnologías de la Información y Procesos (OTI), etc.
  5. **Nivel 5 - Línea:** Gerencia de Seguridad Ciudadana y Vial, Gerencia de Desarrollo Urbano, Gerencia de Servicios a la Ciudad y Gestión Ambiental, etc.
  6. **Nivel 6 - Órganos Desconcentrados.**
  7. **Nivel 7 - Subgerencias y Áreas Operativas.**
- **Visor Interactivo de Organigrama:**
  - Lienzo con ajuste a pantalla (*fit-to-screen*), zoom interactivo (+ / - / Reset), pantalla completa y selector de vistas (Árbol Jerárquico, Organigrama Oficial en Grafo y Vista Lista Compacta).
  - Nodos circulares elegantes con glifos SVG vectoriales que identifican el nivel administrativo sin recurrir a emojis.
  - Líneas conectoras dinámicas mediante curvas bezier SVG que unen cada gerencia con sus subunidades orgánicas.

### 1.5 Flujo Estructurador de Carpetas y Subcarpetas
- Modal reactivo de creación rápida de unidades orgánicas dependientes (`Crear Subunidad / Oficina`).
  - Preselecciona automáticamente la unidad padre seleccionada.
  - Valida el código único y el tipo de nivel administrativo correspondiente.
  - Permite estructurar la entidad de manera visual e intuitiva como un sistema de directorios institucionales.

### 1.6 Purificación Arquitectónica: Desacople de Activos del Organigrama
- **Criterio institucional:** El Organigrama debe ser exclusivamente el **esquema maestro institucional de la entidad**. La inclusión de activos individuales de TI (computadoras, impresoras, monitores) saturaba el visor y desvirtuaba el propósito del organigrama.
- **Acción realizada:**
  - Se eliminó la pestaña y el conteo de activos del inspector del organigrama.
  - Se enfocó el inspector estrictamente en: código orgánico, responsable/jefatura, personal adscrito a la dependencia y estructura de suboficinas subordinadas.
  - La gestión de equipamiento informático se mantiene centralizada y especializada en los módulos de **Inventario de Activos** (`/assets`) y **Campañas de Mantenimiento** (`/campaigns`).
- **Navegación:**
  - Se actualizaron [`config/navigation.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/config/navigation.php) y [`sidebar.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/components/layouts/partials/sidebar.blade.php) para incorporar accesos directos elegantes e independientes a **Estructura Orgánica** (`organizational-units.*`) y **Personal** (`employees.*`).

---

## 2. Archivos Modificados y Creados

### Backend (Controladores, Servicios, Requests y Modelos)
- [`app/Services/OrganizationalUnit/OrganizationalUnitService.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Services/OrganizationalUnit/OrganizationalUnitService.php): Métodos para generación de árbol jerárquico, cálculo de niveles oficiales MDSJ (Ordenanza 015-2023), conteo de personal adscrito y búsqueda recursiva.
- [`app/Http/Controllers/OrganizationalUnitController.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Http/Controllers/OrganizationalUnitController.php): Controladores para index split-view, endpoint JSON de información rápida (`quickInfo`) y creación de subunidades.
- [`app/Http/Requests/StoreOrganizationalUnitRequest.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Http/Requests/StoreOrganizationalUnitRequest.php): Reglas de validación para nuevas oficinas y dependencias.
- [`app/Models/OrganizationalUnit.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Models/OrganizationalUnit.php): Relaciones `children`, `parent`, `employees` y scopes de ordenamiento.
- [`app/Services/Employee/EmployeeService.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Services/Employee/EmployeeService.php) y [`app/Http/Controllers/EmployeeController.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/app/Http/Controllers/EmployeeController.php): Búsqueda, filtros y visualización de empleados y custodios.
- [`routes/web.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/routes/web.php): Rutas para `organizational-units.quick-info` y recursos de empleados.
- [`config/navigation.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/config/navigation.php): Integración en menú institucional.

### Frontend (Vistas Blade, Estilos y Componentes)
- [`resources/views/organizational-units/index.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/organizational-units/index.blade.php): Vista principal en pantalla dividida, visor de organigrama interactivo con SVG curvos y modales de subunidades.
- [`resources/views/organizational-units/partials/compact-tree-item.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/organizational-units/partials/compact-tree-item.blade.php): Renderizado recursivo para el explorador de árbol izquierdo.
- [`resources/views/organizational-units/partials/tree-node.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/organizational-units/partials/tree-node.blade.php): Nodos gráficos circulares del organigrama visual.
- [`resources/views/organizational-units/show.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/organizational-units/show.blade.php): Ficha de detalle de oficina.
- [`resources/views/employees/index.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/employees/index.blade.php): Listado minimalista de personal con filtros y estadísticas.
- [`resources/views/employees/show.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/employees/show.blade.php): Perfil de empleado y custodia de bienes.
- [`resources/views/campaigns/show.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/campaigns/show.blade.php): Paginación reactiva y protección anti-flicker con `x-cloak`.
- [`resources/views/components/layouts/partials/sidebar.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/components/layouts/partials/sidebar.blade.php): Navegación lateral actualizada.
- [`resources/views/components/layouts/partials/head.blade.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/views/components/layouts/partials/head.blade.php) y [`resources/css/app.css`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/resources/css/app.css): Estilos globales para `x-cloak`.

### Pruebas Automatizadas
- [`tests/Feature/SidebarAndModulesNavigationTest.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/tests/Feature/SidebarAndModulesNavigationTest.php): 6 pruebas completas que verifican la carga de la estructura orgánica, el endpoint JSON `quickInfo`, la vista de personal y el sidebar por roles.
- [`tests/Feature/CampaignIsolationTest.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/tests/Feature/CampaignIsolationTest.php): Añadida prueba para verificar renderizado sin parpadeo de modales.
- **Resultado de la suite:** 25 pruebas pasadas (119 aserciones) en 2.77 s.

---

## 3. Estado del Proyecto
- **Rama:** `feature/campanas-v2`.
- **Suite de pruebas:** 100% verde (25/25 tests).
- **Consistencia visual:** Unidades Orgánicas, Personal y Campañas comparten exactamente el mismo sistema de diseño ultra-minimalista, paleta de colores neutrales (zinc/slate) y glifos vectoriales monocromáticos.
