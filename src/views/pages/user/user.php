<?php if (Session::getUser()): ?>
    <section aria-labelledby="user-info">
        <i class="bi bi-person-fill"></i>
        <div class="container center vertical">
            <h4 id="user-mail"><?php echo $user['mail'] ?></h4>
            <?php if($user['notifications']):?>
                <a href="/notifications">
                    <p>See Notifications</p>
                    <i class="bi bi-bell-fill"></i>
                </a>
            <?php else: ?>
                <span>
                    <p>Notifications disabled</p>
                    <i class="bi bi-bell-slash-fill"></i>
                </span>
            <?php endif; ?>
        </div>
        <div class="container center margin-top">
            <a class="error no-border" href="/logout">
                <p class="error">Logout</p>
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
        <div class="div"></div>
    </section>
    <form aria-label="Defaults" id="form-user_defaults">
        <h2>Defaults</h2>
        <ul>
            <li>
                <label for="input-default_address"><i class="bi bi-geo-alt-fill"></i>
                    Address:</label>
                <input type="text" id="input-default_address" value="<?php echo (isset($user['default_address']) && !empty($user['default_address'])) ? ($user['name'] . ' - ' . $user['city'] . ' (' . $user['postal_code'] . ')') : 'no default address.' ?>" name="default_address" disabled />
                <a aria-label="User Addresses" href="user/addresses"><i class="bi bi-caret-right-fill"></i></a>
            </li>
            <li>
                <label for="input-default_card"><i class="bi bi-credit-card-fill"></i>
                    Card:</label>
                <input type="text" id="input-default_card" value="<?php echo (isset($user['default_card']) && !empty($user['default_card'])) ? ('**** **** **** ' . substr($user['card_number'], -4)) : ('Balance: ' . $user['balance'] . ' €') ?>" name="default_card" disabled />
                <a aria-label="User Cards" href="/user/cards"><i class="bi bi-caret-right-fill"></i></a>
            </li>
        </ul>
        <div class="div"></div>
    </form>
    <?php include PAGES . 'user/orders.php'; ?>
    <script src="/resources/js/user.js"></script>
<?php else: ?>
    <?php include COMPONENTS . 'login.php' ?>
    <?php include COMPONENTS . 'register.php' ?>
    <script src="/resources/js/auth.js"></script>
<?php endif; ?>