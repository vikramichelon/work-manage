<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $activities = Activity::query()
            ->with(['user', 'task.category'])
            ->latest('created_at')
            ->paginate(40);

        return view('activity.index', compact('activities'));
    }
}
