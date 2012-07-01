<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../classes/QuarkModel2.php';
require '../../application/models/UserModel.php';

$UserModel = new UsersModel();
$UserModel->sync();
