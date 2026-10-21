<?php

namespace Database\Seeders;

use App\Models\MaintenanceTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaintenanceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'superadmin@mdsj.local')->first();

        if (! $admin) {
            $this->command->warn('No se encontró el superadmin. Saltando MaintenanceTemplateSeeder.');

            return;
        }

        $templates = $this->getTemplates($admin->id);

        foreach ($templates as $templateData) {
            $items = $templateData['items'] ?? [];
            unset($templateData['items']);

            $template = MaintenanceTemplate::updateOrCreate(
                ['name' => $templateData['name']],
                $templateData
            );

            if ($template->wasRecentlyCreated || $template->wasChanged()) {
                $template->items()->delete();
                foreach ($items as $order => $item) {
                    $template->items()->create(array_merge($item, ['sort_order' => $order]));
                }
            }
        }

        $this->command->info('Plantillas de mantenimiento sembradas correctamente.');
    }

    private function getTemplates(int $adminId): array
    {
        return [

            // ── 1. Mantenimiento preventivo de computadora ────────────────────
            [
                'name' => 'Mantenimiento preventivo - PC de escritorio',
                'maintenance_type' => 'preventivo',
                'description' => 'Plantilla estándar para mantenimiento preventivo trimestral/semestral de computadoras de escritorio.',
                'problem_description' => 'Mantenimiento preventivo programado del equipo. Se realizará limpieza interna, verificación de componentes y actualización de configuraciones.',
                'diagnosis_template' => 'Equipo presenta acumulación de polvo en ventiladores y disipadores. Temperatura de operación dentro de rango aceptable. Sin fallas críticas detectadas en diagnóstico inicial.',
                'actions_template' => "1. Limpieza interna con aire comprimido (ventiladores, disipador, tarjetas).\n2. Limpieza de conectores y puertos.\n3. Verificación y reajuste de módulos de memoria RAM.\n4. Verificación de conexiones internas de disco y cables.\n5. Revisión de temperatura con software de diagnóstico.\n6. Verificación de estado del disco duro (SMART).\n7. Limpieza externa del gabinete, teclado y monitor.\n8. Verificación de sistema operativo y actualizaciones pendientes.\n9. Prueba de funcionamiento general post-mantenimiento.",
                'steps_json' => [
                    ['title' => 'Apagar y desconectar el equipo',        'detail' => 'Apagar correctamente y desconectar cables de alimentación'],
                    ['title' => 'Apertura del gabinete',                  'detail' => 'Retirar tapa lateral con destornillador'],
                    ['title' => 'Limpieza con aire comprimido',           'detail' => 'Limpiar ventiladores, tarjetas y disipadores de calor'],
                    ['title' => 'Limpieza de módulos RAM',                'detail' => 'Retirar, limpiar contactos con borrador y reinsertar'],
                    ['title' => 'Verificar conexiones de disco',          'detail' => 'Revisar cables SATA/NVMe y asegurar conexiones'],
                    ['title' => 'Limpieza externa',                       'detail' => 'Limpiar gabinete, teclado y pantalla'],
                    ['title' => 'Encender y ejecutar diagnóstico',        'detail' => 'Verificar temperatura, SMART del disco y RAM'],
                    ['title' => 'Actualizar sistema operativo',           'detail' => 'Instalar actualizaciones de Windows/SO pendientes'],
                    ['title' => 'Prueba de funcionamiento final',         'detail' => 'Confirmar operación normal con usuario'],
                ],
                'next_maintenance_interval_days' => 180,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'insumo',      'name' => 'Aire comprimido (lata)',        'description' => 'Para limpieza de componentes internos',    'quantity' => 1, 'unit_cost' => 8.00],
                    ['item_type' => 'insumo',      'name' => 'Paño de microfibra',            'description' => 'Para limpieza de superficies',              'quantity' => 1, 'unit_cost' => 3.50],
                    ['item_type' => 'insumo',      'name' => 'Pasta térmica',                 'description' => 'Para disipador del procesador si se retira', 'quantity' => 1, 'unit_cost' => 12.00],
                    ['item_type' => 'herramienta', 'name' => 'Destornillador de estrella',   'description' => 'Para apertura de gabinete',                  'quantity' => 1, 'unit_cost' => 0.00],
                ],
            ],

            // ── 2. Mantenimiento preventivo de laptop ─────────────────────────
            [
                'name' => 'Mantenimiento preventivo - Laptop',
                'maintenance_type' => 'preventivo',
                'description' => 'Plantilla para mantenimiento preventivo de laptops: limpieza, revisión de batería y actualización de drivers.',
                'problem_description' => 'Mantenimiento preventivo programado de laptop. Se realizará limpieza interna, revisión de batería y verificación general de componentes.',
                'diagnosis_template' => 'Laptop presenta funcionamiento general correcto. Se detecta posible acumulación de polvo en ventilación. Temperatura en el límite aceptable. Batería dentro del rango de uso esperado.',
                'actions_template' => "1. Desmontaje de tapa inferior de la laptop.\n2. Limpieza del ventilador y rejilla de salida de aire.\n3. Limpieza de disipador de calor.\n4. Verificación del estado de la batería (ciclos de carga).\n5. Revisión de conexiones internas.\n6. Limpieza de teclado y pantalla.\n7. Verificación de puertos USB, HDMI y audio.\n8. Actualización de drivers de red, gráficos y chipset.\n9. Prueba de temperatura y rendimiento post-limpieza.",
                'steps_json' => [
                    ['title' => 'Apagar y desconectar el cargador',       'detail' => 'Asegurarse de apagar completamente'],
                    ['title' => 'Desmontar tapa inferior',                 'detail' => 'Retirar tornillos con cuidado, usar herramienta de apertura'],
                    ['title' => 'Limpiar ventilador y rejillas',           'detail' => 'Usar aire comprimido suavemente'],
                    ['title' => 'Revisar estado de la batería',            'detail' => 'Verificar ciclos y capacidad con software'],
                    ['title' => 'Limpiar teclado y pantalla',              'detail' => 'Usar paño húmedo y alcohol isopropílico diluido'],
                    ['title' => 'Verificar puertos y conectores',          'detail' => 'Probar USB, HDMI, auriculares y cargador'],
                    ['title' => 'Actualizar drivers y SO',                 'detail' => 'Instalar actualizaciones pendientes'],
                    ['title' => 'Prueba de funcionamiento',                'detail' => 'Verificar rendimiento y temperatura bajo carga'],
                ],
                'next_maintenance_interval_days' => 180,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'insumo',      'name' => 'Aire comprimido (lata)',       'description' => 'Para limpieza de ventilación',             'quantity' => 1, 'unit_cost' => 8.00],
                    ['item_type' => 'insumo',      'name' => 'Alcohol isopropílico 70%',     'description' => 'Para limpieza de componentes',             'quantity' => 1, 'unit_cost' => 5.00],
                    ['item_type' => 'insumo',      'name' => 'Paño de microfibra',           'description' => 'Para limpiar pantalla y teclado',          'quantity' => 1, 'unit_cost' => 3.50],
                    ['item_type' => 'herramienta', 'name' => 'Kit de destornilladores',      'description' => 'Estrella y plano de precisión',            'quantity' => 1, 'unit_cost' => 0.00],
                ],
            ],

            // ── 3. Mantenimiento correctivo - Falla de encendido ──────────────
            [
                'name' => 'Correctivo - Equipo no enciende',
                'maintenance_type' => 'correctivo',
                'description' => 'Plantilla para diagnóstico y reparación de equipos que no encienden o presentan falla de arranque.',
                'problem_description' => 'El equipo no enciende o no completa el proceso de inicio. El usuario reporta que al presionar el botón de encendido no hay respuesta o se apaga inmediatamente.',
                'diagnosis_template' => 'Se realizó diagnóstico inicial: verificación de fuente de poder, revisión de módulos de memoria y conexiones internas. Pendiente determinar componente con falla específica.',
                'actions_template' => "1. Verificar alimentación eléctrica y cable de poder.\n2. Probar con tomacorriente diferente.\n3. Inspección visual de daños o quemaduras en placa.\n4. Prueba de fuente de poder con multímetro.\n5. Retirar y reinstalar módulos de RAM.\n6. Prueba de encendido con RAM mínima.\n7. Desconectar periféricos y probar encendido.\n8. Verificar batería del BIOS (pila CR2032).\n9. Registrar componente defectuoso y proceder con reemplazo.",
                'steps_json' => [
                    ['title' => 'Verificar alimentación eléctrica',        'detail' => 'Probar cable y tomacorriente con otro dispositivo'],
                    ['title' => 'Inspección visual interna',               'detail' => 'Buscar quemaduras, condensadores dañados o daños físicos'],
                    ['title' => 'Probar fuente de poder',                  'detail' => 'Medir voltajes con multímetro o tester de fuente'],
                    ['title' => 'Prueba de RAM mínima',                    'detail' => 'Dejar solo un módulo e intentar encender'],
                    ['title' => 'Desconectar periféricos',                 'detail' => 'Probar solo con lo indispensable (CPU, RAM, fuente)'],
                    ['title' => 'Revisar pila del BIOS',                   'detail' => 'Medir voltaje de la CR2032 (debe ser ~3V)'],
                    ['title' => 'Identificar componente defectuoso',       'detail' => 'Documentar la causa raíz encontrada'],
                    ['title' => 'Reemplazo o reparación',                  'detail' => 'Ejecutar la acción correctiva correspondiente'],
                    ['title' => 'Prueba de encendido y funcionamiento',    'detail' => 'Confirmar solución con arranque completo del SO'],
                ],
                'next_maintenance_interval_days' => 90,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'herramienta', 'name' => 'Multímetro digital',            'description' => 'Para medir voltajes de la fuente',         'quantity' => 1, 'unit_cost' => 0.00],
                    ['item_type' => 'repuesto',    'name' => 'Pila CR2032 (BIOS)',             'description' => 'Batería de repuesto para BIOS',            'quantity' => 1, 'unit_cost' => 2.50],
                ],
            ],

            // ── 4. Mantenimiento correctivo - Pantalla dañada ─────────────────
            [
                'name' => 'Correctivo - Reemplazo de pantalla/monitor',
                'maintenance_type' => 'correctivo',
                'description' => 'Plantilla para sustitución de monitor o pantalla de laptop dañada.',
                'problem_description' => 'El usuario reporta que la pantalla del equipo presenta daños visibles: líneas, manchas, imagen distorsionada o pantalla en negro. Se requiere diagnóstico y posible reemplazo.',
                'diagnosis_template' => 'Inspección visual confirma daño en pantalla. Se verificó conexión de cable de video. El equipo proyecta imagen correctamente por salida HDMI/VGA externa, confirmando que la falla es exclusiva del panel o cable de pantalla.',
                'actions_template' => "1. Conectar monitor externo para verificar funcionamiento de la tarjeta gráfica.\n2. Verificar cable de pantalla (LVDS/eDP en laptops).\n3. Desconectar y reconectar cable de pantalla.\n4. Si persiste la falla, proceder con reemplazo del panel.\n5. Instalar nuevo panel/monitor y verificar imagen.\n6. Prueba de color, brillo y resolución.\n7. Registrar número de serie del componente reemplazado.",
                'steps_json' => [
                    ['title' => 'Conectar monitor externo',                'detail' => 'Verificar si la GPU funciona correctamente'],
                    ['title' => 'Inspeccionar cable de pantalla',          'detail' => 'Revisar cable LVDS/eDP, buscar dobleces o daños'],
                    ['title' => 'Reconectar cable de pantalla',            'detail' => 'Desconectar y reconectar firmemente en ambos extremos'],
                    ['title' => 'Reemplazar panel si es necesario',        'detail' => 'Instalar panel nuevo verificando compatibilidad'],
                    ['title' => 'Prueba de imagen',                        'detail' => 'Verificar color, resolución y ángulos de visión'],
                    ['title' => 'Documentar componente instalado',         'detail' => 'Registrar modelo y número de serie del panel nuevo'],
                ],
                'next_maintenance_interval_days' => null,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'herramienta', 'name' => 'Kit de apertura de plástico', 'description' => 'Para desmontar bisel de pantalla',           'quantity' => 1, 'unit_cost' => 0.00],
                    ['item_type' => 'insumo',      'name' => 'Cinta adhesiva doble cara',   'description' => 'Para fijar panel nuevo al bisel',            'quantity' => 1, 'unit_cost' => 4.00],
                ],
            ],

            // ── 5. Mantenimiento preventivo de impresora ──────────────────────
            [
                'name' => 'Mantenimiento preventivo - Impresora',
                'maintenance_type' => 'preventivo',
                'description' => 'Plantilla para mantenimiento preventivo de impresoras de inyección de tinta o láser.',
                'problem_description' => 'Mantenimiento preventivo programado de impresora. Se realizará limpieza de cabezales, rodillos y bandeja de papel, y revisión general de consumibles.',
                'diagnosis_template' => 'Impresora en funcionamiento general correcto. Se observa acumulación de polvo en bandeja y rodillos. Nivel de tóner/tinta dentro del rango aceptable. Sin errores en pantalla del equipo.',
                'actions_template' => "1. Limpiar exterior de la impresora con paño húmedo.\n2. Limpiar rodillos de alimentación de papel.\n3. Revisar y limpiar bandeja de entrada y salida.\n4. Ejecutar ciclo de limpieza de cabezales desde software.\n5. Imprimir página de prueba y verificar calidad.\n6. Revisar nivel de tóner/tinta e informar si requiere reemplazo próximo.\n7. Revisar conectividad de red/USB.\n8. Verificar configuración de impresión predeterminada.",
                'steps_json' => [
                    ['title' => 'Limpiar exterior',                        'detail' => 'Paño húmedo en superficie y ranuras de ventilación'],
                    ['title' => 'Limpiar rodillos de papel',               'detail' => 'Usar paño con alcohol isopropílico suavemente'],
                    ['title' => 'Limpieza de cabezales',                   'detail' => 'Ejecutar utilidad de limpieza desde el software de impresora'],
                    ['title' => 'Revisar consumibles',                     'detail' => 'Verificar nivel de tinta/tóner y papel disponible'],
                    ['title' => 'Imprimir página de prueba',               'detail' => 'Confirmar calidad de impresión y alineación'],
                    ['title' => 'Verificar conectividad',                  'detail' => 'Confirmar que la impresora aparece en red o USB'],
                ],
                'next_maintenance_interval_days' => 90,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'insumo',  'name' => 'Alcohol isopropílico 70%',     'description' => 'Para limpiar rodillos',                     'quantity' => 1, 'unit_cost' => 5.00],
                    ['item_type' => 'insumo',  'name' => 'Paño de microfibra',           'description' => 'Para limpiar superficies',                  'quantity' => 2, 'unit_cost' => 3.50],
                ],
            ],

            // ── 6. Mantenimiento preventivo de red/switch ─────────────────────
            [
                'name' => 'Mantenimiento preventivo - Equipos de red (switch/router)',
                'maintenance_type' => 'preventivo',
                'description' => 'Plantilla para mantenimiento preventivo de switches, routers y equipos de red activa.',
                'problem_description' => 'Mantenimiento preventivo programado de equipo de red. Se realizará limpieza, revisión de puertos y verificación de configuración.',
                'diagnosis_template' => 'Equipo de red en operación normal. Se observa acumulación de polvo en ventilación. Todos los puertos activos reportando enlace. Sin alarmas o logs de error críticos en revisión inicial.',
                'actions_template' => "1. Verificar LEDs e indicadores del equipo antes de intervenir.\n2. Registrar configuración actual (hacer respaldo si aplica).\n3. Limpiar exterior y ranuras de ventilación con aire comprimido.\n4. Inspeccionar puertos RJ45 y SFP en busca de daños.\n5. Verificar que los cables estén correctamente etiquetados.\n6. Revisar logs de errores en interfaz de administración.\n7. Verificar temperatura de operación del equipo.\n8. Confirmar que todos los VLANs y rutas estén operativos.\n9. Documentar estado post-mantenimiento.",
                'steps_json' => [
                    ['title' => 'Verificar estado antes de intervenir',    'detail' => 'Registrar LEDs, puertos activos y alarmas'],
                    ['title' => 'Respaldo de configuración',               'detail' => 'Exportar config por SSH/consola si aplica'],
                    ['title' => 'Limpieza de ventilación',                 'detail' => 'Usar aire comprimido en rejillas de ventilación'],
                    ['title' => 'Inspeccionar puertos físicos',            'detail' => 'Verificar daños en conectores RJ45/SFP'],
                    ['title' => 'Revisar etiquetado de cables',            'detail' => 'Confirmar que cables estén identificados correctamente'],
                    ['title' => 'Revisar logs de errores',                 'detail' => 'Buscar errores críticos en interfaz web o CLI'],
                    ['title' => 'Verificar conectividad de VLANs',        'detail' => 'Hacer ping entre segmentos de red configurados'],
                    ['title' => 'Documentar estado final',                 'detail' => 'Registrar observaciones y estado del equipo'],
                ],
                'next_maintenance_interval_days' => 180,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'insumo',      'name' => 'Aire comprimido (lata)',    'description' => 'Para limpieza de ventilación',             'quantity' => 1, 'unit_cost' => 8.00],
                    ['item_type' => 'herramienta', 'name' => 'Cable de consola RJ45',     'description' => 'Para acceso por consola serial si aplica', 'quantity' => 1, 'unit_cost' => 0.00],
                ],
            ],

            // ── 7. Correctivo - Disco duro defectuoso ─────────────────────────
            [
                'name' => 'Correctivo - Reemplazo de disco duro / SSD',
                'maintenance_type' => 'correctivo',
                'description' => 'Plantilla para reemplazo de disco duro o SSD defectuoso incluyendo recuperación de datos si aplica.',
                'problem_description' => 'El equipo presenta lentitud extrema, errores frecuentes del sistema operativo o no reconoce el disco de almacenamiento. Se sospecha falla en disco duro o SSD.',
                'diagnosis_template' => 'Diagnóstico SMART del disco muestra sectores defectuosos / errores de lectura-escritura / disco no reconocido por BIOS. Se confirma falla de unidad de almacenamiento. Se evaluará posibilidad de recuperación de datos antes del reemplazo.',
                'actions_template' => "1. Ejecutar diagnóstico SMART del disco (CrystalDiskInfo o similar).\n2. Evaluar posibilidad de recuperación de datos antes de reemplazar.\n3. Intentar respaldo de datos a unidad externa si el disco aún monta.\n4. Apagar equipo y retirar disco defectuoso.\n5. Instalar nuevo disco/SSD verificando compatibilidad (SATA/NVMe).\n6. Instalar sistema operativo en nuevo disco.\n7. Restaurar datos respaldados.\n8. Instalar controladores y software necesario.\n9. Prueba de funcionamiento y verificación de salud del nuevo disco.",
                'steps_json' => [
                    ['title' => 'Diagnóstico SMART del disco',             'detail' => 'Usar CrystalDiskInfo, HDTune o similar'],
                    ['title' => 'Recuperar datos si es posible',           'detail' => 'Copiar archivos a disco externo antes del reemplazo'],
                    ['title' => 'Retirar disco defectuoso',                'detail' => 'Desconectar cables SATA/NVMe con cuidado'],
                    ['title' => 'Instalar nuevo disco',                    'detail' => 'Verificar compatibilidad de interfaz y espacio físico'],
                    ['title' => 'Instalar sistema operativo',              'detail' => 'Usar medio de instalación USB o imagen corporativa'],
                    ['title' => 'Restaurar datos',                         'detail' => 'Copiar datos respaldados al nuevo disco'],
                    ['title' => 'Instalar software necesario',             'detail' => 'Controladores, antivirus y software institucional'],
                    ['title' => 'Verificar salud del disco nuevo',        'detail' => 'Ejecutar diagnóstico SMART del disco instalado'],
                ],
                'next_maintenance_interval_days' => 365,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'herramienta', 'name' => 'Destornillador de estrella',  'description' => 'Para desmontaje de disco',                 'quantity' => 1, 'unit_cost' => 0.00],
                    ['item_type' => 'herramienta', 'name' => 'Disco externo USB',           'description' => 'Para respaldo de datos del usuario',       'quantity' => 1, 'unit_cost' => 0.00],
                ],
            ],

            // ── 8. Emergencia - Equipo con sobrecalentamiento ─────────────────
            [
                'name' => 'Emergencia - Sobrecalentamiento crítico',
                'maintenance_type' => 'emergencia',
                'description' => 'Plantilla para atención de emergencia por sobrecalentamiento extremo de equipo con riesgo de daño físico.',
                'problem_description' => 'El equipo se apaga solo por sobrecalentamiento o presenta temperatura crítica detectada por el usuario / sistema de monitoreo. Requiere atención inmediata.',
                'diagnosis_template' => 'Se detecta temperatura superior a los límites seguros de operación. Posible causa: ventilador defectuoso, acumulación extrema de polvo, pasta térmica reseca o falla en disipador. Se suspende uso del equipo hasta resolución.',
                'actions_template' => "1. Apagar el equipo inmediatamente y desconectar de la corriente.\n2. Dejar enfriar al menos 15 minutos antes de abrir.\n3. Inspeccionar ventilador del CPU (verificar si gira).\n4. Limpiar completamente el interior del equipo.\n5. Retirar disipador, limpiar pasta térmica antigua y aplicar nueva.\n6. Verificar que el ventilador funcione correctamente.\n7. Revisar si otros componentes presentan daños por temperatura.\n8. Encender y monitorear temperatura durante 10 minutos.\n9. Si persiste, evaluar reemplazo de ventilador o disipador.",
                'steps_json' => [
                    ['title' => 'Apagar y desconectar INMEDIATAMENTE',    'detail' => 'No continuar usando el equipo hasta diagnóstico'],
                    ['title' => 'Esperar enfriamiento',                    'detail' => 'Mínimo 15 minutos antes de abrir el equipo'],
                    ['title' => 'Inspeccionar ventilador del procesador',  'detail' => 'Verificar si gira libremente y sin ruido anormal'],
                    ['title' => 'Limpiar interior con aire comprimido',    'detail' => 'Eliminar todo el polvo acumulado'],
                    ['title' => 'Cambiar pasta térmica',                   'detail' => 'Retirar pasta vieja, aplicar pasta nueva uniformemente'],
                    ['title' => 'Verificar flujo de aire del gabinete',    'detail' => 'Confirmar que la disposición de cables no obstruya ventilación'],
                    ['title' => 'Encender y monitorear temperatura',       'detail' => 'Usar HWMonitor u otro durante 10-15 min de operación'],
                    ['title' => 'Determinar si se requiere pieza de repuesto', 'detail' => 'Evaluar reemplazo de ventilador o disipador'],
                ],
                'next_maintenance_interval_days' => 60,
                'is_active' => true,
                'created_by' => $adminId,
                'items' => [
                    ['item_type' => 'insumo',      'name' => 'Pasta térmica',               'description' => 'Para reemplazo en disipador del CPU',      'quantity' => 1, 'unit_cost' => 12.00],
                    ['item_type' => 'insumo',      'name' => 'Aire comprimido (lata)',       'description' => 'Para limpieza urgente de polvo',           'quantity' => 1, 'unit_cost' => 8.00],
                    ['item_type' => 'herramienta', 'name' => 'Destornillador de estrella',  'description' => 'Para desmontaje del disipador',            'quantity' => 1, 'unit_cost' => 0.00],
                ],
            ],

        ];
    }
}
