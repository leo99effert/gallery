<?php 

class Db_object {
  public static $db_table = "";
  public static $db_table_fields = array();
  public $id;
  public $filename;
  public $upload_directory = "images";
  public $image_placeholder = "http://placehold.it/400x400&text=image";
  public $tmp_path;
  public $type;
  public $size;
  public $errors = array();
  public $upload_errors_array = array(
    UPLOAD_ERR_OK=>
      "Value: 0; There is no error, the file uploaded with success.",
    UPLOAD_ERR_INI_SIZE=>
      "Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.",
    UPLOAD_ERR_FORM_SIZE=>
      "Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
    UPLOAD_ERR_PARTIAL=>
      "Value: 3; The uploaded file was only partially uploaded.",
    UPLOAD_ERR_NO_FILE=>
      "Value: 4; No file was uploaded.",
    UPLOAD_ERR_NO_TMP_DIR=>
      "Value: 6; Missing a temporary folder.",
    UPLOAD_ERR_CANT_WRITE=>
      "Value: 7; Failed to write file to disk.",
    UPLOAD_ERR_EXTENSION=>
      "Value: 8; A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help."
  );
  public function set_file($file) {
    if(empty($file) || !$file || !is_array($file)){
      $this->errors[] = "There was no file uploaded here";
      return false;
    } elseif($file['error'] != 0){
      $this->errors[] = $this->upload_errors_array[$file['error']];
      return false;
    } else{
      $this->filename = basename($file['name']);
      $this->tmp_path = $file['tmp_name'];
      $this->type = $file['type'];
      $this->size = $file['size'];
    }
  }
  public static function find_all(){
    return static::find_by_query("SELECT * FROM " . static::$db_table . " ");
  }
  public static function find_by_id($id){
    $the_result_array = static::find_by_query("SELECT * FROM " . static::$db_table . " WHERE id=$id LIMIT 1");
    return !empty($the_result_array) ? array_shift($the_result_array) : false;
  }
  public static function find_by_query($sql){
    global $database;
    $result_set = $database->query($sql);
    $the_object_array = array();
    while($row = mysqli_fetch_array($result_set)){
      $the_object_array[] = static::instatination($row);
    }
    return $the_object_array;
  }
  public static function instatination($the_record){
    $calling_class = get_called_class();
    $the_object = new $calling_class;
    foreach ($the_record as $the_attribute => $value) {
      if($the_object->has_the_attribute($the_attribute)){
        $the_object->$the_attribute = $value;
      }
    }
    return $the_object;
  }
  private function has_the_attribute($the_attribute){
    $object_properties = get_object_vars($this);
    return array_key_exists($the_attribute, $object_properties);
  }
  protected function properties() {
    $properties = array();
    foreach (static::$db_table_fields as $db_field) {
      if(property_exists($this, $db_field)){
        $properties[$db_field] = $this->$db_field;
      }
    }
    return $properties;
  }
  protected function clean_properties() {
    global $database;
    $clean_properties = array();
    foreach ($this->properties() as $key => $value) {
      $clean_properties[$key] = $database->escape_string($value);
    }
    return $clean_properties;
  }
  public function save(){
    return isset($this->id) ? $this->update() : $this->create();
  }
  public function create(){
    global $database;
    $properties = $this->clean_properties();
    $sql = "INSERT INTO " . static::$db_table . "(" . implode(",", array_keys($properties)) . ")";
    $sql .= "VALUES ('" .  implode("','", array_values($properties))  . "')";
    if($database->query(($sql))){
      $this->id = $database->the_insert_id();
      return true;
    } else{
      return false;
    }
  }
  public function update(){
    global $database;
    $properties = $this->clean_properties();
    $property_pairs = array();
    foreach ($properties as $key => $value) {
      $property_pairs[] = "{$key}='{$value}'";
    }
    $sql = "UPDATE " . static::$db_table . " SET ";
    $sql .= implode(", ", $property_pairs);
    $sql .= " WHERE id= " . $database->escape_string($this->id);
    $database->query($sql);
    return (mysqli_affected_rows($database->connection) == 1) ? true : false;
  }
  public function delete(){
    global $database;
    $sql = "DELETE FROM " . static::$db_table . " ";
    $sql .= "WHERE id= " . $database->escape_string($this->id);
    $sql .= " LIMIT 1";
    $database->query($sql);
    return (mysqli_affected_rows($database->connection) == 1) ? true : false;
  }
  public static function count_all(){
    global $database;
    $sql = "SELECT COUNT(*) FROM " . static::$db_table;
    $result_set = $database->query($sql);
    $row = mysqli_fetch_array($result_set);
    return array_shift($row);
  }
}

?>