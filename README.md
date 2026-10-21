# SIGAT - Sistema de Mantenimiento y Gestión de Activos TI

Sistema integral de gestión de activos de hardware, mantenimiento preventivo/correctivo y monitoreo mediante agentes de telemetría de la MDSJ.

---

## 🚀 Despliegue y Operaciones en Producción

### Comando de Optimización y Depuración de Historial de Agentes
Para depurar heartbeats rutinarios redundantes y compactar snapshots idénticos sin perder inventarios ni alertas de salud:

```bash
# Simulación previa sin riesgo (Dry-Run):
php artisan agents:purge-sync-history --dry-run --compact-snapshots

# Ejecución real controlada en lotes de 10,000 registros con pausa de 50ms:
php artisan agents:purge-sync-history --compact-snapshots --batch-size=10000 --sleep-ms=50
```

> 📖 **Guía Completa para Producción:** Consulta el manual operativo oficial en [docs/RUNBOOK_PRODUCCION.md](docs/RUNBOOK_PRODUCCION.md) para el protocolo de backup previo, monitoreo, mitigación de bloqueos y optimización física de almacenamiento.

---

## 🛠️ Stack Tecnológico

- **Backend:** Laravel 11/13 + PHP 8.3
- **Base de Datos:** MariaDB 10.11 / MySQL (Motor InnoDB)
- **Frontend:** Blade + Tailwind CSS + Alpine.js + Vite
- **Autenticación y Roles:** `spatie/laravel-permission`
- **Monitoreo:** Endpoints REST API para Agentes Windows/Linux con deduplicación estructural SHA-256

---

## 💻 Puesta en Marcha Local

### 1. Iniciar servicios en segundo plano:
```powershell
# Levantar servidor backend (Laravel):
php artisan serve

# Levantar servidor de desarrollo de vistas (Vite):
npm run dev
```

### 2. Acceso al sistema:
- **URL Local:** [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **Usuario administrador:** `superadmin` *(o `superadmin@mdsj.local`)*
- **Contraseña:** `MdsjAdmin2026!`

---

## 🧪 Pruebas Automatizadas

Para correr la suite de pruebas unitarias y de integración de agentes:

```bash
php artisan test --filter=AgentSyncPruneAndDeduplicationTest
```

---

## 📁 Documentación del Proyecto

- [Runbook Oficial de Producción](docs/RUNBOOK_PRODUCCION.md): Procedimiento de backup, despliegue y limpieza.
- [Bitácora de Sesión Técnica](docs/historial-sesiones/2026-10-21_optimizacion_base_datos_y_agentes.md): Auditoría de datos reales y registro de decisiones.
- [Estado de Implementación](ESTADO_IMPLEMENTACION.md): Módulos y avance del sistema.
- [Mapa del Sistema](MAPA_SISTEMA_IA.md): Arquitectura detallada de modelos y relaciones.
