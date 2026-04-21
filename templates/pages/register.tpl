{extends 'layouts/main.tpl'}

{block 'content'}
<article style="max-width: 400px; margin: 2rem auto;" 
         x-data="{ 
            loading: false, 
            password: '',
            name: '',
            get isValid() { return this.password.length >= 6 && this.name.length >= 2 }
         }">
    <header>
        <h3>Реєстрація</h3>
    </header>

    <form hx-post="/register" 
          hx-swap="none"
          @htmx:before-request="loading = true"
          @htmx:after-request="loading = false"
          {ignore}@htmx:response-error="$dispatch('toast', {message: $event.detail.xhr.responseText, type: 'error'})"{/ignore}>
        
        <label for="name">Ім'я</label>
        <input type="text" id="name" name="name" x-model="name" placeholder="Ваше ім'я" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="example@mail.com" required>
        
        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" 
               x-model="password"
               placeholder="••••••••" required>
        <small x-show="password.length > 0 && password.length < 6" style="color: var(--pico-form-element-invalid-border-color)">
            Пароль має бути не менше 6 символів
        </small>
        
        <button type="submit" 
                :aria-busy="loading" 
                :disabled="loading || !isValid">Зареєструватися</button>
    </form>
    <footer>
        <p>Вже є аккаунт? <a href="/login">Увійти</a></p>
    </footer>
</article>
{/block}
