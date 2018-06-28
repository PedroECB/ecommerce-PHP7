<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;



$app->get('/admin/users', function(){
    
    User::verifyLogin();                            #Lista usuários
    $users = User::listAll();


    $page = new PageAdmin();
    $page->setTpl("users", array(
      "users"=>$users
    ));


});
                                                        // CADASTRO DE USUÁRIOS

$app->get('/admin/users/create', function(){
    
    User::verifyLogin();                                #GET Cadastro usuários
    
    $page = new PageAdmin();
    $page->setTpl("users-create");


});




$app->post('/admin/users/create', function(){
    
    User::verifyLogin();                            #POST Cadastro de usuários
    

   $user = new User();
   $_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;
   $user->setData($_POST);
   $user->save();
 
   header("Location:/admin/users");
   exit;

});

                                                          //  EDIÇÃO DE USUÁRIO




$app->get('/admin/users/:iduser/delete', function($iduser){
    
    User::verifyLogin();                                  #Deletar
    
    $user = new User();
    $user->get((int)$iduser);

    $user->delete();
    header("Location: /admin/users");
    exit;


});





$app->get('/admin/users/:iduser', function($iduser){
    
    User::verifyLogin();   
    $user = new User();
    $user->get((int)$iduser);                                   #Visualiza para edição

    $page = new PageAdmin();
    $page->setTpl("users-update", array("user"=>$user->getValues()));


});





$app->post('/admin/users/:iduser', function($iduser){
    
    User::verifyLogin();

    $user = new User();

    $_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;
    
    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

 header("Location:/admin/users");
 exit;

});
