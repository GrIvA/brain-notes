<dialog open id="notebook-manage-modal">
    <article style="width: 600px; max-width: 90vw;">
        <header>
            <a href="#close" aria-label="Close" class="close" hx-on:click="document.getElementById('notebook-manage-modal').remove()"></a>
            Керування зошитами
        </header>
        
        <table class="striped">
            <thead>
                <tr>
                    <th>Назва</th>
                    <th style="width: 150px;">Дії</th>
                </tr>
            </thead>
            <tbody>
                {foreach $notebooks as $nb}
                <tr>
                    <td>
                        {$nb.title}
                        {if $nb.attributes|attr:1}
                            <mark style="font-size: 0.7rem;">За замовчуванням</mark>
                        {/if}
                    </td>
                    <td>
                        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0;">
                            <button class="outline secondary" 
                                    style="padding: 0.2rem 0.4rem;" 
                                    hx-get="/api/v1/notebooks/edit-ui/{$nb.id}" 
                                    hx-target="#modal-container">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="outline contrast" 
                                    style="padding: 0.2rem 0.4rem;" 
                                    hx-delete="/api/v1/notebooks/{$nb.id}" 
                                    hx-confirm="Ви впевнені, що хочете видалити цей зошит з усіма розділами та нотатками?"
                                    hx-on::after-request="if(event.detail.successful) { document.dispatchEvent(new CustomEvent('refresh-sidebar')); }">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        <footer>
            <button class="outline" hx-get="/api/v1/notebooks/create-ui" hx-target="#modal-container">
                <i class="fas fa-plus"></i> Додати новий зошит
            </button>
            <button class="secondary outline" hx-on:click="document.getElementById('notebook-manage-modal').remove()">Закрити</button>
        </footer>
    </article>
</dialog>
