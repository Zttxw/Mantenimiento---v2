# Runbook de Despliegue y Operaciones en Producción

**Sistema:** SIGAT - Mantenimiento y Gestión de Activos TI (MDSJ)  
**Versión del Runbook:** 1.0 (2026-10-21)  
**Objetivo:** Guía operativa oficial para el despliegue del sistema y la depuración / optimización segura del historial de agentes en el servidor de producción.

---

## 1. El Comando Clave de Optimización

Para la depuración inicial de registros históricos acumulados en producción:

```bash
php artisan agents:purge-sync-history --compact-snapshots --batch-size=10000 --sleep-ms=50
```

### ¿Qué hace exactamente cada parámetro?

| Parámetro | Valor | Propósito |
| :--- | :--- | :--- |
| `--compact-snapshots` | *Flag* | Compacta snapshots históricos consecutivamente idénticos mediante hash SHA-256 estructural. Preserva el inicio de cada tramo y **protege el último inventario vigente de cada equipo**. |
| `--batch-size` | `10000` | Procesa y elimina en bloques de 10,000 registros para no saturar la memoria ni bloquear el motor InnoDB. |
| `--sleep-ms` | `50` | Aplica una pausa de 50 milisegundos entre cada lote para permitir que la base de datos atienda otras consultas concurrentes sin contención de I/O. |
| *(Filtro por defecto)* | *Automático* | **Preserva todas las alertas de salud** (`critical`, `warning`, o con mensajes en `warnings[]`). Solo elimina latidos rutinarios vacíos. |

---

## 2. Flujo de Ejecución en el Servidor Productivo

```text
[1. Backup con Verificación] 
       │
       ▼
[2. Despliegue de Código (git pull)]
       │
       ▼
[3. Simulación sin Riesgo (--dry-run)]
       │
       ▼
[4. Purga Controlada en Lotes]
       │
       ▼
[5. Verificación de Integridad en Panel Web]
       │
       ▼
[6. OPTIMIZE TABLE (Ventana de Mantenimiento)]
```

---

### Paso 1: Backup previo obligatorio
Antes de cualquier operación, generar un volcado transaccional y comprobar que finalizó con éxito:

```bash
# 1. Comprobar espacio en disco
df -h

# 2. Generar volcado transaccional consistente
mysqldump -u root -p --single-transaction --quick sistema01_mantenimiento > /backups/pre_limpieza_$(date +%Y%m%d_%H%M%S).sql

# 3. Comprobar código de salida (DEBE ser 0)
echo $?

# 4. Verificar tamaño del archivo generado
ls -lh /backups/pre_limpieza_*.sql
```

---

### Paso 2: Despliegue de código
1. Descargar la versión actualizada en el directorio de la aplicación:
   ```bash
   git pull origin main
   ```
2. Instalar dependencias si las hubiese:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Limpiar cachés de configuración:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. **En este punto, los nuevos heartbeats rutinarios ya NO engrosarán la base de datos.**

---

### Paso 3: Simulación previa en caliente (`--dry-run`)
Antes de borrar un solo registro, correr el comando en modo seguro para verificar el cálculo sobre la base real:

```bash
php artisan agents:purge-sync-history --dry-run --compact-snapshots
```

*Verificar en pantalla que la cantidad de alertas e inventarios protegidos sea coherente.*

---

### Paso 4: Ejecución de la depuración
Correr el comando con pausas para no afectar a los usuarios en línea:

```bash
php artisan agents:purge-sync-history --compact-snapshots --batch-size=10000 --sleep-ms=50
```

---

### Paso 5: Verificación de integridad
1. Ingresar al panel web y revisar la lista de equipos en `/agents` y `/assets`.
2. Verificar que cada computadora conserve su hardware, sistema operativo y lista de software instalado.
3. Verificar que las alertas históricas críticas sigan visibles.

---

### Paso 6: Reclamar espacio en disco (`OPTIMIZE TABLE`)
En una **ventana de bajo tráfico** (ej. 10:00 PM o fin de semana), ejecutar para que el sistema operativo recupere los gigabytes en disco:

```sql
OPTIMIZE TABLE agent_syncs;
```
*(O ejecutar `php artisan agents:purge-sync-history --optimize`).*

---

## 3. Mantenimiento Automático Posterior (Ya configurado)

El sistema tiene programada una limpieza nocturna automática diaria en [`routes/console.php`](file:///c:/Users/Jeanpier/Documents/OTI/mantenimiento-oti/routes/console.php):

```php
Schedule::command('agents:purge-sync-history --compact-snapshots --batch-size=5000 --sleep-ms=50')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/agents-purge.log'));
```

Para asegurar que esto funcione, verificar que el cron del servidor tenga activa la directiva estándar de Laravel:
```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Procedimiento de Reversión (Rollback)

Si ocurriese cualquier imprevisto durante el despliegue de código:
```bash
git checkout <commit_anterior>
php artisan config:cache
```
Si se requiriera restaurar la base de datos desde el backup previo:
```bash
mysql -u root -p sistema01_mantenimiento < /backups/pre_limpieza_YYYYMMDD_HHMMSS.sql
```
