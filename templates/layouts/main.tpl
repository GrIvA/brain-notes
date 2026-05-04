<!DOCTYPE html>
<html lang="uk" data-theme="{$common.theme}">
    <head>
        <title>{($common.title_of_page ?: 'Brain notes')|translate}</title>
        <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">

        <script src="/js/htmx.min.js"></script>
        <script src="/js/alpine.min.js" defer></script>

        <script>
            window.BN_PAGE_ID = '{$common.page_id}';
            window.BN_THEME = '{$common.theme}';
        </script>

        <!--<script src="/js/tools/ic.min.js"></script>-->
        <!--<script src="/js/tools/ic.js"></script>-->
        {block 'js'}{/block}

        <link rel="stylesheet" href="/css/fontawesome-6.5.1.min.css">
        <!-- Підключення Pico CSS v2 з темою Slate -->
        <link rel="stylesheet" href="/css/pico.custom.min.css">
        <link rel="stylesheet" href="/css/custom.css">
        {block 'css'}{/block}
    </head>
    <body x-data="{ 
        toasts: [],
        addToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3000);
        }
    }" @toast.window="addToast($event.detail.message, $event.detail.type)">
        <!-- Toasts Container -->
        <div class="toast-container">
            <template x-for="toast in toasts" :key="toast.id">
                <div class="toast" x-text="toast.message" :style="toast.type === 'error' ? 'border-left-color: var(--pico-form-element-invalid-border-color)' : ''"></div>
            </template>
        </div>
        <!-- Header -->
        <header class="container">
            {insert 'header/header.tpl'}
        </header>

        <!-- Main Container -->
        <main class="container layout-with-sidebar">
        
            <!-- Sidebar -->
            {insert 'side/side.tpl'}
        
            {block 'content'}
            {/block}

        </main>

        <!-- Footer -->
        <footer class="container">
            <hr>
            <small>© {$.php.date('Y')} Всі права захищені • <a href="#">Політика конфіденційності</a></small>
        </footer>

        <!-- Оверлей для мобільного -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- Контейнер для модальних вікон -->
        <div id="modal-container"></div>

        <!-- Common scripts -->
        {insert 'scripts.tpl'}
        {block 'footerJS'}
        {/block}
    </body>
</html>
