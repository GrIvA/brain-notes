<dialog open id="section-create-modal">
    <article style="width: 500px; max-width: 90vw;">
        <header>
            <a href="#close" aria-label="Close" class="close" hx-on:click="document.getElementById('section-create-modal').remove()"></a>
            Створення нового розділу
        </header>
        <form hx-post="/api/v1/sections" 
              hx-on::after-request="if(event.detail.successful) { document.getElementById('section-create-modal').remove(); document.dispatchEvent(new CustomEvent('refresh-sidebar')); }">
            
            <input type="hidden" name="notebook_id" value="{$notebookId}">
            {if $parentId}
                <input type="hidden" name="parent_id" value="{$parentId}">
            {/if}

            <label for="sec-title">Назва розділу</label>
            <input type="text" id="sec-title" name="title" placeholder="Введіть назву..." required autofocus>

            <label for="sec-sort">Порядок сортування</label>
            <input type="number" id="sec-sort" name="sort_order" value="0">

            <footer>
                <button type="button" class="secondary outline" hx-on:click="document.getElementById('section-create-modal').remove()">Скасувати</button>
                <button type="submit">Створити</button>
            </footer>
        </form>
    </article>
</dialog>
