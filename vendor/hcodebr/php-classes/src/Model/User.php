<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model{

 const SESSION = "User";
 const SECRET = "HcodePhp7_Secret"

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
      throw new Exception('Usuário inexistente ou senha incorreta 2');
    }


  }


public static function verifyLogin($inadmin = true){

  if(!isset($_SESSION[User::SESSION])
   || !$_SESSION[User::SESSION]
   || !(int)$_SESSION[User::SESSION]["iduser"] > 0
   || (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
 ){

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
      ":despassword"=>$this->getdespassword(),
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

  $sql = new Sql();

  $result = $sql->select("SELECT * FROM db_ecommerce.tb_persons a inner join tb_users b using(idperson) where a.desemail=:email", 
    array(":email"=>$email));

  if(count($result === 0)){
     throw new \Exception('Não foi possível recuperar a senha');
  
  }else{

     $data = $result[0];

     $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create (:iduser, :desip)", 
        array(":iduser"=>$data["iduser"],
              ":desip"=>$_SERVER["REMOTE_ADDR"]
    ));

     if(count($results2) === 0){

          throw new \Exception('Não foi possível recuperar a senha', 4);
     
     }else{

        $datarecovery = $results2[0];

        $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET,$datarecovery['idrecovery'], 
          MCRYPT_MODE_ECB));

        $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";


     }




  }
  
}














}   // FIM DA CLASSE
