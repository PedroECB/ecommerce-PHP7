<?php 

session_start();

require_once("vendor/autoload.php");
use \Slim\Slim;
use Hcode\Page;
use Hcode\PageAdmin;
use Hcode\Model\User;

$app = new \Slim\Slim();

$app->config('debug', true);

$app->get('/', function() {
    
	$page = new Page();
  
  $page->setTpl("index");

});



$app->get('/admin', function() {

 User::verifyLogin();

  $page = new PageAdmin();
  
  $page->setTpl("index");

});


$app->get('/admin/login', function() {
    
  $page = new PageAdmin([
    "header"=>false,
    "footer"=>false
  ]);
  
  $page->setTpl("login");

});


$app->post('/admin/login', function() {
    
  User::login($_POST['login'], $_POST['senha']);

  header("Location: /admin");
  exit;

});

$app->get('/admin/logout', function(){
 
  User::logout();
  header("Location: /admin/login");
  exit;

});




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
    
    $page = new PageAdmin();
    $page->setTpl("users-update");


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
    
    $page = new PageAdmin();                        #Atualizar
    $page->setTpl("users-update");


});






$app->run();

 ?>
