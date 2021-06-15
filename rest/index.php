<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS, PATCH');

require '../vendor/autoload.php';
require_once('Config.php');
require_once('dao/BaseDao.class.php');
require_once('dao/MoviesDao.class.php');
require_once('dao/UserDao.class.php');

use \Firebase\JWT\JWT;

Flight::register('movies_dao', 'MoviesDao');
Flight::register('user_dao', 'UserDao');

//Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=moviedb_test','root','jusuf1503'));

Flight::before('start', function(&$params, &$output){
  $request = Flight::request();

  if(!(strpos($request->url, "movies")) &&
     !(strpos($request->url, "login")) &&
     !(strpos($request->url, "register")) &&
     !(strpos($request->url, "favourites")) &&
     !(strpos($request->url, "favourite-movies")) &&
     !(strpos($request->url, "favourites-remove")) &&
     !(strpos($request->url, "movie")) &&
     !(strpos($request->url, "movie-reviews"))
   ){

    $headers = getallheaders();
    $token = @$headers['X-Authorization'];

    if($token){
      $user = JWT::decode($token, Config::JWT_SECRET, array('HS256'));
      if($user){
        Flight::register('start_ts', time());
        Flight::register('user', $user);
      }
      else{
        Flight::halt(403, "Unauthorized User. Wrong token.");
      }
    }
    else{
      Flight::halt(403, "Unauthorized User");
    }
  }
});

//Get all users
Flight::route('GET /users', function(){
  $users = Flight::user_dao()->getAll();
  Flight::json($users);
});

//Get all movies
Flight::route('GET /movies', function(){
  $movies = Flight::movies_dao()->get_all();
  Flight::json($movies);
});

//Get  movie by id
Flight::route('GET /movie', function(){
  $id = Flight::request()->query['id'];
  $movie = Flight::movies_dao()->get_by_id($id);
  Flight::json($movie);
});

//Get user by id
Flight::route('GET /user', function(){
  $id = Flight::request()->query['id'];
  $user = Flight::user_dao()->get_by_id($id);
  Flight::json($user);
});

//Delete review
Flight::route('DELETE /movie-review', function(){
  $id = Flight::request()->query['id'];
  Flight::movies_dao()->delete_review($id);
});

//Delete from favourites
Flight::route('DELETE /favourites-remove/@userId/@movieId', function($userId, $movieId){
  //$user_id = Flight::request()->query['userId'];
  //$movie_id = Flight::request()->query['movieId'];
  //Flight::movies_dao()->delete_from_favourites($user_id, $movie_id);
  Flight::movies_dao()->delete_from_favourites($userId, $movieId);
});

//Delete movie
Flight::route('DELETE /movie', function(){
  $id = Flight::request()->query['id'];
  Flight::movies_dao()->delete_movie($id);
});

//Get review by movie id
Flight::route('GET /movie-reviews/@id', function($id){
  $query =  'SELECT CONCAT(u.first_name," ", u.last_name) as postedBy, r.id AS review_id, r.date_created, r.text AS comment FROM reviews r
     JOIN movies m ON r.movie_id = m.id
     JOIN users u ON r.user_id = u.id
     WHERE m.id = :id';
  $reviews = Flight::movies_dao()->execute_movie_query($query, [':id'=>$id]);
  Flight::json($reviews);
});

//Get favourite movies for user
Flight::route('GET /favourite-movies/@id', function($id){
  $query =  'SELECT * FROM movies m
     JOIN favourites f ON f.movieId = m.id AND f.userId =:id';
  $favourites = Flight::movies_dao()->execute_movie_query($query, [':id'=>$id]);
  Flight::json($favourites);
});

//Add review
Flight::route('POST /movie-review', function(){
  $request = Flight::request()-> data -> getData();
  $insert = 'INSERT INTO reviews (movie_id, text,user_id) VALUES (:movie_id, :reviewCommentText, :user_id)';
  $statement = Flight::movies_dao()-> execute($request,$insert);
  Flight::json('Review has been added');
});

// Add to favourites
Flight::route('POST /favourites', function(){
  $request = Flight::request()-> data -> getData();
  $user_id = Flight::request()->query['user_id'];
  $insert = 'INSERT INTO favourites (movieId,userId) VALUES (:movie_id, :user_id)';
  $statement = Flight::movies_dao()-> execute($request,$insert);
  Flight::json('Movie has been added to favourites.');
});

// Get favourites
Flight::route('GET /favourites', function(){
  $user_id = Flight::request()->query['user_id'];
  $query =  'SELECT * FROM favourites WHERE userId = :user_id';
  $favourites = Flight::movies_dao()->execute_movie_query($query, [':user_id'=>$user_id]);
  Flight::json($favourites);
});

//Registration
Flight::route('POST /register', function(){
  $user = Flight::request()->data->getData();
  $user['first_name'] = $user['firstName'];
  $user['last_name'] = $user['lastName'];
  $user['email'] = $user['signUpEmail'];
  $user['password'] = $user['signUpPassword'];
  unset($user['firstName'], $user['lastName'], $user['signUpEmail'], $user['signUpPassword'], $user['signUpConfirmPassword']);
  Flight::user_dao()->add($user);
});

//Add a movie
Flight::route('POST /movie', function(){
  $movie = Flight::request()->data->getData();
  $movie['title'] = $movie['movieTitle'];
  $movie['description'] = $movie['movieDescription'];
  $movie['image_url'] = $movie['movieImageUrl'];
  unset($movie['movieTitle'], $movie['movieDescription'], $movie['movieImageUrl']);
  Flight::movies_dao()->add($movie);
});

// Update user
Flight::route('POST /user/@id', function($id){
  $request = Flight::request()->data->getData();
  $request['first_name'] = $request['firstName'];
  $request['last_name'] = $request['lastName'];
  $request['email'] = $request['emailAddress'];
  unset($request['firstName'],$request['lastName'],$request['emailAddress']);
  Flight::user_dao()->update_user($request, $id);
  Flight::json('User has been updated');
});

// Login
Flight::route('POST /login', function(){
  $user = Flight::request()->data->getData();
  $db_user = Flight::user_dao()->get_user_by_email($user['email']);

  if($db_user){
    if($db_user['password']==$user['password']){
      $token_user = [
        'id' => $db_user['id'],
        'email' => $db_user['email']
      ];
      $jwt = JWT::encode($token_user, Config::JWT_SECRET);
      Flight::json(['id'=>$db_user['id'],'token'=>$jwt,'role_name'=>$db_user['role_name']]);
    } else {
      Flight::halt(404, 'Incorrect password.');
    }
  } else{
    Flight::halt(404, 'User not found.');
  }
});

// Get user by email
Flight::route('GET /user/@email', function($email){
  $statement = Flight::db()->prepare("SELECT * FROM users WHERE email = :email");
  $statement->execute([':email'=>$email]);
  $user = $statement->fetch();
  Flight::json($user);
});

Flight::route('POST /user/changePassword/@id', function($id){
  // $request = Flight::request()->data->getData();
  // $statement = "UPDATE users SET password = :password WHERE id = :id";
  // $request['password'] = $request['newPassword'];
  // unset($request['newPassword'], $request['newPasswordConfirmed']);
  // $statement->execute($request,[':id'=>$id]);
  // Flight::json('Password changed!');
  $request = Flight::request()->data->getData();
  $request['password'] = $request['newPassword'];
  unset($request['newPassword'], $request['newPasswordConfirm']);
  Flight::user_dao()->update_user($request, $id);
  Flight::json('Password has been updated');
});

// Flight::route('POST /users', function(){
//   $request = Flight::request()-> data -> getData();
//   //by name
//   unset($request['signUpConfirmPassword']);
//   //by name
//   $insert = 'INSERT INTO users (first_name, last_name, email_address, password) VALUES (:firstName, :lastName, :signUpEmail, :signUpPassword)';
//   $statement = Flight::db()-> prepare($insert);
//   $statement->execute($request);
//   Flight::json('Student has been added');
// });

Flight::after('start', function(&$params, &$output){
  $executionTime = time() - Flight::get('start_ts');
  $method = Flight::request()->url;
  $ip = $_SERVER['REMOTE_ADDR'];
  file_put_contents('log.txt', $method."\t".$executionTime."\t".$ip, FILE_APPEND);
});

Flight::start();
?>
