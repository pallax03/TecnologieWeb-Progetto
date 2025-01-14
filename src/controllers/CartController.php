<?php
class CartController extends Controller {

    private $auth_model = null;
    private $user_model = null;
    private $cart_model = null;
    private $order_model = null;

    public function __construct() {
        require_once MODELS . 'AuthModel.php';
        $this->auth_model = new AuthModel();

        require_once MODELS . 'UserModel.php';
        $this->user_model = new UserModel();

        require_once MODELS . 'CartModel.php';
        $this->cart_model = new CartModel();

        require_once MODELS . 'OrderModel.php';
        $this->order_model = new OrderModel();

        $this->auth_model->checkAuth();
    }

    public function index(Request $request, Response $response) {
        $this->redirectSuperUser();

        $head = array('title' => 'Cart', 'style'=> array(''),
         'header' => "Oltre i " . $_ENV['SHIPPING_GOAL'] . "€ spedizione gratuita!");
        
        $data['user'] = $this->user_model->getUser();
        $data['cart'] = $this->cart_model->getCart();
        $data['total'] = Session::getTotal();

        $this->render('ecommerce/cart', $head, $data);
    }

    public function manage(Request $request, Response $response) {
        $body = $request->getBody();
        $old_quantity = Session::getVinylFromCart($body['id_vinyl']);
        if(isset($body['id_vinyl']) && isset($body['quantity']) && $this->cart_model->setCart($body['id_vinyl'], $body['quantity'])) {
            if($old_quantity === Session::getVinylFromCart($body['id_vinyl'])) {
                $response->Error('Vinyl cannot be added');
                return;
            }
            $response->Success('Cart updated');
            return;
        }
        $response->Error('Cart not updated', $body);
    }

    public function get(Request $request, Response $response) {
        $response->Success(['cart' => $this->cart_model->getCart(), 'total' => Session::getTotal()]);
    }

    public function sync(Request $request, Response $response) {
        if($this->cart_model->syncCart()) {
            $response->Success('Cart synced');
            return;
        }
        $response->Error('Cart not synced');
    }

    public function checkout(Request $request, Response $response) {
        $this->redirectUser();
        $this->redirectIf(empty($this->cart_model->getCart()), '/cart');

        $head = array('title' => 'Checkout', 'style'=> array(''));
        $data['user'] = $this->user_model->getUser(Session::getUser());
        $data['cart'] = $this->cart_model->getCart();
        $data['shipping'] = ['cost' => Session::getTotal() < $_ENV['SHIPPING_GOAL'] ? $_ENV['SHIPPING_COST'] : 0, 'courier' => $_ENV['SHIPPING_COURIER']];
        $data['total'] = $this->order_model->getOrderTotal();

        $this->render('ecommerce/checkout', $head, $data);
    }

    public function getTotal(Request $request, Response $response) {
        $body = $request->getBody();
        if(!Session::isLogged() || empty(Session::getCart())) {
            $response->Error('User not logged');
            return;
        }
        $total = $this->order_model->getOrderTotal($body['discount_code'] ?? null);
        $percentage = $this->order_model->checkDiscount($body['discount_code'] ?? null);
        $difference = Session::getTotal() * $percentage;
        $response->Success(['total' => $total, 'difference' => $difference, 'percentage' => $percentage*100]);
    }

    public function pay(Request $request, Response $response) {
        if(!Session::getUser()) {
            $response->Error('User not logged');
            return;
        }
        $body = $request->getBody();
        if($this->order_model->setOrder($body['discount_code'] ?? null)) {
            $response->Success('Order placed', $body);
            return;
        }
        $response->Error('Order cannot be placed', $body);
    }

}
?>