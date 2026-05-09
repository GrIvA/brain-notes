<dialog open id="notebook-form-modal">
    <article style="width: 500px; max-width: 90vw;">
        <header>
            <a href="#close" aria-label="Close" class="close" hx-on:click="document.getElementById('notebook-form-modal').remove()"></a>
            {if $mode == 'create'}Створення нового зошита{else}Редагування зошита{/if}
        </header>
        <form {if $mode == 'create'}hx-post="/api/v1/notebooks"{else}hx-patch="/api/v1/notebooks/{$notebook.id}"{/if} 
              hx-on::after-request="if(event.detail.successful) { document.getElementById('notebook-form-modal').remove(); document.dispatchEvent(new CustomEvent('refresh-sidebar')); }">
            
            <label for="nb-title">Назва</label>
            <input type="text" id="nb-title" name="title" value="{if $mode == 'edit'}{$notebook.title}{/if}" placeholder="Введіть назву..." required autofocus>

            <label>
                <input type="checkbox" name="attributes" value="1" {if $mode == 'edit' && $notebook.attributes|attr:1}checked{/if}>
                Використовувати за замовчуванням
            </label>

            <footer>
                <button type="button" class="secondary outline" hx-on:click="document.getElementById('notebook-form-modal').remove()">Скасувати</button>
                <button type="submit">{if $mode == 'create'}Створити{else}Зберегти{/if}</button>
            </footer>
        </form>
    </article>
</dialog>
