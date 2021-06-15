<?php
require_once 'BaseDao.class.php';

class UserDao extends BaseDao{

  public $table = 'users';

  public function __construct(){
    parent::__construct('users');
  }

  public function execute_user_query($query, $params){
    return $this->execute_query($query, $params);
  }

  public function get_user_by_email($email)
  {
    $query = 'SELECT u.id, u.email, u.first_name, u.last_name, ur.role_name, u.password
              FROM users u
              JOIN user_role ur ON u.role_id = ur.role_id
              WHERE email = :email';
    return @($this->execute_query($query,['email'=>$email]))[0];
  }

  public function update_user($user, $user_id){
    $entity[':id'] = $user_id;
    $query= 'UPDATE '.  $this->table . ' SET ';
    foreach ($user as $key => $value) {
      $query .= $key . '=:' . $key . ', ';
      $entity[':' . $key] = $value;
    }
    $query = rtrim($query,', ') . ' WHERE id=:id';
    return $this->update($entity, $query);
  }
}
 ?>
