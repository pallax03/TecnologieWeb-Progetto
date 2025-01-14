<form id="form-register">
    <h1> Signup </h1>
    <ul>
        <li>
            <label for="input-register_mail">Mail</label>
            <input type="email" id="input-register_mail" name="mail" autocomplete="email" required />
        </li>
        <li>
            <label for="input-register_password">Password</label>
            <input type="password" id="input-register_password" name="password" required />
        </li>
        <li>
            <label class="checkbox" for="input-register_notifications">Notifications:
                <input type="checkbox" id="input-register_notifications" name="register_notifications" checked />
                <span class="checkmark"><i class="bi bi-check"></i></span>
            </label>
        </li>
        <li>
            <div class="large button">
                <i class="bi bi-person-fill"></i>
                <button class="animate" id="btn-register_submit">Register</button>
            </div>
        </li>
    </ul>
</form>