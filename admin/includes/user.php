<?php 

class User extends Db_object{
  public static $db_table = "users";
  public static $db_table_fields = array('username', 'password', 'first_name', 'last_name', 'filename');
  public $username;
  public $password;
  public $first_name;
  public $last_name;
  public function upload_photo(){
    if(!empty($this->errors)){
      return false;
    }
    if(empty($this->filename) || empty($this->tmp_path)){
      $this->errors[] = "the file was not available";
      return false;
    }
    $target_path = SITE_ROOT . DS . 'admin' . DS . 
      $this->upload_directory . DS . $this-> filename;
    if(file_exists($target_path)){
      $this->errors[] = "The file {$this->filename} already exists";
      return false;
    }
    if(move_uploaded_file($this->tmp_path, $target_path)){
      if($this->create()){
        unset($this->tmp_path);
        return true;
      } else{
        $this->errors[] = "The file directory probably does not have permission";
        return false;
      }
    }
  }
  public function image_path_and_placeholder(){
    return empty($this->filename) ? $this->image_placeholder : $this->upload_directory . DS . $this->filename;
  }
  public static function verify_user($username, $password){
    global $database;
    $username = $database->escape_string($username);
    $password = $database->escape_string($password);
    $sql = "SELECT * FROM " . self::$db_table . " WHERE ";
    $sql .= "username='{$username}' ";
    $sql .= "AND password='{$password}' ";
    $sql .= "LIMIT 1";
    $the_result_array = self::find_by_query($sql);
    return !empty($the_result_array) ? array_shift($the_result_array) : false;
  }
} // End of User class

?>