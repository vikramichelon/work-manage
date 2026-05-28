<?php

namespace App\Providers;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Observers\TaskObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Task::observe(TaskObserver::class);

        // Navbar badge = tasks the user personally needs to act on
        // (assigned to them, still open).
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $count = 0;
            if ($user) {
                $count = Task::query()
                    ->where('assigned_to', $user->id)
                    ->where('status', '!=', TaskStatus::DONE->value)
                    ->count();
            }
            $view->with('navMyOpenCount', $count);
        });
    }
}
