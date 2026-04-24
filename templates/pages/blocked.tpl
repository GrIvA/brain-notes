{extends 'layouts/main.tpl'}

{block 'content'}
<article class="container" style="max-width: 600px; margin-top: 5rem; text-align: center;">
    <header>
        <h1 style="color: var(--pico-error-color);">Доступ заблоковано</h1>
    </header>
    <p>Ваша IP адреса <strong>{$ip}</strong> була автоматично заблокована через підозрілу активність (занадто багато запитів до неіснуючих сторінок).</p>
    <p>Якщо ви вважаєте, що це помилка, будь ласка, зверніться до адміністратора:</p>
    <a href="mailto:{$contact}" class="contrast">{$contact}</a>
    <footer style="margin-top: 2rem;">
        <small>Код помилки: 403 Forbidden / IP_BLOCKED</small>
    </footer>
</article>
{/block}
