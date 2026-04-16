<!DOCTYPE html>
<html lang="uk">
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

        <!--<script src="/js/tools/ic.min.js"></script>-->
        <!--<script src="/js/tools/ic.js"></script>-->
        {block 'js'}{/block}

        <link rel="stylesheet" href="/css/fontawesome-all.min.css">
        <!-- Підключення Pico CSS v2 з темою Slate -->
        <link rel="stylesheet" href="/css/pico.custom.min.css">
        {block 'css'}{/block}
    </head>
    <body>
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

        <!-- Common scripts -->
        {insert 'scripts.tpl'}
        {block 'footerJS'}
        {/block}
    </body>
</html>
