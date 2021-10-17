<?php

require_once('Database.php');
require_once('../model/Response.php');
require_once('../functions/functions.php');

// note: never cache user http requests/responses
// (our response model defaults to no cache unless specifically set)

// attempt to set up connections to db connections
try {

  $writeDB = DB::connectWriteDB();

}
catch(PDOException $ex) {
  // log connection error for troubleshooting and return a json error response
  error_log("Connection Error: ".$ex, 0);
  $response = new Response();
  $response->setHttpStatusCode(500);
  $response->setSuccess(false);
  $response->addMessage("Database connection error");
  $response->send();
  exit;
}

// handle creating new user
// check to make sure the request is POST only - else exit with error response
if($_SERVER['REQUEST_METHOD'] !== 'POST'):
  $response = new Response();
  $response->setHttpStatusCode(405);
  $response->setSuccess(false);
  $response->addMessage("Request method not allowed");
  $response->send();
  exit;
endif;

// check request's content type header is JSON
if($_SERVER['CONTENT_TYPE'] !== 'application/json'):
  // set up response for unsuccessful request
  $response = new Response();
  $response->setHttpStatusCode(400);
  $response->setSuccess(false);
  $response->addMessage("Content Type header not set to JSON");
  $response->send();
  exit;
endif;

// get POST request body as the POSTed data will be JSON format
$rawPostData = file_get_contents('php://input');

if(!$jsonData = json_decode($rawPostData)):
  // set up response for unsuccessful request
  $response = new Response();
  $response->setHttpStatusCode(400);
  $response->setSuccess(false);
  $response->addMessage("Request body is not valid JSON");
  $response->send();
  exit;
endif;

// check if post request contains full name, username and password in body as they are mandatory
if(!isset($jsonData->fullname) || !isset($jsonData->email) || !isset($jsonData->password) || !isset($jsonData->nin) || !isset($jsonData->contact) || !isset($jsonData->dob)):
  $response = new Response();
  $response->setHttpStatusCode(400);
  $response->setSuccess(false);
  // add message to message array where necessary
  (!isset($jsonData->fullname) ? $response->addMessage("Full name not supplied") : false);
  (!isset($jsonData->email) ? $response->addMessage("Email not supplied") : false);
  (!isset($jsonData->password) ? $response->addMessage("Password not supplied") : false);
  (!isset($jsonData->nin) ? $response->addMessage("NIN not supplied") : false);
  (!isset($jsonData->contact) ? $response->addMessage("Contact not supplied") : false);
  (!isset($jsonData->dob) ? $response->addMessage("Date Of Birth not supplied") : false);
  $response->send();
  exit;
endif;
if(!filter_var($jsonData->email, FILTER_VALIDATE_EMAIL) || !preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/',$jsonData->password)):
  $response = new Response();
  $response->setHttpStatusCode(400);
  $response->setSuccess(false);
  (!filter_var($jsonData->email, FILTER_VALIDATE_EMAIL) ? $response->addMessage("Invalid Email") : false);
  (!preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/',$jsonData->password) ? $response->addMessage("Password Must contain special character") : false);
  $response->send();
  exit;
endif;
// check to make sure that full name username and password are not empty and less than 255 long
if(strlen($jsonData->nin) < 1 || strlen($jsonData->nin) > 16 || strlen($jsonData->fullname) < 1 || strlen($jsonData->fullname) > 255 || strlen($jsonData->email) < 1 || strlen($jsonData->email) > 255 || strlen($jsonData->password) < 1 || strlen($jsonData->password) > 100):
  $response = new Response();
  $response->setHttpStatusCode(400);
  $response->setSuccess(false);
  (strlen($jsonData->fullname) < 1 ? $response->addMessage("Full name cannot be blank") : false);
  (strlen($jsonData->fullname) > 255 ? $response->addMessage("Full name cannot be greater than 255 characters") : false);
  (strlen($jsonData->email) < 1 ? $response->addMessage("Email cannot be blank") : false);
  (strlen($jsonData->email) > 255 ? $response->addMessage("Email cannot be greater than 255 characters") : false);
  (strlen($jsonData->password) < 1 ? $response->addMessage("Password cannot be blank") : false);
  (strlen($jsonData->password) > 100 ? $response->addMessage("Password cannot be greater than 100 characters") : false);
  (strlen($jsonData->nin) < 1 ? $response->addMessage("NIN cannot be blank") : false);
  (strlen($jsonData->nin) > 16 ? $response->addMessage("NIN cannot be greater than 16 characters") : false);

  $response->send();
  exit;
endif;


// trim any leading and trailing blank spaces from full name and username only - password may contain a leading or trailing space
$fullname = trim($jsonData->fullname);
$email = trim($jsonData->email);
$nin = trim($jsonData->nin);
$contact = trim($jsonData->contact);
$dob = trim($jsonData->dob);
$password = $jsonData->password;

// attempt to query the database to check if username already exists
try {
  // create db query
  $query = $writeDB->prepare('SELECT employee_id from employees where employee_email = :email');
  $query->bindParam(':email', $email, PDO::PARAM_STR);
  $query->execute();

  // get row count
  $rowCount = $query->rowCount();

  if($rowCount !== 0):
    // set up response for username already exists
    $response = new Response();
    $response->setHttpStatusCode(409);
    $response->setSuccess(false);
    $response->addMessage("Manager Account already exists");
    $response->send();
    exit;
  endif;

  // hash the password to store in the DB as plain text password stored in DB is bad practice
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  // create the  function
  $code = code();
  // create db query to create user
  $query = $writeDB->prepare('INSERT into employees (employee_name, employee_nin, employee_code, employee_contact, employee_d_o_b, employee_password, employee_email)
  values (:fullname, :nin, :code, :contact, :dob, :password, :email)');
  $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
  $query->bindParam(':email', $email, PDO::PARAM_STR);
  $query->bindParam(':nin', $nin, PDO::PARAM_INT);
  $query->bindParam(':contact', $contact, PDO::PARAM_INT);
  $query->bindParam(':dob', $dob, PDO::PARAM_STR);
  $query->bindParam(':code', $code, PDO::PARAM_STR);
  $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
  $query->execute();

  // get row count
  $rowCount = $query->rowCount();

  if($rowCount === 0):
    // set up response for error
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("There was an error creating the manager account - please try again");
    $response->send();
    exit;
  endif;
  // get last user id so we can return the user id in the json
  $lastUserID = $writeDB->lastInsertId();
  // verification starts
  $verify_code = verification_code();
  $query = $writeDB->prepare('INSERT into verification (verification_code, verification_employee_id)
  values (:code, :id)');
  $query->bindParam(':code', $verify_code, PDO::PARAM_INT);
  $query->bindParam(':id', $lastUserID, PDO::PARAM_INT);
  $query->execute();
  // get row count
  $rowCount = $query->rowCount();
  if($rowCount === 0):
    // set up response for error
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("There was an error with verification");
    $response->send();
    exit;
  endif;
  send_verification_email($verify_code, $lastUserID, $email);

  // build response data array which contains basic user details
  $returnData = array();
  $returnData['id'] = $lastUserID;
  $returnData['fullname'] = $fullname;
  $returnData['email'] = $email;
  $returnData['nin'] = $nin;
  $returnData['dob'] = $dob;
  $returnData['contact'] = $contact;

  $response = new Response();
  $response->setHttpStatusCode(201);
  $response->setSuccess(true);
  $response->addMessage("Manager Account created, please check email for verification");
  $response->setData($returnData);
  $response->send();
  exit;
}
catch(PDOException $ex) {
  $response = new Response();
  $response->setHttpStatusCode(500);
  $response->setSuccess(false);
  $response->addMessage("There was an issue creating a manager account - please try again" .$ex);
  $response->send();
  exit;
}
