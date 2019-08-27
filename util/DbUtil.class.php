<?php


class DbUtil
{
   public $link;       
   public $host;        
   public $dbname;        
   public $user;        
   public $pwd;        
   public $char;  
         
   public function __construct($host='47.106.23.233',$dbname='test',$user='root',$pwd='625625'){            
        $this->host=$host;            
        $this->dbname=$dbname;            
        $this->user=$user;            
        $this->pwd=$pwd;            
        $this->char='set names utf8';            
        $this->link=new pdo("mysql:host={$this->host};dbname={$this->dbname}",$this->user,$this->pwd);            
        $this->link->query($this->char);        
    }  
          
    public function query($sql){            
        return $this->link->query($sql);        
    }

    public function getAll($sql){
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


}


?>