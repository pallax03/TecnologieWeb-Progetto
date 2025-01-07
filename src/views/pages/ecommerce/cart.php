<section aria-labelledby="cart-info">
    <i class="bi bi-bag-fill"></i>
    <h1>Cart</h1>
</section>
<?php if (isset($cart) && count($cart) > 0): ?>
    <section id="sec-cart" class="cards">
        <?php
            foreach ($cart as $item) {
                include COMPONENTS . 'cards/cart.php';
            }
        ?>
    </section>
    <div class="large button">
        <i class="bi bi-credit-card-fill"></i>
        <button id="btn-cart_submit" onclick="redirect('/checkout')" >Checkout - <?php echo $total ?> €</button>
    </div>
<?php else: ?>
    <div class="div"></div>
    <div class="container center vertical">
        <h2>No vinyls in the cart!</h2>
        <a href="/">Go to Shop!</a>
    </div>
<?php endif; ?>
<script src="/resources/js/cart.js"></script>