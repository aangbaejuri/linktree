<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI LinkTree</title>
    <link rel="icon" type="image/svg+xml" href="<?= $link_url ?>layout/bot-light.svg" id="app-favicon">
    <script>
        window.faviconPaths = {
            light: "<?= $link_url ?>layout/bot-light.svg",
            dark: "<?= $link_url ?>layout/bot-dark.svg"
        };

        const favicon = document.getElementById('app-favicon');
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            favicon.href = window.faviconPaths.dark;
        } else {
            document.documentElement.classList.remove('dark');
            favicon.href = window.faviconPaths.light;
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/aangbaejuri/tailboot@1.0.0/dist/tailboot.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap');
        body {
            font-family: 'Raleway', sans-serif;
        }
    </style>
</head>
<body class="antialiased min-h-screen relative">

    <div id="toastContainer" class="fixed top-5 right-5 z-[110] flex flex-col gap-3"></div>

    <div class="modal fade" id="universalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" id="universalModalDialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="modal-close" onclick="closeUniversalModal()">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="modal-body" id="modalBody"></div>
            </div>
        </div>
    </div>

    <div id="modalBackdrop" class="modal-backdrop fade" style="display: none;"></div>

    <div class="offcanvas" id="universalOffcanvas" tabindex="-1">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasTitle"></h5>
            <button type="button" class="offcanvas-close" onclick="closeUniversalOffcanvas()">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="offcanvas-body" id="offcanvasBody"></div>
    </div>

    <div class="min-h-screen app-main">
        <header class="app-panel border-b sticky top-0 z-50" style="border-color: var(--border);">
            <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-dark rounded-lg flex items-center justify-center shrink-0 shadow-sm">
                        <i data-lucide="bot" class="w-5 h-5"></i>
                    </div>
                    <h3 class="align-middle mb-0">LinkTree</h3>
                </div>
                <div class="flex items-center gap-3">
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-[var(--bg-hover)] border transition-colors" style="color: var(--text-secondary); border-color: var(--border);">
                        <span id="theme-icon-container" style="opacity: 0;"><i data-lucide="moon" class="w-4 h-4"></i></span>
                    </button>
                    <a href="https://github.com/aangbaejuri/tailboot" target="_blank" class="btn btn-sm btn-soft-secondary border">
                        <i data-lucide="github" class="w-4 h-4"></i>
                        GitHub
                    </a>
                </div>
            </div>
        </header>

        <div class="main-content max-w-7xl mx-auto px-6 py-4">
    