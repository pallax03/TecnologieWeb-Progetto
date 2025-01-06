<?php
final class VinylController extends Controller {

    private $vinyl_model = null;

    function __construct() {
        require_once MODELS . '/VinylsModel.php';
        $this->vinyl_model = new VinylsModel();

        require_once MODELS . '/OrderModel.php';
    }
    
    function index(Request $request, Response $response) {
        $body = $request->getBody();
        $data['vinyl'] = $this->vinyl_model->getVinylDetails($body['id']);
        $data['suggested'] = $this->vinyl_model->getSuggested($body['id']);
        $head = array('title' => $data["vinyl"]["details"]["title"], 'style'=> array(''),
         'header' => "Oltre i " . $_ENV['SHIPPING_GOAL'] . "€ spedizione gratuita!");
        
        $this->render('ecommerce/vinyl', $head, $data);
    }

    function addVinyl(Request $request, Response $response) {
        $body = $request->getBody();
        if(!Session::isSuperUser()) {
            $response->Error('Not allowed to add vinyls', $body);
            return;
        }
        if ($this->vinyl_model->addVinyl($body['cost'], $body['rpm'], $body['inch'], $body['type'], $body['stock'], $body['album'], $body['artist'], $body['id_vinyl'] ?? null)) {
            $response->Success('Vinyl added / updated', $body);
            return;
        }
        $response->Error('Vinyl not added / updated', $body);
    }

    function deleteVinyl(Request $request, Response $response) {
        $body = $request->getBody();
        if(!Session::isSuperUser()) {
            $response->Error('Not allowed to delete vinyls', $body);
            return;
        }
        if ($body['id_vinyl'] && $this->vinyl_model->deleteVinyl($body['id_vinyl'])) {
            $response->Success('Vinyl deleted', $body);
            return;
        }
        $response->Error('Vinyl not deleted', $body);
    }
}
// TODO / TOREMOVE
    /*
    public function login(Request $request, Response $response) {
        // JWT token for admin users
        $body = $request->getBody();
        $result = $this->model->login($body['mail'], $body['password']);
        echo json_encode($result);
        // $response->redirect('/');
    }

    public function logout(Response $response) {
        // $this->model->logout();
        echo json_encode(['error' => 'Not implemented']);
    }
    */
