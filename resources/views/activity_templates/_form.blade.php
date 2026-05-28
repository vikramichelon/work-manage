@csrf
@php
    $h = $template->estimated_minutes !== null ? intdiv($template->estimated_minutes, 60) : null;
    $m = $template->estimated_minutes !== null ? $template->estimated_minutes % 60 : null;
@endphp
<div class="row g-3">
    <div class="col-12">
        <label for="name" class="form-label">Template name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $template->name) }}"
               class="form-control @error('name') is-invalid @enderror" required autofocus
               placeholder="e.g. LP create, Sitemap update, Page design">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Typical time <span class="text-danger">*</span></label>
        <div class="input-group @error('estimated_minutes') is-invalid @enderror">
            <input type="number" name="estimated_h" min="0" max="999"
                   value="{{ old('estimated_h', $h) }}" class="form-control" placeholder="0">
            <span class="input-group-text">h</span>
            <input type="number" name="estimated_m" min="0" max="59"
                   value="{{ old('estimated_m', $m) }}" class="form-control" placeholder="0">
            <span class="input-group-text">m</span>
        </div>
        @error('estimated_minutes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="category_id" class="form-label">Default team (optional)</label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">— None —</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) old('category_id', $template->category_id) === (string) $cat->id)>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-1"></i>{{ $submitLabel ?? 'Save' }}
    </button>
    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
