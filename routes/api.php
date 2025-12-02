<?php

use App\Http\Controllers\modules\Admin\Module\ModuleController;
use App\Http\Controllers\modules\Admin\Tenant\TenantController;
use App\Http\Controllers\modules\Auth\AuthController;
use App\Http\Controllers\modules\File\FileController;
use App\Http\Controllers\modules\Student\StatusStudentController;
use App\Http\Controllers\modules\Student\StudentController;
use App\Http\Controllers\modules\User\UserController;
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
        Route::delete('/{id}', [UserController::class, 'destroy']) ->middleware('check.permission:admin.users.delete');
    });

    // Rotas de Admin
    Route::prefix('admin')->group(function () {
        // Rotas de Módulos
        Route::get('/modules', [ModuleController::class, 'index']);
        
        // Rotas de Tenant
        Route::prefix('tenants')->group(function () {
            Route::get('/', [TenantController::class, 'index']);
            Route::get('/{id}', [TenantController::class, 'show']);
            Route::post('/', [TenantController::class, 'store']);
            Route::put('/{id}', [TenantController::class, 'update']);
            Route::patch('/{id}', [TenantController::class, 'update']);
            Route::delete('/{id}', [TenantController::class, 'destroy']);
        });
    });

    // Rotas de Status de Alunos
    Route::prefix('status-students')->group(function () {
        Route::get('/', [StatusStudentController::class, 'index']);
        Route::get('/{id}', [StatusStudentController::class, 'show']);
    });

    // Rotas de Alunos (com controle de permissões)
    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->middleware('check.permission:students.view');
        Route::get('/{id}', [StudentController::class, 'show'])->middleware('check.permission:students.view');
        Route::post('/', [StudentController::class, 'store'])->middleware('check.permission:students.create');
        Route::put('/{id}', [StudentController::class, 'update'])->middleware('check.permission:students.edit');
        Route::patch('/{id}', [StudentController::class, 'update'])->middleware('check.permission:students.edit');
        Route::delete('/{id}', [StudentController::class, 'destroy'])->middleware('check.permission:students.delete');
        
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
        Route::delete('/{id}/delete', [FileController::class, 'delete'])->middleware('check.permission:files.delete');
    });
});

