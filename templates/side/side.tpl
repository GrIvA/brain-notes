<aside id="sidebar">
    <div class="sidebar-logo">
        <a href="/{'home'|getPageURL}" style="display: block; text-align: center;">
            <img src="/logo2.png" alt="Logo">
        </a>
        <hr>
    </div>

    <nav>
        {if $user}
            <div style="padding: 0 0.5rem 1rem;">
                <strong>Вітаємо, {$user.name}!</strong>
            </div>
        {/if}
        <ul>
            <li><strong>Навігація</strong></li>
            <li><a href="#">🏠 Головна</a></li>
            <li><a href="#">📊 Дашборд</a></li>
            <li><a href="#">⚙️ Налаштування</a></li>
        </ul>
        <hr>
        <section>
            <small>
                <strong>Службова інфо:</strong><br>
                Статус: <mark>Online</mark><br>
                Версія: 1.2.4
            </small>
        </section>
        <hr>
        <section>
            <small>
                <strong>Контакти:</strong><br>
                📞 +380 44 000 00 00<br>
                📧 <a href="mailto:info@example.com">info@example.com</a>
            </small>
        </section>
    </nav>
</aside>
