<?php

require_once 'BaseDao.class.php';

class MoviesDao extends BaseDao{

public $table = 'movies';

public function __construct(){
   parent::__construct($this->table);
 }

 public function execute_movie_query($query, $params){
   return $this->execute_query($query, $params);
 }

 public function delete_review($review_id){
    $entity[':id'] = $review_id;
    $query = 'DELETE FROM reviews WHERE id = :id';
    return $this->execute($entity, $query);
  }

  public function delete_from_favourites($user_id, $movie_id){
     $entity[':user_id'] = $user_id;
     $entity[':movie_id'] = $movie_id;
     $query = 'DELETE FROM favourites WHERE movieId = :movie_id AND userId = :user_id';
     return $this->execute($entity, $query);
   }

  public function delete_movie($id){
     $entity[':id'] = $id;
     $query = 'DELETE FROM reviews WHERE movie_id = :id; DELETE FROM movies WHERE id = :id;';
     return $this->execute($entity, $query);
   }
}
 ?>
