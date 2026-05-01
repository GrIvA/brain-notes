<dialog id="note-search-modal" open>
    <article style="max-width: 800px; width: 90%;">
        <header>
            <button aria-label="Close" rel="prev" @click="document.getElementById('note-search-modal').removeAttribute('open')"></button>
            <hgroup>
                <h3>Результати пошуку</h3>
                <p>Знайдено нотаток: {if $notes}{$notes|count}{else}0{/if}</p>
            </hgroup>
        </header>
        
        <div style="max-height: 60vh; overflow-y: auto; padding-right: 1rem;">
            {include 'components/note_list.tpl' notes=$notes}
        </div>
        
        <footer>
            <button class="secondary outline" @click="document.getElementById('note-search-modal').removeAttribute('open')">Закрити</button>
        </footer>
    </article>
</dialog>
