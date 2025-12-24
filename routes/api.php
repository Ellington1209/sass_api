<?php

use App\Http\Controllers\modules\Admin\Module\ModuleController;
use App\Http\Controllers\modules\Admin\Services\ServiceController as ServicesServiceController;
use App\Http\Controllers\modules\Admin\Tenant\TenantController;
use App\Http\Controllers\modules\Agenda\AppointmentController;
use App\Http\Controllers\modules\Agenda\AvailabilityController;
use App\Http\Controllers\modules\Agenda\BlockController;
use App\Http\Controllers\modules\Agenda\ProviderController;
use App\Http\Controllers\modules\Agenda\ServiceController;
use App\Http\Controllers\modules\Agenda\StatusAgendaController;
use App\Http\Controllers\modules\Agenda\TenantBusinessHourController;
use App\Http\Controllers\modules\Auth\AuthController;
use App\Http\Controllers\modules\File\FileController;
use App\Http\Controllers\modules\Permission\PermissionController;
use App\Http\Controllers\modules\Student\StatusStudentController;
use App\Http\Controllers\modules\Student\StudentController;
use App\Http\Controllers\modules\User\UserController;
use App\Http\Controllers\modules\WhatsApp\WhatsappInstanceController;
use Illuminate\Support\Facades\Route;

// Rotas públicas (sem autenticação)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('files')->group(function () {
    Route::get('/public/{path}', [FileController::class, 'showPublic'])->where('path', '.*');
});

// Rotas que requerem autenticação
Route::middleware('auth:sanctum')->group(function () {
    // Rotas de Autenticação
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Rotas de Usuários (com controle de permissões)   
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('check.permission:admin.users.view');        
        Route::get('/{id}', [UserController::class, 'show'])->middleware('check.permission:admin.users.view');        
        Route::post('/', [UserController::class, 'store'])->middleware('check.permission:admin.users.create');        
        Route::put('/{id}', [UserController::class, 'update'])->middleware('check.permission:admin.users.edit');        
        Route::patch('/{id}', [UserController::class, 'update'])->middleware('check.permission:admin.users.edit');        
        Route::post('/{id}/permissions', [PermissionController::class, 'saveUserPermissions'])->middleware('check.permission:admin.users.edit'); // Salvar permissões do usuário
        Route::delete('/batch', [UserController::class, 'destroy'])->middleware('check.permission:admin.users.delete'); // Array de IDs no body - /users/batch
        Route::delete('/', [UserController::class, 'destroy'])->middleware('check.permission:admin.users.delete'); // Array de IDs no body - /users
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('check.permission:admin.users.delete'); // ID na URL
    });

    // Rotas de Permissões e Configurações do Tenant
    Route::prefix('tenants')->group(function () {
        // Horários de funcionamento do tenant (deve vir antes das rotas dinâmicas)
        Route::prefix('{tenantId}/business-hours')->group(function () {
            Route::get('/', [TenantBusinessHourController::class, 'index']);
            Route::post('/', [TenantBusinessHourController::class, 'store']);
            Route::post('/sync', [TenantBusinessHourController::class, 'sync']);
            Route::put('/{id}', [TenantBusinessHourController::class, 'update']);
            Route::patch('/{id}', [TenantBusinessHourController::class, 'update']);
            Route::delete('/{id}', [TenantBusinessHourController::class, 'destroy']);
        });
        
        // Rotas de Permissões
        Route::get('/{id}/permissions/{user_id}', [PermissionController::class, 'getTenantPermissions']); // Listar permissões do tenant com user_id
        Route::get('/{id}/permissions', [PermissionController::class, 'getTenantPermissions']); // Listar permissões do tenant (sem user_id)
    });

    // Rotas de Admin
    Route::prefix('admin')->group(function () {
        // Rotas de Módulos
        Route::get('/modules/services', [ModuleController::class, 'servicesEssential']);
        Route::get('/modules', [ModuleController::class, 'index']);
        Route::get('/services', [ServiceController::class, 'servicesProvider']);
        // Rotas de Tenant
        Route::prefix('tenants')->group(function () {
            Route::get('/', [TenantController::class, 'index']);
            Route::get('/{id}', [TenantController::class, 'show']);
            Route::post('/', [TenantController::class, 'store']);
            Route::put('/{id}', [TenantController::class, 'update']);
            Route::patch('/{id}', [TenantController::class, 'update']);
            Route::delete('/batch', [TenantController::class, 'destroy']); // Array de IDs no body - /admin/tenants/batch
            Route::delete('/{id}', [TenantController::class, 'destroy']); // ID na URL
        });
    });

    // Rotas de Status de Alunos
    Route::prefix('status-students')->group(function () {
        Route::get('/', [StatusStudentController::class, 'index']);
        Route::get('/{id}', [StatusStudentController::class, 'show']);
    });

    // Rotas de Status de Agenda
    Route::prefix('status-agenda')->group(function () {
        Route::get('/', [StatusAgendaController::class, 'index']);
        Route::get('/{id}', [StatusAgendaController::class, 'show']);
    });

    // Rotas de Clientes
    Route::prefix('clients')->group(function () {
        Route::get('/', [StudentController::class, 'clients'])->middleware('check.permission:students.view');
    });

    // Rotas de Alunos (com controle de permissões)
    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->middleware('check.permission:students.view');
        Route::get('/{id}', [StudentController::class, 'show'])->middleware('check.permission:students.view');
        Route::post('/', [StudentController::class, 'store'])->middleware('check.permission:students.create');
        Route::put('/{id}', [StudentController::class, 'update'])->middleware('check.permission:students.edit');
        Route::patch('/{id}', [StudentController::class, 'update'])->middleware('check.permission:students.edit');
        Route::delete('/batch', [StudentController::class, 'destroy'])->middleware('check.permission:students.delete'); // Array de IDs no body - /students/batch
        Route::delete('/', [StudentController::class, 'destroy'])->middleware('check.permission:students.delete'); // Array de IDs no body - /students
        Route::delete('/{id}', [StudentController::class, 'destroy'])->middleware('check.permission:students.delete'); // ID na URL
        
        // Rotas de documentos
        Route::post('/{id}/documents', [StudentController::class, 'addDocument'])->middleware('check.permission:students.upload_document');
        Route::delete('/{id}/documents/{documentId}', [StudentController::class, 'removeDocument'])->middleware('check.permission:students.edit');
        
        // Rotas de observações
        Route::post('/{id}/notes', [StudentController::class, 'addNote'])->middleware('check.permission:students.edit');
        Route::delete('/{id}/notes/{noteId}', [StudentController::class, 'removeNote'])->middleware('check.permission:students.edit');
    });

    // Rotas de Arquivos
    Route::prefix('files')->group(function () {
        Route::post('/upload', [FileController::class, 'upload'])->middleware('check.permission:files.upload');
        Route::get('/show/{path}', [FileController::class, 'show'])->where('path', '.*')->middleware('check.permission:files.view');
        Route::get('/{id}/url', [FileController::class, 'showUrl'])->middleware('check.permission:files.download');
        Route::delete('/batch/delete', [FileController::class, 'delete'])->middleware('check.permission:files.delete'); // Array de IDs no body - /files/batch/delete
        Route::delete('/delete', [FileController::class, 'delete'])->middleware('check.permission:files.delete'); // Array de IDs no body - /files/delete
        Route::delete('/{id}/delete', [FileController::class, 'delete'])->middleware('check.permission:files.delete'); // ID na URL
    });

    // Rotas de Agenda
    Route::prefix('agenda')->group(function () {
        // Agenda completa (disponibilidades, bloqueios e agendamentos)
        Route::get('/', [AppointmentController::class, 'getAgenda'])->middleware('check.permission:agenda.appointments.view');

        // Services
        Route::prefix('services')->group(function () {
            Route::get('/', [ServiceController::class, 'index'])->middleware('check.permission:agenda.services.view');
            Route::get('/{id}', [ServiceController::class, 'show'])->middleware('check.permission:agenda.services.view');
            Route::post('/', [ServiceController::class, 'store'])->middleware('check.permission:agenda.services.create');
            Route::put('/{id}', [ServiceController::class, 'update'])->middleware('check.permission:agenda.services.edit');
            Route::patch('/{id}', [ServiceController::class, 'update'])->middleware('check.permission:agenda.services.edit');
            Route::delete('/batch', [ServiceController::class, 'destroy'])->middleware('check.permission:agenda.services.delete');
            Route::delete('/', [ServiceController::class, 'destroy'])->middleware('check.permission:agenda.services.delete');
            Route::delete('/{id}', [ServiceController::class, 'destroy'])->middleware('check.permission:agenda.services.delete');
        });

        // Providers
        Route::prefix('providers')->group(function () {
            Route::get('/', [ProviderController::class, 'index'])->middleware('check.permission:agenda.providers.view');
            Route::get('/{id}', [ProviderController::class, 'show'])->middleware('check.permission:agenda.providers.view');
            Route::post('/', [ProviderController::class, 'store'])->middleware('check.permission:agenda.providers.create');
            Route::put('/{id}', [ProviderController::class, 'update'])->middleware('check.permission:agenda.providers.edit');
            Route::patch('/{id}', [ProviderController::class, 'update'])->middleware('check.permission:agenda.providers.edit');
            Route::delete('/batch', [ProviderController::class, 'destroy'])->middleware('check.permission:agenda.providers.delete');
            Route::delete('/', [ProviderController::class, 'destroy'])->middleware('check.permission:agenda.providers.delete');
            Route::delete('/{id}', [ProviderController::class, 'destroy'])->middleware('check.permission:agenda.providers.delete');
        });

        // Appointments
        Route::prefix('appointments')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])->middleware('check.permission:agenda.appointments.view');
            Route::get('/{id}', [AppointmentController::class, 'show'])->middleware('check.permission:agenda.appointments.view');
            Route::post('/', [AppointmentController::class, 'store'])->middleware('check.permission:agenda.appointments.create');
            Route::put('/{id}', [AppointmentController::class, 'update'])->middleware('check.permission:agenda.appointments.edit');
            Route::patch('/{id}', [AppointmentController::class, 'update'])->middleware('check.permission:agenda.appointments.edit');
            Route::delete('/batch', [AppointmentController::class, 'destroy'])->middleware('check.permission:agenda.appointments.delete');
            Route::delete('/', [AppointmentController::class, 'destroy'])->middleware('check.permission:agenda.appointments.delete');
            Route::delete('/{id}', [AppointmentController::class, 'destroy'])->middleware('check.permission:agenda.appointments.delete');
        });

        // Disponibilidades
        Route::prefix('providers/{providerId}/availabilities')->group(function () {
            Route::get('/', [AvailabilityController::class, 'index'])->middleware('check.permission:agenda.providers.view');
            Route::post('/', [AvailabilityController::class, 'store'])->middleware('check.permission:agenda.providers.edit');
            Route::post('/sync', [AvailabilityController::class, 'sync'])->middleware('check.permission:agenda.providers.edit');
            Route::put('/{id}', [AvailabilityController::class, 'update'])->middleware('check.permission:agenda.providers.edit');
            Route::patch('/{id}', [AvailabilityController::class, 'update'])->middleware('check.permission:agenda.providers.edit');
            Route::delete('/{id}', [AvailabilityController::class, 'destroy'])->middleware('check.permission:agenda.providers.edit');
        });

        // Bloqueios
        Route::prefix('providers/{providerId}/blocks')->group(function () {
            Route::get('/', [BlockController::class, 'index'])->middleware('check.permission:agenda.providers.view');
            Route::post('/', [BlockController::class, 'store'])->middleware('check.permission:agenda.providers.edit');
            Route::put('/{id}', [BlockController::class, 'update'])->middleware('check.permission:agenda.providers.edit');
            Route::patch('/{id}', [BlockController::class, 'update'])->middleware('check.permission:agenda.providers.edit');
            Route::delete('/{id}', [BlockController::class, 'destroy'])->middleware('check.permission:agenda.providers.edit');
        });
    });

    // Rotas do WhatsApp
    Route::prefix('whatsapp')->group(function () {
        Route::prefix('instances')->group(function () {
            Route::get('/', [WhatsappInstanceController::class, 'index'])->middleware('check.permission:whatsapp.instances.view');
            Route::post('/', [WhatsappInstanceController::class, 'store'])->middleware('check.permission:whatsapp.instances.create');
            Route::post('/{id}/send', [WhatsappInstanceController::class, 'send'])->middleware('check.permission:whatsapp.instances.send');
            Route::delete('/{id}', [WhatsappInstanceController::class, 'destroy'])->middleware('check.permission:whatsapp.instances.delete');
        });
    });
});

