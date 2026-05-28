<?php

namespace App\Http\Controllers;

use App\Models\ActivityTemplate;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ActivityTemplate::class, 'template', [
            'except' => ['show'], // no detail page
        ]);
    }

    public function index(): View
    {
        return view('activity_templates.index', [
            'templates' => ActivityTemplate::with('category')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('activity_templates.create', [
            'template'   => new ActivityTemplate(['estimated_minutes' => 30]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTemplate($request);
        ActivityTemplate::create([...$data, 'created_by' => $request->user()->id]);

        return redirect()->route('templates.index')->with('status', 'Template added.');
    }

    public function edit(ActivityTemplate $template): View
    {
        return view('activity_templates.edit', [
            'template'   => $template,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ActivityTemplate $template): RedirectResponse
    {
        $template->update($this->validateTemplate($request, $template));

        return redirect()->route('templates.index')->with('status', 'Template updated.');
    }

    public function destroy(ActivityTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }

    private function validateTemplate(Request $request, ?ActivityTemplate $template = null): array
    {
        // Accept "estimated_h" + "estimated_m" from the form and fold into minutes.
        $h = max(0, (int) $request->input('estimated_h', 0));
        $m = max(0, min(59, (int) $request->input('estimated_m', 0)));
        $request->merge(['estimated_minutes' => max(1, $h * 60 + $m)]);

        return $request->validate([
            'name'              => ['required', 'string', 'max:255', Rule::unique('activity_templates', 'name')->ignore($template)],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:6000'],
            'category_id'       => ['nullable', Rule::exists('categories', 'id')],
        ]);
    }
}
