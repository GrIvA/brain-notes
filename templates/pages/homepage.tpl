{extends 'layouts/main.tpl'}

{block 'content'}
<section>
    <h2>This is home</h2>
    
    <div class="grid">
        <!-- Alpine.js Test -->
        <article x-data="{ count: 0 }">
            <header><strong>Alpine.js Test</strong></header>
            <p>Count: <span x-text="count"></span></p>
            <button @click="count++" class="outline">Increment</button>
        </article>

        <!-- HTMX Test -->
        <article>
            <header><strong>HTMX Test</strong></header>
            <div id="htmx-result">Click to load content...</div>
            <button hx-get="/hello" hx-target="#htmx-result" hx-swap="outerHTML" class="secondary">
                Load Hello Fragment
            </button>
        </article>
    </div>
</section>
{/block}


