@csrf
@php
    $estTotalMin = $task->estimated_hours !== null ? (int) round(((float) $task->estimated_hours) * 60) : null;
    $estH = $estTotalMin !== null ? intdiv($estTotalMin, 60) : null;
    $estM = $estTotalMin !== null ? $estTotalMin % 60 : null;
    $actTotalMin = $task->actual_hours !== null ? (int) round(((float) $task->actual_hours) * 60) : null;
    $actH = $actTotalMin !== null ? intdiv($actTotalMin, 60) : null;
    $actM = $actTotalMin !== null ? $actTotalMin % 60 : null;
@endphp

<div class="row g-3">
    <div class="col-12">
        <label for="title" class="form-label">Task headline <span class="text-danger">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}"
               class="form-control @error('title') is-invalid @enderror"
               required autofocus
               placeholder="Apne shabdo mein likho — e.g. Aelira ke contact page mein form fix">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-8">
        <label for="activity_template_id" class="form-label">
            Activity type
            @if (! $task->exists) <span class="text-danger">*</span> @endif
        </label>
        <select id="activity_template_id" name="activity_template_id"
                class="form-select @error('activity_template_id') is-invalid @enderror @error('new_activity_name') is-invalid @enderror"
                @if (! $task->exists) required @endif>
            <option value="">— Select activity —</option>
            @foreach ($templates as $tpl)
                <option value="{{ $tpl->id }}" data-minutes="{{ $tpl->estimated_minutes }}"
                        @selected((string) old('activity_template_id', $task->activity_template_id) === (string) $tpl->id)>
                    {{ $tpl->name }} ({{ $tpl->estimated_minutes >= 60 ? floor($tpl->estimated_minutes / 60) . 'h' . ($tpl->estimated_minutes % 60 ? ' ' . ($tpl->estimated_minutes % 60) . 'm' : '') : $tpl->estimated_minutes . 'm' }})
                </option>
            @endforeach
            <option value="other" @selected(old('activity_template_id') === 'other')>+ Other (add new)</option>
        </select>
        @error('activity_template_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

        {{-- New activity name — only when "Other" is selected --}}
        <div id="wm-new-activity-row" class="mt-2 d-none">
            <input type="text" name="new_activity_name" value="{{ old('new_activity_name') }}"
                   class="form-control @error('new_activity_name') is-invalid @enderror"
                   placeholder="New activity ka naam (e.g. Newsletter setup) — save karte hi templates mein add ho jayega">
            @error('new_activity_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $task->status?->value) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="website" class="form-label">Website / URL</label>
        <input type="text" id="website" name="website" value="{{ old('website', $task->website) }}"
               class="form-control @error('website') is-invalid @enderror"
               list="wm-website-list" autocomplete="off"
               placeholder="e.g. https://www.example.com  (purani websites suggest hongi)">
        <datalist id="wm-website-list">
            @foreach ($priorWebsites ?? [] as $w)
                <option value="{{ $w }}"></option>
            @endforeach
        </datalist>
        @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="category_id" class="form-label">Team <span class="text-danger">*</span></label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) old('category_id', $task->category_id) === (string) $cat->id)>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="priority" class="form-label">Priority</label>
        <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
            @foreach ($priorities as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $task->priority?->value) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="assigned_to" class="form-label">Assign to (kaun karega)</label>
        <select id="assigned_to" name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
            <option value="">— Unassigned —</option>
            @foreach ($assignableUsers as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_to', $task->assigned_to) === (string) $user->id)>
                    {{ $user->name }} ({{ $user->role->label() }})
                </option>
            @endforeach
        </select>
        @error('assigned_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="assigned_by_name" class="form-label">Assign person <span class="text-secondary small">(kisne diya)</span></label>
        <input type="text" id="assigned_by_name" name="assigned_by_name"
               value="{{ old('assigned_by_name', $task->assigned_by_name) }}"
               class="form-control @error('assigned_by_name') is-invalid @enderror"
               placeholder="e.g. Manager ka naam, client ka naam">
        @error('assigned_by_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="assigned_at" class="form-label">Assign date <span class="text-secondary small">(kab mila)</span></label>
        <input type="date" id="assigned_at" name="assigned_at"
               value="{{ old('assigned_at', $task->assigned_at?->format('Y-m-d')) }}"
               class="form-control @error('assigned_at') is-invalid @enderror">
        @error('assigned_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="due_date" class="form-label">Timeline <span class="text-secondary small">(kab tak)</span></label>
        <input type="date" id="due_date" name="due_date"
               value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
               class="form-control @error('due_date') is-invalid @enderror">
        <div id="wm-timeline-hint" class="form-text d-none mt-1">
            <span class="text-secondary">Suggested:</span>
            <strong id="wm-timeline-hint-date"></strong>
            <span class="text-secondary small">(based on assignee's queue of <span id="wm-timeline-hint-queue">0m</span>)</span>
            <button type="button" id="wm-use-suggested" class="btn btn-link btn-sm p-0 ms-1">Use this</button>
        </div>
        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Estimated time <span class="text-secondary small">(activity se auto-fill)</span></label>
        <div class="input-group @error('estimated_hours') is-invalid @enderror">
            <input type="number" name="estimated_hours_h" min="0" max="999"
                   value="{{ old('estimated_hours_h', $estH) }}" class="form-control" placeholder="0">
            <span class="input-group-text">h</span>
            <input type="number" name="estimated_hours_m" min="0" max="59"
                   value="{{ old('estimated_hours_m', $estM) }}" class="form-control" placeholder="0">
            <span class="input-group-text">m</span>
        </div>
        @error('estimated_hours') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Actual time <span class="text-secondary small">(kitna laga)</span></label>
        <div class="input-group @error('actual_hours') is-invalid @enderror">
            <input type="number" name="actual_hours_h" min="0" max="999"
                   value="{{ old('actual_hours_h', $actH) }}" class="form-control" placeholder="0">
            <span class="input-group-text">h</span>
            <input type="number" name="actual_hours_m" min="0" max="59"
                   value="{{ old('actual_hours_m', $actM) }}" class="form-control" placeholder="0">
            <span class="input-group-text">m</span>
        </div>
        @error('actual_hours') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    {{-- Hold reason — visible + required only when status is Hold --}}
    <div class="col-12 wm-hold-reason-row d-none">
        <label for="hold_reason" class="form-label">
            <i class="fa-solid fa-circle-pause text-secondary me-1"></i>Hold reason <span class="text-danger">*</span>
            <span class="text-secondary small">(kyu hold pe hai)</span>
        </label>
        <textarea id="hold_reason" name="hold_reason" rows="2"
                  class="form-control @error('hold_reason') is-invalid @enderror"
                  placeholder="e.g. Client revisions pending / waiting for content / blocker XYZ">{{ old('hold_reason', $task->hold_reason) }}</textarea>
        @error('hold_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Final URL — visible only when status is First Draft or Done --}}
    <div class="col-12 wm-final-url-row d-none">
        <label for="final_url" class="form-label">
            <i class="fa-solid fa-link text-primary me-1"></i>Final URL
            <span class="text-secondary small">(deliverable link — review / live)</span>
        </label>
        <input type="url" id="final_url" name="final_url" value="{{ old('final_url', $task->final_url) }}"
               class="form-control @error('final_url') is-invalid @enderror"
               placeholder="https://www.example.com/page  (paste karo jab ready ho)">
        @error('final_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description (kya task hai)</label>
        <textarea id="description" name="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Task ki details">{{ old('description', $task->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="delay_reason" class="form-label">
            Notes <span class="text-secondary small">(koi issue ya delay ho to likho)</span>
        </label>
        <textarea id="delay_reason" name="delay_reason" rows="2"
                  class="form-control @error('delay_reason') is-invalid @enderror"
                  placeholder="Optional">{{ old('delay_reason', $task->delay_reason) }}</textarea>
        @error('delay_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-1"></i> {{ $submitLabel ?? 'Save' }}
    </button>
    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    (function () {
        const activitySel = document.getElementById('activity_template_id');
        const newActRow   = document.getElementById('wm-new-activity-row');
        const statusSel   = document.getElementById('status');
        const finalUrlRow = document.querySelector('.wm-final-url-row');
        const estH        = document.querySelector('input[name="estimated_hours_h"]');
        const estM        = document.querySelector('input[name="estimated_hours_m"]');
        const assigneeSel = document.getElementById('assigned_to');
        const dueDate     = document.getElementById('due_date');
        const hint        = document.getElementById('wm-timeline-hint');
        const hintDate    = document.getElementById('wm-timeline-hint-date');
        const hintQ       = document.getElementById('wm-timeline-hint-queue');
        const useBtn      = document.getElementById('wm-use-suggested');

        // Activity dropdown → fill estimated time + toggle "Other" input
        function applyActivity() {
            const val = activitySel.value;
            if (val === 'other') {
                newActRow.classList.remove('d-none');
                // Leave estimated time blank — user must define it for new activity
                return;
            }
            newActRow.classList.add('d-none');
            const opt = activitySel.selectedOptions[0];
            const minutes = parseInt(opt?.dataset.minutes || '0', 10);
            if (minutes > 0) {
                estH.value = Math.floor(minutes / 60) || 0;
                estM.value = minutes % 60;
                refreshTimelinePreview();
            }
        }
        activitySel.addEventListener('change', applyActivity);

        const holdRow = document.querySelector('.wm-hold-reason-row');

        // Status dropdown → show Final URL field on First Draft / Done,
        // show Hold reason field on Hold.
        function applyStatus() {
            const v = statusSel.value;
            finalUrlRow.classList.toggle('d-none', !(v === 'first_draft' || v === 'done'));
            holdRow.classList.toggle('d-none', v !== 'hold');
        }
        statusSel.addEventListener('change', applyStatus);

        // Timeline AJAX preview
        async function refreshTimelinePreview() {
            const h = parseInt(estH.value || '0', 10);
            const m = parseInt(estM.value || '0', 10);
            const minutes = h * 60 + m;
            if (!minutes) { hint.classList.add('d-none'); return; }
            const params = new URLSearchParams({ minutes });
            if (assigneeSel && assigneeSel.value) params.set('assignee', assigneeSel.value);
            try {
                const res = await fetch(`{{ url('tasks/suggest-timeline') }}?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const json = await res.json();
                if (json && json.date) {
                    hintDate.textContent = json.date_label;
                    hintQ.textContent    = json.queue_label;
                    dueDate.dataset.suggested = json.date;
                    hint.classList.remove('d-none');
                }
            } catch (e) { /* ignore */ }
        }
        if (useBtn) {
            useBtn.addEventListener('click', () => {
                if (dueDate.dataset.suggested) dueDate.value = dueDate.dataset.suggested;
            });
        }
        [estH, estM, assigneeSel].forEach(el => el && el.addEventListener('change', refreshTimelinePreview));

        // Initial state (handles old() repopulation on validation errors)
        applyActivity();
        applyStatus();
        refreshTimelinePreview();
    })();
</script>
