<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityTemplateController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Categories (Ads, SEO, Project) + flat Tasks (category chosen in the form)
Route::middleware('auth')->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban');

    Route::resource('categories', CategoryController::class);

    // Specific routes BEFORE the resource so `tasks/{task}` doesn't swallow them.
    Route::get('tasks/suggest-timeline', [TaskController::class, 'suggestTimeline'])
        ->name('tasks.suggest-timeline');

    Route::resource('tasks', TaskController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->name('tasks.status');
    Route::patch('tasks/{task}/final-url', [TaskController::class, 'updateFinalUrl'])
        ->name('tasks.final-url');
    // Generic single-field inline edit (priority, assignee, due_date, etc.).
    Route::patch('tasks/{task}/inline', [TaskController::class, 'updateInline'])
        ->name('tasks.inline');
    // AJAX-loaded detail panel for the dashboard's right column.
    Route::get('tasks/{task}/panel', [TaskController::class, 'panel'])
        ->name('tasks.panel');

    // Activity templates (work types with default estimated time)
    Route::resource('templates', ActivityTemplateController::class)
        ->parameters(['templates' => 'template'])
        ->except(['show']);

    // Comments (nested store, flat delete)
    Route::post('tasks/{task}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Attachments (nested upload, flat download/delete)
    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});

// Admin-only area
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    });
