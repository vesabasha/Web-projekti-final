<?php

$basePath = '/Web final project';
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

$path = trim(str_replace($basePath, '', $requestPath), '/');

$filePath = __DIR__ . $requestPath;
if ($requestPath !== '/' && file_exists($filePath) && is_file($filePath)) {
    return false;
}

switch ($path) {
    case '':
    case 'landing':
        require 'pages/landing.php';
        break;
    case 'dashboard':
        require 'pages/dashboard.php';
        break;
    case 'profile':
        require 'pages/profile.html';
        break;
    case 'list':
        require 'pages/lists.html';
        break;
    case 'details':
        require 'pages/details.html';
        break;
    case 'privacy':
        require 'pages/privacy.html';
        break;
    case 'games': 
        require 'pages/games.php';
        break;
    case 'manage_games':
        require 'pages/manage_games.php';
        break;
    default:
        http_response_code(404);
}

?>