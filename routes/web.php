<?php

use App\Http\Controllers\AgentDeviceController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MaintenanceCampaignController;
use App\Http\Controllers\MaintenanceCaseController;
use App\Http\Controllers\MaintenanceTemplateController;
use App\Http\Controllers\OrganizationalUnitController;
use App\Http\Controllers\Permission\PermissionController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});
// ── Rutas públicas ───────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// ── Rutas privadas ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'user.active'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Administración de roles y permisos (solo coordinador / superadmin) ──
    Route::middleware('role:coordinador_oti|admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
        Route::resource('permissions', PermissionController::class)->except('show');
    });

    // ── Usuarios ─────────────────────────────────────────────────────────────
    Route::middleware('permission:user.view')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

    // ── Empleados ─────────────────────────────────────────────────────────────
    Route::middleware('permission:employee.view')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::patch('employees/{employee}/toggle-active', [EmployeeController::class, 'toggleActive'])->name('employees.toggle-active');
    });

    // ── Unidades organizacionales ─────────────────────────────────────────────
    Route::middleware('permission:org-unit.view')->group(function () {
        Route::resource('organizational-units', OrganizationalUnitController::class);
    });

    // ── Activos ───────────────────────────────────────────────────────────────
    Route::middleware('permission:asset.view')->group(function () {
        Route::get('assets/pc-monitor', [AssetController::class, 'pcMonitor'])->name('assets.pc-monitor');
        Route::resource('assets', AssetController::class);
        Route::get('assets/{asset}/generate-ficha', [AssetController::class, 'generateFicha'])->name('assets.generate-ficha');
        Route::get('assets/{asset}/generate-historial', [AssetController::class, 'generateHistorial'])->name('assets.generate-historial');
    });

    // ── Movimientos de activos ────────────────────────────────────────────────
    Route::middleware('permission:asset-movement.view')->group(function () {
        Route::resource('asset-movements', AssetMovementController::class)->only(['index', 'create', 'store']);
    });

    // ── Campañas de mantenimiento ─────────────────────────────────────────────
    Route::middleware('permission:campaign.view')->group(function () {
        Route::resource('campaigns', MaintenanceCampaignController::class);
        Route::post('campaigns/{campaign}/assets', [MaintenanceCampaignController::class, 'addAsset'])->name('campaigns.assets.add');
        Route::delete('campaigns/{campaign}/assets/{asset}', [MaintenanceCampaignController::class, 'removeAsset'])->name('campaigns.assets.remove');
        Route::post('campaigns/{campaign}/assets/bulk-unit', [MaintenanceCampaignController::class, 'bulkAddByUnit'])->name('campaigns.assets.bulk-unit');
        Route::post('campaigns/{campaign}/assets/{campaignAsset}/attend', [MaintenanceCampaignController::class, 'attendAsset'])->name('campaigns.assets.attend');
        Route::post('campaigns/{campaign}/assets/quick-register', [MaintenanceCampaignController::class, 'quickRegisterAsset'])->name('campaigns.assets.quick-register');
        Route::patch('campaigns/{campaign}/status', [MaintenanceCampaignController::class, 'updateStatus'])->name('campaigns.status');
        Route::post('campaigns/{campaign}/generate-acta', [MaintenanceCampaignController::class, 'generateActa'])->name('campaigns.generate-acta');
    });

    // ── Casos de mantenimiento ────────────────────────────────────────────────
    Route::middleware('permission:maintenance-case.view')->group(function () {
        Route::resource('maintenance-cases', MaintenanceCaseController::class);
        Route::patch('maintenance-cases/{maintenanceCase}/close', [MaintenanceCaseController::class, 'close'])->name('maintenance-cases.close');
        Route::post('maintenance-cases/{maintenanceCase}/items', [MaintenanceCaseController::class, 'addItem'])->name('maintenance-cases.items.add');
        Route::delete('maintenance-cases/{maintenanceCase}/items/{item}', [MaintenanceCaseController::class, 'removeItem'])->name('maintenance-cases.items.remove');
        Route::get('maintenance-cases/{maintenanceCase}/generate-informe', [MaintenanceCaseController::class, 'generateInforme'])->name('maintenance-cases.generate-informe');
        Route::post('maintenance-cases/{maintenanceCase}/apply-template', [MaintenanceCaseController::class, 'applyTemplate'])->name('maintenance-cases.apply-template');
    });

    // ── Plantillas de mantenimiento ───────────────────────────────────────────
    Route::middleware('permission:maintenance-template.view')->group(function () {
        Route::resource('maintenance-templates', MaintenanceTemplateController::class)->except('show');
        Route::get('maintenance-templates/{maintenanceTemplate}/data', [MaintenanceTemplateController::class, 'data'])->name('maintenance-templates.data');
        Route::get('maintenance-templates-list', [MaintenanceTemplateController::class, 'listForType'])->name('maintenance-templates.list');
    });

    // ── Documentos ────────────────────────────────────────────────────────────
    Route::middleware('permission:document.view')->group(function () {
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });

    // ── Agentes (vista admin) ─────────────────────────────────────────────────
    Route::middleware('permission:agent.view')->group(function () {
        Route::get('agents', [AgentDeviceController::class, 'webIndex'])->name('agents.index');
        Route::get('agents/{agentDevice}/status', [AgentDeviceController::class, 'webStatus'])->name('agents.status');
        Route::get('agents/{agentDevice}', [AgentDeviceController::class, 'webShow'])->name('agents.show');
    });

    // ── Configuración ─────────────────────────────────────────────────────────
    Route::middleware('permission:setting.view')->group(function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::patch('settings/{setting}', [SettingController::class, 'update'])->name('settings.update');
    });

    // ── Panel técnico ──────────────────────────────────────────────────────────
    Route::prefix('tecnico')->name('tecnico.')->group(function () {
        Route::get('/', [TechnicianController::class, 'dashboard'])->name('dashboard');
        Route::get('/cola', [TechnicianController::class, 'workQueue'])->name('work-queue');
        Route::get('/atender', [TechnicianController::class, 'attendAsset'])->name('attend-asset');
        Route::post('/atender', [TechnicianController::class, 'storeAttention'])->name('attend-asset.store');

        Route::prefix('casos')->name('cases.')->group(function () {
            Route::get('/{maintenanceCase}/flujo', [TechnicianController::class, 'caseWorkflow'])->name('workflow');
            Route::get('/{maintenanceCase}', [TechnicianController::class, 'caseDetail'])->name('show');
            Route::patch('/{maintenanceCase}/progreso', [TechnicianController::class, 'updateProgress'])->name('progress');
            Route::post('/{maintenanceCase}/items', [TechnicianController::class, 'addItem'])->name('items.add');
            Route::delete('/{maintenanceCase}/items/{item}', [TechnicianController::class, 'removeItem'])->name('items.remove');
            Route::post('/{maintenanceCase}/apply-template', [TechnicianController::class, 'applyTemplate'])->name('apply-template');
            Route::patch('/{maintenanceCase}/close', [TechnicianController::class, 'closeCase'])->name('close');
        });
    });
});
