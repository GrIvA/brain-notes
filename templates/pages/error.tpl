{extends 'layouts/empty.tpl'}

{block 'content'}
<article>
    <header>
        <hgroup>
            <h1>Помилка {$code}</h1>
            <h2>{$title}</h2>
        </hgroup>
    </header>
    
    <p>{$message}</p>

    {if $trace}
        <details>
            <summary>Технічні деталі (Stack Trace)</summary>
            <pre style="font-size: 0.8rem; overflow-x: auto;">{$trace}</pre>
        </details>
    {/if}

    <footer>
        <div class="grid">
            <a href="/" class="outline">На головну</a>
            {if $code == 403 && !$user}
                <a href="/login" role="button">Увійти</a>
            {/if}
        </div>
    </footer>
</article>
{/block}
