<?php
class Api {

  private $_db, $_data;

  public function __construct(){
    $this->_db = DB::getInstance();
  }

  public function generate($fields){
    $user_id = $fields['user_id'];
    if (!$this->check($user_id)) {
      if (!$this->_db->insert('users_api_token', $fields)) {
        throw new Exception("Houve um problema gravando o token!", 1);
        return false; // deu merda, não retorna nada
      }
      return $fields['token']; // retorna o último gerado
    }
    return $this->data()->token; // retorna o já estocado
  }

  public function check($value){
    if (is_numeric($value)) {
      return $this->find('user_id', $value);
    } else {
      return $this->find('token', $value);
    }
  }

  public function destroy($hash){
    if ($this->check($hash)) {
      if (!$this->_db->delete('users_api_token', array('id','=',$this->data()->id))) {
        throw new Exception("Houve um problema destruindo o token!", 1);
        return false;
      }
      return true;
    }
    return true;
  }

  private function find($field, $value){
    $search = array($field,'=',$value);
    $res    = $this->_db->get('users_api_token', $search);
    if ($res->count()) {
      $this->_data = $res->first();
      return true;
    }
    return false;
  }

  private function data(){
    return $this->_data;
  }
}