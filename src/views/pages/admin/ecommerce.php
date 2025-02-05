<section>
    <h1>Ecommerce Dashboard</h1>
    <div class="callout">
        <p>Here you can manage the shippings settings, and coupons.</p>
    </div>
</section>
<div class="div"></div>
<form action="/shipping" method="post">
    <h2>Shipping Info</h2>
    <ul>
        <li>
            <label for="input-shipping_courier">Courier</label>
            <input type="text" id="input-shipping_courier" name="shipping_courier" value="<?php echo $_ENV['SHIPPING_COURIER'] ?>" id="input-shipping_courier">
        </li>
        <li class="split">
            <label for="input-shipping_cost">Cost</label>
            <input type="text" id="input-shipping_cost" name="shipping_cost" value="<?php echo $_ENV['SHIPPING_COST'] ?>" id="input-shipping_cost">
        </li>
        <li class="split">
            <label for="input-shipping_goal">Goal</label>
            <input type="text" id="input-shipping_goal" name="shipping_goal" value="<?php echo $_ENV['SHIPPING_GOAL'] ?>" id="input-shipping_goal">
        </li>
        <li>
            <div class="large button">
                <i class="bi bi-truck"></i>
                <input type="button" id="btn-shipping_submit" aria-label="Set Shipping" value="Set Shipping" />
            </div>
        </li>
    </ul>
</form>
<div class="div"></div>
<?php
$editable = 1;
include PAGES . 'ecommerce/coupons.php';
?>
<div class="div"></div>
<form action="/coupon" id="form-coupon" method="post">
    <h2>Coupon</h2>
    <ul>
        <li>
            <label for="input-discount_code">Code</label>
            <input type="text" name="discount_code" id="input-discount_code" placeholder="EXAMPLE10" aria-required="true" />
        </li>
        <li>
            <label for="input-percentage">Percentage</label>
            <input type="text" name="percentage" id="input-percentage" placeholder="%" aria-required="true" />
        </li>
        <li class="split">
            <label for="input-valid_from">From:</label>
            <input type="date" name="valid_from" id="input-valid_from" value="<?php echo date('Y-m-d'); ?>" aria-required="true" />
        </li>
        <li class="split">
            <label for="input-valid_until">Until:</label>
            <input type="date" name="valid_until" id="input-valid_until" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" aria-required="true" />
        </li>
        <li id="li-form_reset" class="split">
            <div class="button">
                <i class="bi bi-x"></i>
                <button class="close" type="button" id="btn-coupon_reset" aria-label="Reset Form">Reset</button>
            </div>
        </li>
        <li class="split">
            <div class="button">
                <i class="bi bi-percent"></i>
                <button type="button" id="btn-coupon_submit" aria-label="Add Coupon">Add</button>
            </div>
        </li>
    </ul>
    <div class="callout">
        <p>If edit pressed, and want to add press reset!</p>
    </div>
</form>
<script src="/resources/js/coupon.js"></script>