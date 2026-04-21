{extends 'layouts/main.tpl'}

{block 'content'}
<article style="max-width: 400px; margin: 2rem auto;" 
         x-data="{ 
            loading: false, 
            password: '',
            get isValid() { return this.password.length >= 6 }
         }">
    <header>
        <h3>Вхід</h3>
    </header>

    <form hx-post="/login" 
          hx-swap="none"
          @htmx:before-request="loading = true"
          @htmx:after-request="loading = false"
          {ignore}@htmx:response-error="$dispatch('toast', {message: $event.detail.xhr.responseText, type: 'error'}); password = ''"{/ignore}>
        
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="example@mail.com" required>
        
        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" 
               x-model="password"
               placeholder="••••••••" required>
        <small x-show="password.length > 0 && !isValid" style="color: var(--pico-form-element-invalid-border-color)">
            Пароль має бути не менше 6 символів
        </small>
        
        <button type="submit" 
                :aria-busy="loading" 
                :disabled="loading || !isValid">Увійти</button>
    </form>
    <footer>
        <p>Немає аккаунту? <a href="/register">Зареєструватися</a></p>
    </footer>
</article>
{/block}
