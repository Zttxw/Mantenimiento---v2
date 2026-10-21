<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceCaseStatus;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\MaintenanceCampaign;
use App\Models\MaintenanceCase;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_assets' => Asset::count(),
            'assets_in_repair' => Asset::where('status', AssetStatus::EN_REPARACION)->count(),
            'open_cases' => MaintenanceCase::whereNotIn('status', [
                MaintenanceCaseStatus::COMPLETADO->value,
                MaintenanceCaseStatus::CANCELADO->value,
            ])->count(),
            'active_campaigns' => MaintenanceCampaign::where('status', 'en_curso')->count(),
            'total_technicians' => Employee::where('is_technician', true)->where('is_active', true)->count(),
        ];

        $recentCases = MaintenanceCase::with('asset', 'assignedTechnician')
            ->whereNotIn('status', [MaintenanceCaseStatus::COMPLETADO->value, MaintenanceCaseStatus::CANCELADO->value])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'recentCases'));
    }
}
