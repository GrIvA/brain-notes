<!DOCTYPE html>
<html lang="uk" data-theme="{$common.theme}">
    <head>
        <title>Security error</title>
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

        {block 'js'}{/block}

        <link rel="stylesheet" href="/css/fontawesome-all.min.css">
        <!-- Підключення Pico CSS v2 з темою Slate -->
        <link rel="stylesheet" href="/css/pico.custom.min.css">
        <link rel="stylesheet" href="/css/custom.css">
        {block 'css'}{/block}
    </head>
    <body>
        <!-- Main Container -->
        <main class="container layout-with-sidebar">
            {block 'content'}
            {/block}
        </main>

        <!-- Footer -->
        <footer class="container">
            <hr>
            <small>© {$.php.date('Y')} Всі права захищені • <a href="#">Політика конфіденційності</a></small>
        </footer>

        <!-- Common scripts -->
        {insert 'scripts.tpl'}
        {block 'footerJS'}
        {/block}
    </body>
</html>
