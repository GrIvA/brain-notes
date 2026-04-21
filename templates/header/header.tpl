<nav>
    <ul>
        <li>
            <button id="toggle-sidebar" class="outline contrast" aria-label="Меню" style="padding: 0.25rem 0.5rem; margin-bottom: 0;">
                ☰
            </button>
            <a href="/{'home'|getPageURL}" style="display: inline-block; vertical-align: middle;">
                <img id="brain-slogan" src="/logo_text.png" alt="slogan" style="margin-bottom: 0;">
            </a>
        </li>
    </ul>
    <ul>
        {*<li><a href="https://picocss.com/docs" target="_blank" class="secondary">Документація</a></li>*}
        <li>
            <details class="dropdown lang-switcher">
                <summary class="outline contrast" aria-haspopup="listbox" role="button">
                    {$common.languages[$common.language_id].abr|upper}
                </summary>
                <ul role="listbox">
                    {foreach $common.languages as $langId => $info}
                        <li>
                            <a href="{$info.href}" {if $langId == $common.language_id}aria-current="true"{/if}>
                                {$info.title}
                            </a>
                        </li>
                    {/foreach}
                </ul>
            </details>
        </li>
        <li>
            <button id="theme-switcher" class="outline contrast" aria-label="Змінити тему" style="padding: 0.25rem 0.5rem; margin-bottom: 0; display: flex; align-items: center;">
                <svg id="theme-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <!-- SVG контент буде вставлений через JS -->
                </svg>
            </button>
        </li>
        {if $user}
            <li>
                
            </style>
                <button style="padding: 0.1rem 0.5rem;" hx-post="/logout" class="outline secondary">Вихід</button>
            </li>
        {elseif $common.page_id != 3 && $common.page_id != 4}
            <li><a style="padding: 0.1rem 0.5rem;" href="/login" role="button">Вхід</a></li>
        {/if}

    </ul>
</nav>
