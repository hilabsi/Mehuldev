<?php


namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use \Kreait\Firebase\Firestore;

class FireStoreService
{
  /**
   * FireStoreService constructor.
   */
  private function __construct(){}

  /**
   * @return FirestoreClient
   */
  public static function client(): FirestoreClient
  {
    return new FirestoreClient();
  }
}
