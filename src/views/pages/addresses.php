<section>
    <?php
    if (isset($addresses) && $addresses !== false) {
        echo "<h1>Addresses</h1>";
        foreach ($addresses as $address) {
            include COMPONENTS . '/cards/address.php';
        }
    } else {
        echo "<h1>No addresses found!</h1>";
    }
    ?>
    </ul>
</section>
<div class="div"></div>
<form action="/user/address" id="form-address" method="post" novalidate>
    <h2>Address</h2>
    <ul>
        <li>
            <label for="address_name">Name:</label>
            <input autocomplete="name" type="text" id="input-address_name" name="address_name" placeholder="Full name" required aria-required="true" aria-label="Name" />
        </li>
        <li>
            <label for="address_street">Street & Number:</label>
            <input autocomplete="street-address" type="text" id="input-address_street" name="address_street" placeholder="Via Example, 123" required aria-required="true" aria-label="Street and number" />
        </li>
        <li class="split">
            <label for="address_city">City:</label>
            <input autocomplete="address-level2" type="text" id="input-address_city" name="address_city" placeholder="City" required aria-required="true" aria-label="City" />
        </li>
        <li class="split">
        <label for="address_cap">CAP:</label>
        <input autocomplete="postal-code" type="text" id="input-address_cap" name="address_cap" placeholder="CAP" pattern="[0-9]{5}" required aria-required="true" aria-label="CAP" />
        </li>
        <li>
            <div class="large button">
                <i class="bi bi-geo-alt-fill"></i>
                <input type="button" id="btn-address_submit" aria-label="Add Address" value="Add Address" />
            </div>
        </li>
    </ul>
</form>
<script src="/resources/js/address.js"></script>