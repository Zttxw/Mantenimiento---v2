# Bitácora de Sesión: Optimización y Limpieza de Agentes en Base de Datos

**Fecha:** 21 de octubre de 2026  
**Rama:** `feature/campanas-v2`  
**Entorno auditado:** Backup real `Bd/sistema01_mantenimiento.sql` (MariaDB 10.11 / Laravel 11/13)  
**Objetivo:** Diagnosticar el tamaño excesivo de la base de datos (1.84 GB), auditar la estructura de sincronización de agentes, evitar el crecimiento descontrolado futuro y establecer un procedimiento seguro de depuración sin pérdida de información de valor.

---

## 1. Resumen Ejecutivo de la Sesión

1. **Puesta en marcha y accesos:**
   - Se levantaron los servicios locales: Laravel Backend (`http://127.0.0.1:8000`), Vite Frontend (`http://localhost:5173`) y MariaDB en Docker (`127.0.0.1:3306`).
   - Se validaron las credenciales de administración: `superadmin` / `MdsjAdmin2026!`.
2. **Carga del backup de producción:**
   - Se importó el volcado real `Bd/sistema01_mantenimiento.sql`. Tamaño inicial: **1.84 GB**.
3. **Hallazgo clave de la auditoría:**
   - El **98.9% del espacio** (1.82 GB) estaba concentrado en una única tabla: `agent_syncs`.
   - Todas las demás 29 tablas del sistema (activos, empleados, casos de mantenimiento, roles, usuarios) ocupan menos de **20 MB combinadas**.
4. **Descubrimiento de riesgo crítico en la auditoría:**
   - De los **1,169,683 registros de tipo heartbeat**:
     * **1,022,129 filas (87.4%)** son latidos rutinarios vacíos (`overallStatus: 'ok'`, `warnings: []`) enviados cada 5 minutos.
     * **147,554 filas (12.6%)** contienen incidentes de salud reales (`overallStatus: 'warning'` o `'critical'`, con alertas como `"disco C:\ en estado critico"`).
   - **Decisión acordada:** **No se debe purgar el historial indiscriminadamente por fecha**. Las alertas y fallos históricos deben conservarse intactos para fines de auditoría y soporte.
5. **Ajustes implementados y testeados localmente:**
   - `AgentDeviceService::heartbeat()`: Los latidos rutinarios (`ok`) solo actualizan el estado en vivo de `agent_devices`. Únicamente se inserta fila histórica en `agent_syncs` si existen advertencias o estados no-ok.
   - `PruneAgentSyncsCommand`: Preserva por defecto los heartbeats con incidentes de salud, añade micro-pausa `--sleep-ms=50` entre lotes de 10,000 registros para evitar bloqueos y contención de I/O, y desacopla `OPTIMIZE TABLE` para ser evaluado en ventana de mantenimiento.
   - Se validaron los 3 casos solicitados mediante tests automatizados (13/13 tests en verde).
   - Código formateado con **Laravel Pint** bajo estándares PSR-12 / Laravel.

---

## 2. Detalle de la Auditoría sobre Datos Reales

### 2.1 Distribución de almacenamiento en tablas
- **Total base de datos:** 1.84 GB (30 tablas, 100% motor InnoDB).
- **Tabla `agent_syncs`:** 1.82 GB (~1,182,071 registros totales).
- **Tabla `agent_devices`:** 16.56 MB (198 dispositivos registrados).
- **Resto de tablas:** 3.44 MB.

### 2.2 Desglose de Heartbeats en la tabla `agent_syncs`
La consulta sobre los 1,169,683 registros arrojó:

| Estado general (`health.overallStatus`) | Estado comunicación (`status`) | Cantidad de filas | Acción de depuración |
| :--- | :--- | :--- | :--- |
| `ok` | `ok` | 1,022,129 | **Candidatas a purga** (>7 días) por ser telemetría repetitiva sin incidentes. |
| `warning` | `ok` | 89,846 | **PRESERVADAS**: Reportes con avisos de memoria o disco alto. |
| `critical` | `ok` | 57,708 | **PRESERVADAS**: Reportes con fallas críticas (discos llenos, caídas). |

### 2.3 Desglose de Snapshots
- **Total snapshots:** 12,388 registros.
- **Snapshots antiguos (>90 días):** 2,521 registros.
- **Snapshots vigentes protegidos:** 198 (el último snapshot de cada equipo está blindado).
- **Snapshots duplicados estructuralmente idénticos a compactar:** 2,354 registros.

---

## 3. Decisiones Técnicas y Arquitectura Acordada

| Decisión | Estado | Racional |
| :--- | :--- | :--- |
| **1. Eliminar heartbeats redundantes** | **Implementado y Probado** | Pings rutinarios cada 5 minutos actualizan `agent_devices` sin llenar `agent_syncs`. Solo alertas generan fila. |
| **2. Deduplicar snapshots** | **Implementado y Probado** | Hash SHA-256 estructural en tiempo real (`AgentDeviceService`) y compactación segura en el comando de depuración. |
| **3. Guardar diferencias (deltas)** | **Segunda Fase** | Pospuesto para diseño riguroso posterior que garantice reconstrucción histórica. |
| **4. Automatización con cron** | **Post-Validación** | Se habilitará en el Scheduler una vez ejecutada y estabilizada la depuración en producción. |
| **5. Ejecución destructiva en producción** | **Pendiente / Controlada** | Se ejecutará directamente en el servidor productivo sobre los datos reales tras backup verificado, sin sobreescribir la BD. |

---

## 4. Pruebas Automatizadas Desarrolladas

Archivo: `tests/Feature/AgentSyncPruneAndDeduplicationTest.php`

1. `test_consecutive_identical_snapshots_do_not_insert_new_rows`: Valida que snapshots idénticos consecutivos no creen filas.
2. `test_routine_heartbeat_updates_device_without_creating_sync_history_row`: **(Caso 1)** Valida que un ping normal actualiza IP y fecha sin tocar `agent_syncs`.
3. `test_heartbeat_with_health_alert_or_warning_creates_sync_history_row`: **(Caso 2)** Valida que un ping con alerta crítica sí se guarda en `agent_syncs`.
4. `test_purge_eliminates_routine_heartbeats_but_preserves_alerts_and_valid_snapshots`: **(Caso 3)** Valida que la depuración elimina pings viejos pero conserva alertas de incidentes y el snapshot vigente.
5. `test_purge_command_with_compact_snapshots_preserves_first_of_streak_and_latest`: Valida compactación de duplicados preservando inicio de tramo y último estado.

**Resultado:** `13 passed (58 assertions) - Duración: 1.32s`

---

## 5. Simulación Final en Modo Seguro (`--dry-run`)

Comando ejecutado:
```powershell
php artisan agents:purge-sync-history --dry-run --compact-snapshots
```

Salida verificada:
```text
Base de datos objetivo: sistema01_mantenimiento
Retencion Heartbeats: 7 dias (anteriores a 2026-10-14 00:48:57)
Retencion Snapshots: 90 dias (anteriores a 2026-07-23 00:48:57)
Preservar incidentes/alertas de salud: SI
Compactar Snapshots identicos: SI
Tamano de lote: 10000 (pausa 50ms entre lotes)
Tamano inicial agent_syncs: 1862.58 MB (735567 filas aprox)
--------------------------------------------------------------------------------------
Heartbeats rutinarios a purgar: 964150 (protegiendo 139296 con incidentes/alertas)
Snapshots a purgar por retencion (> 90d): 2521 (protegiendo 198 ultimos snapshots)
Analizando snapshots de dispositivos para compactar...  Listo
Snapshots duplicados en tramos identicos a compactar: 2354
--------------------------------------------------------------------------------------
Modo DRY-RUN activo: no se realizo ningun borrado. Total filas candidatas: 969025
```

---

## 6. Procedimiento para el Pase a Producción (Runbook)

```text
[1. Backup Productivo] ──> [2. Despliegue Código] ──> [3. Dry-Run en Prod] ──> [4. Purga en Lotes] ──> [5. Validación] ──> [6. OPTIMIZE TABLE (Ventana)]
```

### Paso 1: Backup previo obligatorio en el servidor productivo
1. Validar espacio en disco:
   ```bash
   df -h
   ```
2. Ejecutar dump transaccional consistente:
   ```bash
   mysqldump -u root -p --single-transaction --quick sistema01_mantenimiento > /backups/pre_limpieza_$(date +%Y%m%d_%H%M%S).sql
   ```
3. Comprobar que el código de salida sea exitoso:
   ```bash
   echo $?
   # Debe retornar 0
   ```
4. Verificar integridad y tamaño del archivo generado:
   ```bash
   ls -lh /backups/pre_limpieza_*.sql
   ```

### Paso 2: Despliegue del código nuevo
1. Instalar la nueva versión con el `heartbeat` selectivo y el comando de purga mejorado (vía `git pull` o release tag).
2. Tener identificado el hash anterior para reversión inmediata si fuese necesario (`git log -n 1 --oneline`).
3. Verificar en logs que los agentes que comunican en caliente actualizan `agent_devices` con normalidad.

### Paso 3: Simulación en caliente sobre la base productiva
Ejecutar sin borrado para verificar las métricas reales del servidor:
```bash
php artisan agents:purge-sync-history --dry-run --compact-snapshots
```
Verificar que la cantidad de heartbeats con incidentes protegidos sea coherente.

### Paso 4: Ejecución controlada por lotes
Ejecutar la purga con tamaño de lote de 10,000 registros y micro-pausa de 50ms:
```bash
php artisan agents:purge-sync-history --compact-snapshots --batch-size=10000 --sleep-ms=50
```

### Paso 5: Validación post-depuración
1. Verificar en el panel web que los 198 equipos mantienen su inventario completo y software instalado.
2. Verificar que las alertas históricas críticas sigan consultables.
3. Comprobar que los nuevos heartbeats rutinarios ya no engrosen la tabla `agent_syncs`.

### Paso 6: Reclamación física de almacenamiento (`OPTIMIZE TABLE`)
En una **ventana de mantenimiento programada** de bajo tráfico, ejecutar:
```bash
php artisan agents:purge-sync-history --optimize --dry-run=0 --heartbeat-days=7 --snapshot-days=90
```
O directamente en SQL:
```sql
OPTIMIZE TABLE agent_syncs;
```
Esto reconstruirá el espacio físico en disco a nivel de sistema operativo (`.ibd`).

---

## 7. Ejecución Real Verificada en Entorno Local (2026-10-21 01:08)

Tras la aprobación de las reglas de seguridad, se ejecutó la optimización real sobre la base de datos local `sistema01_mantenimiento` con el comando:
```powershell
php artisan agents:purge-sync-history --compact-snapshots --batch-size=10000 --sleep-ms=10 --optimize
```

### Resultados de la Ejecución:
- **Pings rutinarios vacíos eliminados:** `964,192`
- **Snapshots antiguos eliminados (>90d):** `2,521`
- **Snapshots duplicados idénticos compactados:** `2,354`
- **Total registros depurados:** **969,067 filas**
- **Reclamación física en disco (`OPTIMIZE TABLE`):** **1,077.35 MB liberados**
- **Tamaño total de la base de datos:** Reducido de **1.84 GB a 805.44 MB**

### Integridad Post-Ejecución Comprobada:
- **Equipos registrados (`AgentDevice`):** 201 (100% intactos).
- **Activos (`Asset`):** 576 (100% intactos).
- **Casos de mantenimiento (`MaintenanceCase`):** 544 (100% intactos).
- **Alertas y fallas históricas preservadas:** 205,491 registros (todas las advertencias y estados críticos conservados).
- **Snapshots preservados:** 7,513 registros (inventario vigente de cada equipo y eventos de cambio de hardware/software).
- **Servidor web:** `HTTP 200 OK` operativo.

---

## 8. Exportación y Reemplazo del Archivo de Respaldo Físico (2026-10-21 01:15)

Por instrucción explícita del usuario, se eliminó el archivo antiguo no optimizado y se reemplazó por el nuevo volcado limpio:
- **Archivo anterior eliminado:** `Bd/sistema01_mantenimiento.sql` (2,032,911,781 bytes / ~2.03 GB).
- **Nuevo archivo oficial generado:** `Bd/sistema01_mantenimiento.sql` (851,455,757 bytes / ~851 MB).
- **Mejora adicional del volcado:** Incorpora directivas estándar `DROP TABLE IF EXISTS`, foreign keys al final y consistencia transaccional nativa con `mariadb-dump`.
