<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model{

 const SESSION = "cart";


 public static function getFromSession(){

  $cart = new Cart();

  if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0){

        $cart->get((int) $_SESSION[Cart::SESSION]['idcart']);
      
      }else{

          $cart->getFromSessionID();

          if(!(int)$cart->getidcart() > 0){

            $data = [
              'dessessionid'=>session_id(),
            ];

            if(User::checkLogin(false)){

              $user = User::getFromSession();
            
              $data['iduser'] = $user->getiduser();
            }

            $cart->setData($data);

            $cart->save();

            $cart->setToSession();

          }

      }

   return $cart;

 }

public function setToSession(){

  $_SESSION[Cart::SESSION] = $this->getValues();

}


  public function getFromSessionID(){

    $sql = new Sql();

    $result = $sql->select("SELECT * FROM tb_carts WHERE dessessionid=:dessessionid", array(":dessessionid"=>session_id()));

    if(count($result)>0){

        $this->setData($result[0]);

    }

 }


 public function get(int $idcart){

    $sql = new Sql();

    $result = $sql->select("SELECT * FROM tb_carts WHERE idcart=:idcart", array(":idcart"=>$idcart));


    if(count($result)>0){

        $this->setData($result[0]);

    }

   
 }

public function save(){

  $sql= new Sql();

  $results = $sql->select("CALL sp_carts_save (:pidcart, :dessessionid, :piduser, :pdeszipcode, :pvlfreight, :pnrdays)", 
      array(":pidcart"=>$this->getidcart(),
            ':dessessionid'=>$this->getdessessionid(),
            ":piduser"=>$this->getiduser(),
            ":pdeszipcode"=>$this->getdeszipcode(),
            ":pvlfreight"=>$this->getvlfreight(),
            ":pnrdays"=>$this->getnrdays()
          ));




    $this->setData($results[0]);

}





}   // FIM DA CLASSE
