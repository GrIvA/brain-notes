<form hx-put="/api/v1/notes/{$note.id}" hx-target="#note-content-area" hx-swap="innerHTML">
    <textarea name="content" rows="15" style="width: 100%; font-family: monospace; margin-bottom: 1rem;" autofocus>{$note.content}</textarea>
    <div class="grid">
        <button type="submit" class="primary">Зберегти</button>
        <button type="button" class="secondary outline" 
                hx-get="/api/v1/notes/{$note.id}/view-fragment" 
                hx-target="#note-content-area" 
                hx-swap="innerHTML">
            Скасувати
        </button>
    </div>
</form>
