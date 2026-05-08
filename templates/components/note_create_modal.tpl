<dialog open id="create-note-modal">
    <article style="width: 800px; max-width: 90vw;">
        <header>
            <a href="#close" aria-label="Close" class="close" hx-on:click="document.getElementById('create-note-modal').remove()"></a>
            Створення нової нотатки
        </header>
        <form hx-post="/api/v1/notes" hx-target="#modal-container" hx-swap="none">
            <input type="hidden" name="section_id" value="{$sectionId}">
            
            <label for="note-title">Заголовок</label>
            <input type="text" id="note-title" name="title" placeholder="Введіть назву..." required autofocus>

            <label for="note-content">Зміст (Markdown)</label>
            <textarea id="note-content" name="content" rows="10" placeholder="Ваш текст тут..."></textarea>

            <details>
                <summary class="outline secondary">Додаткові налаштування</summary>
                <div style="padding-top: 1rem;">
                    <label for="note-password">Зашифрувати (пароль)</label>
                    <input type="password" id="note-password" name="password" placeholder="Залиште порожнім, якщо не потрібно">
                </div>
            </details>

            <footer>
                <button type="button" class="secondary outline" hx-on:click="document.getElementById('create-note-modal').remove()">Скасувати</button>
                <button type="submit">Створити</button>
            </footer>
        </form>
    </article>
</dialog>
