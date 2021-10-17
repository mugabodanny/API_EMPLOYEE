<?php

      class EmployeeException extends Exception { }

      class Employee{

        private $_id;
        private $_name;
        private $_nin;
        private $_status;
        private $_email;
        private $_dob;
        private $_code;

        public function __construct($id,$name,$nin,$status,$email,$dob,$code){
          $this->setID($id);
          $this->setTitle($name);
          $this->setDescription($nin);
          $this->setDeadline($status);
          $this->setCompleted($dob);
          $this->setCompleted($code);
        }


      }
