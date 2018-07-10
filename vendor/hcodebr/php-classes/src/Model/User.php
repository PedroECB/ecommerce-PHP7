<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model{

 const SESSION = "User";
 const SECRET = "HcodePhp7_Secret";


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
    $results = $sql->select("SELECT * FROM tb_users WHERE deslogin=:LOGIN", array(
      ":LOGIN"=>$login
    ));

    if(count($results) === 0){

      throw new \Exception('Usuário inexistente ou senha inválida 1');

    }

    $data = $results[0];

    if(password_verify($senha, $data['despassword'])){

       $user = new User();

       $user->setData($data);

       $_SESSION[User::SESSION] = $user->getValues();

       return $user;



    }else{
      throw new \Exception('Usuário inexistente ou senha incorreta 2');
    }


  }


public static function verifyLogin($inadmin = true){

  if(User::checkLogin($inadmin)){

      header("Location: /admin/login");
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



public static function getForgot($email){

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

        $link = "http://www.ecommercephp7.com/admin/forgot/reset?code=$code";

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




}   // FIM DA CLASSE
