<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Usuarios
    case USER_VIEW = 'user.view';
    case USER_CREATE = 'user.create';
    case USER_EDIT = 'user.edit';
    case USER_DELETE = 'user.delete';

    // Empleados
    case EMPLOYEE_VIEW = 'employee.view';
    case EMPLOYEE_CREATE = 'employee.create';
    case EMPLOYEE_EDIT = 'employee.edit';
    case EMPLOYEE_DELETE = 'employee.delete';

    // Unidades organizacionales
    case ORG_UNIT_VIEW = 'org-unit.view';
    case ORG_UNIT_CREATE = 'org-unit.create';
    case ORG_UNIT_EDIT = 'org-unit.edit';
    case ORG_UNIT_DELETE = 'org-unit.delete';

    // Activos
    case ASSET_VIEW = 'asset.view';
    case ASSET_CREATE = 'asset.create';
    case ASSET_EDIT = 'asset.edit';
    case ASSET_DELETE = 'asset.delete';

    // Movimientos de activos
    case ASSET_MOVEMENT_VIEW = 'asset-movement.view';
    case ASSET_MOVEMENT_CREATE = 'asset-movement.create';

    // Campañas de mantenimiento
    case CAMPAIGN_VIEW = 'campaign.view';
    case CAMPAIGN_CREATE = 'campaign.create';
    case CAMPAIGN_EDIT = 'campaign.edit';
    case CAMPAIGN_DELETE = 'campaign.delete';
    case CAMPAIGN_APPROVE = 'campaign.approve';

    // Casos de mantenimiento
    case MAINTENANCE_CASE_VIEW = 'maintenance-case.view';
    case MAINTENANCE_CASE_CREATE = 'maintenance-case.create';
    case MAINTENANCE_CASE_EDIT = 'maintenance-case.edit';
    case MAINTENANCE_CASE_CLOSE = 'maintenance-case.close';
    case MAINTENANCE_CASE_DELETE = 'maintenance-case.delete';
    case MAINTENANCE_CASE_ASSIGN = 'maintenance-case.assign';

    // Documentos
    case DOCUMENT_VIEW = 'document.view';
    case DOCUMENT_CREATE = 'document.create';
    case DOCUMENT_DELETE = 'document.delete';

    // Configuración
    case SETTING_VIEW = 'setting.view';
    case SETTING_EDIT = 'setting.edit';

    // Agentes / dispositivos
    case AGENT_VIEW = 'agent.view';
    case AGENT_MANAGE = 'agent.manage';

    // Plantillas de mantenimiento
    case MAINTENANCE_TEMPLATE_VIEW = 'maintenance-template.view';
    case MAINTENANCE_TEMPLATE_CREATE = 'maintenance-template.create';
    case MAINTENANCE_TEMPLATE_EDIT = 'maintenance-template.edit';
    case MAINTENANCE_TEMPLATE_DELETE = 'maintenance-template.delete';
}
