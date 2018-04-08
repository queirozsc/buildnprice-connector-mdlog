<?php 
header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
header("Content-Type: application/json; charset=utf-8");

require_once 'core/init.php';

$post = json_decode(file_get_contents('php://input'));

$json = array('flag' => false, 'data' => null);
/* GETs */
if (Input::exists('get')) {
  
  $appid = Input::get('app_id');
  $token = Input::get('token');
  $api   = new Api();

  if ($appid == Config::get('appId')) {
    if ($api->check($token)) {

      switch (Input::get('mode')) {
        
        default:
          # code...
          break;
      }

    } else {
      $json['data'] = 'Token inválido!';
    }
  } else {
    $json['data'] = 'Application ID inválido!';
  }
/* POSTs */
} elseif ($post) {
  
  // var_dump($post); die();

  switch ($post->mode) {
    
    case 'logoff':
      $api = new Api();
      if ($api->destroy($post->token)) {
        $json['flag'] = true;
        $json['data'] = 'Token destruído';
      } else {
        $json['data'] = 'Token não foi destruido';
      }
      break;

    case 'login':
      
      $user = $post->username;
      $pass = $post->password; //json_decode(Input::get('password'));
      $u    = new User();
      $r    = $u->login($user,$pass);
      if ($r) {
        // guarda token da conexão API
        $api   = new Api();
        $token = null;
        $fields = array(
          'user_id' => $u->data()->id,
          'token' => Hash::unique()
        );
        $token = $api->generate($fields);
        if ($token) {
          $data = array(
            'id' => $u->data()->id,
            'username' => $u->data()->username,
            'name' => $u->data()->name,
            'enterprise_id' => $u->data()->enterprise_id,
            'enterprise_name' => $u->empresas($u->data()->enterprise_id),
            'enterprise_alias' => $u->codigoEmpresa(),
            'store_id' => $u->data()->store_id,
            'store_name' => $u->filiais($u->data()->store_id),
            'department_id' => $u->data()->department_id,
            'department_name' => $u->setores($u->data()->department_id),
            'token' => $token,
            'isAdmin' => $u->hasPermission('admin')
          );
          $json['flag'] = $r;
          $json['data'] = $data;
        } else {
          $json['data'] = 'Não foi possível gerar token';
        }
      } else {
        $json['data'] = 'Usuário/senha inválidos!';
      }
      break;
    
    case 'bnp_mdlog_get_list':
      $empresa = 'p'; //Input::get('empresa');
      $appid = $post->app_id;
      $token = $post->token;
      $produtos = $post->produtos; //json_decode(Input::get('produtos'));

      $data = array();
      $api  = new Api();
      if ($appid == Config::get('appId')) {
        if ($api->check($token)) {

          $mdlog = new Mdlog($empresa);
          if (count($produtos)) {
            foreach ($produtos as $produto) {
              $result = null;
              if($mdlog->consultaPrecoEstoque($produto)){
                $result = $mdlog->data();
              }
              array_push($data, array(
                'ean' => $produto,
                'data' => $result
              ));
            }
          }
          
        }
      }
      $json['flag'] = count($data) ? true:false;
      $json['data'] = $data;
      break;
    
    case 'bnp_mdlog_get_product_info':
      $empresa = 'p'; //Input::get('empresa');
      $appid = $post->app_id;
      $token = $post->token;
      $produto = $post->produto; //json_decode(Input::get('produtos'));

      $data = null;
      $api  = new Api();
      if ($appid == Config::get('appId')) {
        if ($api->check($token)) {

          $mdlog = new Mdlog($empresa);
          if ($produto) {
            
            if($mdlog->consultaProdutos($produto)){
              $data = $mdlog->produtos();
            }
          }
          
        }
      }
      $json['flag'] = isset($data) ? true:false;
      $json['data'] = $data;
      break;

    case 'bnp_mdlog_get_products':
      $empresa = 'p'; //Input::get('empresa');
      $appid = $post->app_id;
      $token = $post->token;
      $produto = $post->produto;
      $fornecedor = $post->fornecedor;
      $segmento = $post->segmento;
      $cEOL = chr(13).chr(10);
      $path = dirname(__FILE__);
      $filename = '/media/bnp_mdlog_get_products.json';

      $api = new Api();
      if ($appid == Config::get('appId')) {
        if ($api->check($token)) {
          $mdlog = new Mdlog($empresa);
          if($mdlog->listaProdutosReduzido($produto, $fornecedor, $segmento, null)){
            $data = $mdlog->data();
            $file = fopen($path.$filename,'w');
            foreach ($data as $value) {
              /*array_push($json, array('index' => array('_id' => $value->CODIGO)));
              array_push($json, $value);*/
              fwrite($file, 
                json_encode(
                  array(
                    'index' => array(
                      '_index'=>'supplier', 
                      '_type'=>'products', 
                      '_id' => $value->CODE
                    )
                  ), JSON_NUMERIC_CHECK
                ).$cEOL
              );
              fwrite($file, json_encode(array_change_key_case((array)$value,CASE_LOWER)).$cEOL);
            }
            fclose($file);
            // $data = $mdlog->data();
          }
        }
      }
      $json['flag'] = isset($data) ? true:false;
      $json['data'] = $_SERVER['HTTP_HOST'] . $filename;
      break;

    default:
      # code...
      break;
  }
}
echo json_encode($json, JSON_NUMERIC_CHECK);