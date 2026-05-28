@csrf

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label">Category name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
               class="form-control @error('name') is-invalid @enderror" required autofocus
               placeholder="e.g. Ads, SEO, Content">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="color" class="form-label">Color</label>
        <input type="color" id="color" name="color" value="{{ old('color', $category->color) }}"
               class="form-control form-control-color @error('color') is-invalid @enderror" style="height: calc(2.4em + .75rem);">
        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <input type="text" id="description" name="description" value="{{ old('description', $category->description) }}"
               class="form-control @error('description') is-invalid @enderror" placeholder="Optional short description">
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-1"></i> {{ $submitLabel ?? 'Save' }}
    </button>
    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
