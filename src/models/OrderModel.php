<?php
final class OrderModel
{

    private $vinyls_model = null;
    private $auth_model = null;
    private $user_model = null;


    public static $ShippingCourier = 'Poste Italiane';
    public static $ShippingCost = 5.0;
    public static $ShippingGoal = 100.0;

    public function __construct() {
        require_once MODELS . 'VinylsModel.php';
        $this->vinyls_model = new VinylsModel();

        require_once MODELS . 'AuthModel.php';
        $this->auth_model = new AuthModel();

        require_once MODELS . 'UserModel.php';
        $this->user_model = new UserModel();
    }

    /**
     * Get all orders of a user
     * An admin can get any user orders
     * A user can only get his own orders
     *
     * @param int $id_user, if null, return the logged user
     * @return array of orders
     */
    public function getOrders($id_user = null) {
        if (!Session::haveAdminUserRights($id_user)) {
            return [];
        }

        $orders = Database::getInstance()->executeResults(
            "SELECT o.id_order,
                    o.order_date,
                    o.total_cost,
                    o.order_status,
                    s.tracking_number,
                    s.shipment_date,
                    s.delivery_date,
                    s.shipment_status,
                    s.courier,
                    s.notes,
                    s.cost AS shipment_cost,
                    ad.name AS address_name,
                    ad.city AS address_city,
                    ad.postal_code AS address_postal_code,
                    ad.street_number AS address_street_number
                FROM `vinylsshop`.`orders` o
                JOIN `vinylsshop`.`shipments` s ON o.id_order = s.id_order
                JOIN `vinylsshop`.`addresses` ad ON s.id_address = ad.id_address
                WHERE o.id_user = ?
                ORDER BY o.order_date DESC;",
            'i',
            $id_user ?? Session::getUser()
        );

        foreach ($orders as $key => $order) {
            $orders[$key]['vinyls'] = Database::getInstance()->executeResults(
                "SELECT 
                        v.id_vinyl,
                        v.cost,
                        a.title,
                        a.cover
                    FROM `vinylsshop`.`checkouts` co
                    JOIN `vinylsshop`.`vinyls` v ON co.id_vinyl = v.id_vinyl
                    JOIN `vinylsshop`.`albums` a ON v.id_album = a.id_album
                    WHERE co.id_order = ?;",
                'i',
                $order['id_order']
            );
        }
        return $orders;
    }

    /**
     * Get a specific order by id
     * A user can only get his own orders
     * An admin can get any order
     *
     * @param int|null $id_user, if null, return the logged user
     * @param int|null $id_order, if null, return the last order
     * @return array of order
     */
    public function getOrder($id_order=null, $id_user=null) {
        if (!Session::haveAdminUserRights($id_user)) {
            return [];
        }

        if ($id_order === null) {
            $id_order = Database::getInstance()->executeResults(
                "SELECT id_order FROM `vinylsshop`.`orders` WHERE id_user = ? ORDER BY order_date DESC LIMIT 1;",
                'i',
                $id_user ?? Session::getUser()
            )[0]['id_order'];
        }

        $order = Database::getInstance()->executeResults(
            "SELECT o.id_order,
                    o.order_date,
                    o.total_cost,
                    o.order_status,
                    o.id_card,
                    c.card_number,
                    o.discount_code,
                    p.percentage AS discount_percentage,
                    s.tracking_number,
                    s.shipment_date,
                    s.delivery_date,
                    s.shipment_status,
                    s.courier,
                    s.notes,
                    s.cost AS shipment_cost,
                    ad.name AS address_name,
                    ad.city AS address_city,
                    ad.postal_code AS address_postal_code,
                    ad.street_number AS address_street_number
                FROM `vinylsshop`.`orders` o
                JOIN `vinylsshop`.`shipments` s ON o.id_order = s.id_order
                JOIN `vinylsshop`.`addresses` ad ON s.id_address = ad.id_address
                LEFT JOIN `vinylsshop`.`cards` c ON o.id_card = c.id_card
                LEFT JOIN `vinylsshop`.`coupons` p ON o.discount_code = p.discount_code
                WHERE o.id_user = ? AND o.id_order = ?;",
            'ii',
            $id_user ?? Session::getUser(),
            $id_order
        )[0];

        $order['vinyls'] = Database::getInstance()->executeResults(
            "SELECT co.quantity,
                    v.id_vinyl,
                    v.cost,
                    v.type,
                    v.rpm, 
                    v.inch, 
                    a.genre, 
                    a.title, 
                    a.cover, 
                    t.name AS artist_name 	
                FROM `vinylsshop`.`checkouts` co 
	            JOIN `vinylsshop`.`vinyls` v ON co.id_vinyl = v.id_vinyl
	            JOIN `vinylsshop`.`albums` a ON v.id_album = a.id_album 
	            JOIN `vinylsshop`.`artists` t ON a.id_artist = t.id_artist 
	            WHERE co.id_order = ?;",
            'i',
            $id_order
        );

        return $order;
    }

    /**
     * Set a shipping for an order.
     *
     * @return bool
     */
    public function setShipping($id_order, $tracking_number = null, $shipment_date = null, $delivery_date = null, $shipment_status = null, $courier = null, $notes = null, $cost = null) {
        if (!Session::haveAdminUserRights() || $id_order === null || !isset(Session::getUser()['default_address'])) {
            return false;
        }

        return Database::getInstance()->executeQueryAffectRows(
            "INSERT INTO `vinylsshop`.`shipments` (id_order, tracking_number, shipment_date, delivery_date, shipment_status, courier, notes, cost, id_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);",
            'issssssdi',
            $id_order,
            $tracking_number ?? bin2hex(random_bytes(6)),
            $shipment_date ?? date('Y-m-d'),
            $delivery_date ?? date('Y-m-d', strtotime('+7 days')),
            $shipment_status ?? 'In preparation',
            $courier ?? self::$ShippingCourier,
            $notes ?? '',
            $cost ?? self::$ShippingCost,
            Session::getUser()['default_address']
        );
    }

    private function loadOrderCart($id_order, $cart) {
        foreach ($cart as $vinyl) {
            if (!Database::getInstance()->executeQueryAffectRows(
                "INSERT INTO `vinylsshop`.`checkouts` (id_order, id_vinyl, quantity) VALUES (?, ?, ?);",
                'iii',
                $id_order,
                $vinyl['vinyl']['id_vinyl'],
                $vinyl['quantity']
            )) {
                return false;
            }
        }
    }

    private function deleteOrderCart($id_order) {
        return Database::getInstance()->executeQueryAffectRows(
            "DELETE FROM `vinylsshop`.`orders` WHERE id_order = ?;",
            'i',
            $id_order
        );
    }

    private function checkDiscount($discount_code) {
        $discount = Database::getInstance()->executeResults(
            "SELECT `percentage`
                FROM `coupons`
                WHERE `discount_code` = ?
                AND CURDATE() BETWEEN `valid_from` AND `valid_until`;",
            's',
            $discount_code
        )[0];

        if (empty($discount)) {
            return false;
        }

        return $discount['percentage'];
    }

    private function tryPayment($total) {
        if (Session::getUser()['default_card'] === null) {
            return Session::getUser()['balance'] >= $total;
        } 
        return true;
    }

    /**
     * Get the total of the order.
     *
     * @return float
     */
    public function getOrderTotal($discount_code = null) {
        $total = Session::getTotal();
        if ($discount_code !== null) {
            $discount = $this->checkDiscount($discount_code);
            $total = $total - ($total * $discount / 100);
        }

        return $total <= self::$ShippingGoal ? $total + self::$ShippingCost : $total;
    }

    /**
     * Set an order from the cart.
     *
     * @return bool
     */
    public function setOrder($discount_code = null, $notes = null) {
        $cart = Session::getCart();

        if (empty($cart)) {
            return false;
        }

        $total = $this->getOrderTotal($discount_code);
        if($this->tryPayment($total)) {
            return false;
        }
        

        if (!Database::getInstance()->executeQueryAffectRows(
            "INSERT INTO `vinylsshop`.`orders` (order_date, total_cost, order_status, id_user, id_card, discount_code) 
                VALUES (?, ?, ?, ?, ?, ?); ",
            'sdsiss',
            date('Y-m-d'),
            $total,
            'In preparation',
            Session::getUser(),
            Session::getUser()['default_card'] ?? '',
            $discount_code ?? '',
        )) {
            return false;
        }

        $id_order = Database::getInstance()->executeResults("SELECT LAST_INSERT_ID() AS id_order;")[0]['id_order'];
        if(!$this->loadOrderCart($id_order, $cart)) {
            $this->deleteOrderCart($id_order);
            return false;
        }
        
        if(!$this->setShipping($id_order, notes: $notes)) {
            return false;
        }

        return true;
    }
}
