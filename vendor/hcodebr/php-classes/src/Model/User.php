<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model{

 const SESSION = "User";
 const SECRET = "HcodePhp7_Secret";
 const ERROR = "UserError";
 const ERROR_REGISTER = "UserErrorRegister";
 const SUCCESS = "UserSuccess";


 public static function getFromSession(){

    $user = new User();

    if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0 ){
 
        $user->setData($_SESSION[User::SESSION]);

    }
 
  return $user;

 }


 public static function checkLogin($inadmin = true){
    
  if(!isset($_SESSION[User::SESSION])
    || !$_SESSION[User::SESSION]
    || !(int)$_SESSION[User::SESSION]["iduser"] > 0){

    //Não está logado
    return false;

  }else{

    if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){

        return true;

    }elseif($inadmin === false){

      return true;

    }else{

      return false;

    }


  }
    


 }


  public static function login($login, $senha){
    $sql = new Sql();
    $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson=b.idperson  WHERE deslogin=:LOGIN", array(
      ":LOGIN"=>$login
    ));

    if(count($results) === 0){

      throw new \Exception('Usuário inexistente ou senha inválida 1');

    }

    $data = $results[0];

    if(password_verify($senha, $data['despassword'])){

       $user = new User();

       $data['desperson'] = utf8_encode($data['desperson']);

       $user->setData($data);

       $_SESSION[User::SESSION] = $user->getValues();

       return $user;



    }else{
      throw new \Exception('Usuário inexistente ou senha incorreta 2');
    }


  }


public static function verifyLogin($inadmin = true){

  if(!User::checkLogin($inadmin)){

      if($inadmin){
        header("Location: /admin/login");
      
      }else{
         header("Location: /login");
      }
      exit;
  }
}


public static function logout(){
  $_SESSION[User::SESSION] = NULL;
}

public static function listAll(){

  $sql = new Sql();
  return $sql->select("SELECT * FROM tb_users  a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
}



public function save(){

    $sql = new Sql();
    $result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
      ":desperson"=>$this->getdesperson(),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
      ":desemail"=>$this->getdesemail(),
      ":nrphone"=>$this->getnrphone(),
      ":inadmin"=>$this->getinadmin()
    ));

 $this->setData($result[0]);


}


public function get($iduser){
  $sql = new Sql();
  $result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser"
    , array(":iduser"=>$iduser));

  $data = $result[0];

  $data['desperson'] = utf8_encode($data['desperson']);

  $this->setData($result[0]);

}





public function update(){

    $sql = new Sql();
    $result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
      ":iduser"=>$this->getiduser(),
      ":desperson"=>$this->getdesperson(),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>$this->getdespassword(),
      ":desemail"=>$this->getdesemail(),
      ":nrphone"=>$this->getnrphone(),
      ":inadmin"=>$this->getinadmin()
    ));

 $this->setData($result[0]);
}


public function delete(){

  $sql = new Sql();
  $sql->query("CALL sp_users_delete(:iduser)", array(":iduser"=>$this->getiduser()));
}



public static function getForgot($email, $inadmin = true){

//$email2 = "pedrosophbc@gmail.com";

  $sql = new Sql();

  $result = $sql->select("SELECT * FROM db_ecommerce.tb_persons a inner join tb_users b using(idperson) where a.desemail=:email", 
    array(":email"=>$email));


  if(count($result)===0){

     throw new \Exception('Não foi possível recuperar a senha', 1);
  
  }else{

     $data = $result[0];

     $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create (:iduser, :desip)", 
        array(":iduser"=>$data["iduser"],
              ":desip"=>$_SERVER["REMOTE_ADDR"]
    ));

     if(count($results2) === 0){

          throw new \Exception('Não foi possível recuperar a senha 2', 4);
     
     }else{

        $datarecovery = $results2[0];

        $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET,$datarecovery['idrecovery'], 
          MCRYPT_MODE_ECB));

        if($inadmin == true){

          $link = "http://www.ecommercephp7.com/admin/forgot/reset?code=$code";
        }else{


          $link = "http://www.ecommercephp7.com/forgot/reset?code=$code";
        }


        

        $mailer = new Mailer($data['desemail'],$data['desperson'], "Redefinir Senha da Hcode Store", "forgot", 
          array("name"=>$data['desperson'],
                "link"=>$link)
      );

          $mailer->send();

          return $data;

     }




  }
  
}



public static function validForgotDecrypt($code){

    base64_decode($code);


   $idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

   $sql = new Sql();
   $result = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a INNER JOIN tb_users b  using(iduser) 
    inner join tb_persons c using(idperson) where a.idrecovery=:idrecovery and dtrecovery is null and date_add(a.dtregister, interval 1 hour)>= now()", 
    array(":idrecovery"=>$idrecovery));

if(count($result) === 0){
  echo $idrecovery;
  throw new \Exception('Não foi possível recuperar a senha idrecovery', 5);

}else{

  return $result[0];
}


}

public static function setForgotUser($idrecovery){

  $sql = new Sql();
  $sql->query("UPDATE tb_userspasswordsrecoveries set dtrecovery= NOW() where idrecovery=:idrecovery", 
    array(":idrecovery"=>$idrecovery));

}



public function setPassword($password){

  $password2 = password_hash($password, PASSWORD_DEFAULT);

  $sql = new Sql();
  $sql->query("UPDATE tb_users set despassword =:password where iduser=:iduser", array(
    ":password"=>$password2,
    ":iduser"=>$this->getiduser()
  ));
}

public static function setError($msg){

$_SESSION[User::ERROR] = $msg;

}


public static function getError(){

  //$msg = isset($_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "Desgraça";

  if(isset($_SESSION[User::ERROR])){

    $msg = $_SESSION[User::ERROR];

  }else{  $msg = '';  }

  User::clearError();

  return $msg;

}





public static function clearError(){

  $_SESSION[User::ERROR] = NULL;



}
                                            ////////////////////////


public static function setSuccess($msg){

$_SESSION[User::SUCCESS] = $msg;


}


public static function getSuccess(){

  $msg = (isset($_SESSION[User::SUCCESS])) ? $_SESSION[User::SUCCESS] : "";

  User::clearError();

  return $msg;

}





public static function clearSuccess(){

  $_SESSION[User::SUCCESS] = NULL;



}



public static function getErrorRegister(){

  $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

  User::clearError();

  return $msg;

}


public static function setErrorRegister($msg){

  $_SESSION[User::ERROR_REGISTER] = $msg;


}


public static function checkLoginExist($login){

  $sql = new Sql();

  $results = $sql->select("SELECT * FROM tb_users WHERE deslogin=:deslogin", array(":deslogin"=>$login));

  return (count($results)>0);



}

public function getOrders(){

      $sql = new Sql();

    $results = $sql->select("SELECT * 
                             FROM tb_orders a 
                             INNER JOIN tb_ordersstatus b USING(idstatus) 
                             INNER JOIN tb_carts c USING(idcart)
                             INNER JOIN tb_users d ON d.iduser = a.iduser
                             INNER JOIN tb_addresses e USING (idaddress)
                             INNER JOIN tb_persons f ON  f.idperson = d.idperson
                             WHERE a.iduser =:iduser", array(":iduser"=>$this->getiduser()
                           ));

        return $results;

}





}   // FIM DA CLASSE
