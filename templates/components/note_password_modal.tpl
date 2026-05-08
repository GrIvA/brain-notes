<dialog open id="decrypt-modal">
    <article>
        <header>
            <a href="#close" aria-label="Close" class="close" hx-on:click="document.getElementById('decrypt-modal').remove()"></a>
            Введення пароля
        </header>
        <form hx-post="/api/v1/notes/decrypt/{$noteId}" hx-target="#note-content-area" hx-swap="innerHTML">
            <div id="decrypt-error" style="color: var(--pico-form-element-invalid-border-color); margin-bottom: 1rem; font-weight: bold;"></div>
            <label for="password">Пароль до нотатки:</label>
            <input type="password" id="password" name="password" required autofocus>
            <footer>
                <button type="button" class="secondary outline" hx-on:click="document.getElementById('decrypt-modal').remove()">Скасувати</button>
                <button type="submit">Розшифрувати</button>
            </footer>
        </form>
    </article>
</dialog>
