<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Apply saved theme BEFORE Bootstrap CSS loads, to avoid a flash. --}}
    <script>
        (function () {
            var t = localStorage.getItem('wm-theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>

    {{-- Inter font (replaces the @import that used to live inside style.css) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700">

    {{-- Bootstrap + Font Awesome from CDN (no build step) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    {{-- Custom theme — must come AFTER Bootstrap so overrides take effect.
         url() detects the current request root, so it works for both
         XAMPP (localhost/work-manage/public/...) AND artisan serve (127.0.0.1:8000/...) --}}
    <link rel="stylesheet" href="{{ url('assets/style.css') }}">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                    <span class="wm-logo">W</span>
                    <span class="fw-semibold">{{ config('app.name', 'Laravel') }}</span>
                </a>

                {{-- Mobile: theme toggle stays visible next to the hamburger --}}
                <div class="d-flex align-items-center gap-1 d-md-none ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-secondary border-0 wm-theme-toggle"
                            title="Toggle theme" aria-label="Toggle theme">
                        <i class="fa-solid fa-moon wm-theme-icon-dark"></i>
                        <i class="fa-solid fa-sun d-none wm-theme-icon-light"></i>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    {{-- Left --}}
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                                    <i class="fa-solid fa-gauge-high me-1"></i>Dashboard
                                    @if ($navMyOpenCount > 0)
                                        <span class="badge text-bg-primary ms-1" title="Your open tasks">{{ $navMyOpenCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('kanban') ? 'active' : '' }}" href="{{ route('kanban') }}">
                                    <i class="fa-solid fa-table-columns me-1"></i>Kanban
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}">
                                    <i class="fa-regular fa-calendar me-1"></i>Calendar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('categories.*') || request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                    <i class="fa-solid fa-users me-1"></i>Teams
                                </a>
                            </li>
                        @endauth
                    </ul>

                    {{-- Right --}}
                    <ul class="navbar-nav ms-auto align-items-md-center">
                        {{-- Dark mode toggle (desktop) --}}
                        <li class="nav-item d-none d-md-block">
                            <button type="button" class="btn btn-sm btn-outline-secondary border-0 wm-theme-toggle"
                                    title="Toggle theme" aria-label="Toggle theme">
                                <i class="fa-solid fa-moon wm-theme-icon-dark"></i>
                                <i class="fa-solid fa-sun d-none wm-theme-icon-light"></i>
                            </button>
                        </li>

                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                    <span class="badge text-bg-{{ Auth::user()->role->color() }}">{{ Auth::user()->role->label() }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    @if (Auth::user()->canManage())
                                        <a class="dropdown-item" href="{{ route('templates.index') }}">
                                            <i class="fa-solid fa-clipboard-list me-2"></i>{{ __('Activity Templates') }}
                                        </a>
                                    @endif
                                    @if (Auth::user()->isAdmin())
                                        <a class="dropdown-item" href="{{ route('admin.users.index') }}">
                                            <i class="fa-solid fa-users-gear me-2"></i>{{ __('Manage Users') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.activity.index') }}">
                                            <i class="fa-solid fa-list-ul me-2"></i>{{ __('Activity Log') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.reports.index') }}">
                                            <i class="fa-solid fa-chart-line me-2"></i>{{ __('Reports') }}
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i>{{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    {{-- Bootstrap JS (dropdowns/collapse/modals) + SortableJS (kanban drag-drop) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        // SortableJS UMD build exposes the global already; ensure window.Sortable
        // for any inline scripts that read it.
        if (typeof Sortable !== 'undefined') window.Sortable = Sortable;
    </script>

    {{-- Password show/hide toggle — any <button class="wm-password-toggle" data-target="<input-id>"> --}}
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.wm-password-toggle');
            if (!btn) return;
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
                btn.setAttribute('aria-label', 'Show password');
            }
        });
    </script>

    {{-- Theme toggle: switch + persist + reflect icon (works for both desktop + mobile buttons) --}}
    <script>
        (function () {
            function current() { return document.documentElement.getAttribute('data-bs-theme') || 'light'; }
            function reflect() {
                var dark = current() === 'dark';
                document.querySelectorAll('.wm-theme-icon-dark').forEach(function (el) { el.classList.toggle('d-none', dark); });
                document.querySelectorAll('.wm-theme-icon-light').forEach(function (el) { el.classList.toggle('d-none', !dark); });
            }
            reflect();
            document.querySelectorAll('.wm-theme-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var next = current() === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-bs-theme', next);
                    localStorage.setItem('wm-theme', next);
                    reflect();
                });
            });
        })();
    </script>
</body>
</html>
