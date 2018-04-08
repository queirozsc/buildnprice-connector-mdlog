<?php
class User {
	private $_db,
		$_data,
		$_grupo,
		$_lista,
		$_empresas,
		$_filiais,
		$_setores,
		$_sessionName,
		$_cookieName,
		$_isLogged;

	public function __construct($user = null){
		$this->_db          = DB::getInstance('mysql');
		$this->_sessionName = Config::get('session/session_name');
		$this->_cookieName  = Config::get('remember/cookie_name');

		if (!$user) {
			if (Session::exists($this->_sessionName)) {
				$user = Session::get(Config::get('session/session_name'));
				if ($this->find($user)) {
					$this->_isLogged = true;
				} else {
					// proceed to logout...
				}
			}
		} else {
			$this->find($user);
		}
		// monta listas
		$this->listaEmpresas();
		$this->listaFiliais();
		$this->listaSetores();
	}

	private function listaEmpresas(){
		$this->_empresas = $this->_db->get('enterprises', array('id','>','0'))->results();
		if (count($this->_empresas)) {
			return true;
		}
		return false;
	}

	private function listaFiliais(){
		$this->_filiais = $this->_db->get('stores', array('id','>','0'))->results();
		if (count($this->_filiais)) {
			return true;
		}
		return false;
	}

	private function listaSetores(){
		$this->_setores = $this->_db->get('departments', array('id','>','0'))->results();
		if (count($this->_setores)) {
			return true;
		}
		return false;
	}

	public function create($fields = array()){
		if (!$this->_db->insert('users', $fields)) {
			throw new Exception("There was a problem creating your account!", 1);
		}
	}

	public function update($fields = array(), $id = null){

		if (!$id && $this->isLogged()) {
			$id = $this->data()->id;
		}
		if (!$this->_db->update('users', $id, $fields)) {
			throw new Exception("Houve um problema atualizando o registro!", 1);
		}
	}

	public function find($username=null, $mode=false){
		if ($username) {
			$field = (is_numeric($username)) ? 'id' : 'username';
			$data  = $this->_db->get('users', array($field,'=',$username));
			if ($data->count()) {
				$this->_data = $data->first();
				// seta grupo
				if ($mode) {
					$this->_grupo = new Group($this->_data->group_id);
				}
				return true;
			}
		}		
		return false;
	}

	public function login($username=null, $password=null, $remember=null){
		$user = $this->find($username);
		
		if (!$username && !$password && $this->exists()) {
			Session::put($this->_sessionName, $this->data()->id);
		} else {

			/*2017.04.01 - usuário precisa existir e estar ativo*/
			if ($user && $this->data()->active) {

				if ($this->data()->password === Hash::make($password, $this->data()->salt) ) {
					
					Session::put($this->_sessionName, $this->data()->id);

					if ($remember) {
						$hash     = Hash::unique();
						$hasCheck = $this->_db->get('users_session', array('user_id','=',$this->data()->id));

						if (!$hasCheck->count()) {

							$this->_db->insert('users_session', array(
								'user_id'=>$this->data()->id,
								'hash'   =>$hash
							));
						} else {
							$hash = $hasCheck->first()->hash;
						}
						Cookie::put($this->_cookieName, $hash, Config::get('remember/cookie_expire'));
					}
					return true;
				}
			}
		}
		return false;
	}

	public function hasPermission($key = null){
		$group = $this->_db->get('groups', array('id', '=', $this->data()->group_id));
		if ($group->count()) {
			$permissions = json_decode($group->first()->permissions, true);
			if ($permissions) {
				if (array_key_exists($key, $permissions)) {
					if ($permissions[$key] == true) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function exists(){
		return (!empty($this->_data)) ? true : false;
	}

	public function logout(){
		$this->_db->delete('users_session', array('user_id','=',$this->data()->id));
		Session::delete($this->_sessionName);
		Cookie::delete($this->_cookieName);
	}

	public function listaUsuario(){
		$this->_db->pdo()->exec("set names utf8");
		$this->_lista = $this->_db->get('users', array('id','>',0))->results();
		if (count($this->_lista)) {
			return true;
		}
		return false;
	}

	public function grupo($id){
		if ($id) {
			$grupo = $this->_db->get('groups',array('id', '=', $id));
			if ($grupo->count()) {
				$this->_grupo = $grupo->first();
				return $this->_grupo;
			}
		}
		return false;
	}

	public function create_fav($user_id, $page, $description) {
		$views = $this->check_fav($user_id, $page);
		$count = $views[0]->count;
		$fields = array(
			'user_id' => $user_id,
			'page' => $page,
			'description' => $description,
			'count' => $count+1
		);
		if ($count) {
			if (!$this->_db->update('users_fav', $views[0]->id, $fields)) {
				throw new Exception("Houve um problema atualizando o registro!", 1);
			}
		} else {
			if (!$this->_db->insert('users_fav', $fields)) {
				throw new Exception("Houve um problema incluindo o registro!", 1);
			}
		}
	}

	public function check_fav($user_id, $page) {
		$query = "select id, count from users_fav ";
		$query.= "where ";
		$query.= " user_id = ".$user_id;
		$query.= " and page = '".$page."'";
		$count = $this->_db->query($query)->results();
		return $count;
	}

	public function list_fav($id = null) {
		$user_id = ($id) ? $id : $this->data()->id;
		$query = "select * from users_fav where user_id = $user_id order by count desc limit 10";
		$pages = $this->_db->query($query)->results();
		return $pages;
	}

	public function clear_fav($id = null) {
		$user_id = ($id) ? $id : $this->data()->id;
		$sql = "delete from users_fav where user_id = $user_id";
		$erro = $this->_db->query($sql)->error();
		return !$erro;
	}

	public function all(){
		return $this->_lista;
	}

	public function data(){
		return $this->_data;
	}

	public function isLogged(){
		return $this->_isLogged;
	}

	public function empresas($id=null){
		if ($id===null) {
			return $this->_empresas;
		} else {
			return ($id > 0) ? $this->_empresas[$id-1]->name : '';
		}
		
	}

	public function filiais($id=null){
		if ($id===null) {
			return $this->_filiais;
		} else {
			return ($id > 0) ? $this->_filiais[$id-1]->name : '';
		}
		
	}

	public function setores($id=null){
		if ($id===null) {
			return $this->_setores;
		} else {
			return ($id > 0) ? $this->_setores[$id-1]->name : '';	
		}
	}
	
	public function codigoFilial($id=null){
		$id = ($id) ? $id : $this->_data->store_id;
		return ($id > 0) ? $this->_filiais[$id-1]->loja : '';
	}

	public function codigoEmpresa($id=null){
		$id = ($id) ? $id : $this->_data->enterprise_id;
		return ($id >0 ) ? substr(strtolower($this->_empresas[$id-1]->name), 0, 1) : '';
	}
}