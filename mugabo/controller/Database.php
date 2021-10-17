<?php

    class DB {
      // initialize read and write DB server
      private static $writeDBconnection;
      private static $readDBconnection;


      // create a write DB server
      public static function connectWriteDB(){
        if(self::$writeDBconnection === null):
          self::$writeDBconnection = new PDO('mysql:host=localhost;dbname=employees;charset=utf8', 'root', '');
          self::$writeDBconnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          self::$writeDBconnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        endif;

        return self::$writeDBconnection;
      }
      // create a read DB server
      public static function connectReadDB(){
        if(self::$readDBconnection === null):
          self::$readDBconnection = new PDO('mysql:host=localhost;dbname=employees;charset=utf8', 'root', '');
          self::$readDBconnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          self::$readDBconnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        endif;

        return self::$readDBconnection;
      }
    }
