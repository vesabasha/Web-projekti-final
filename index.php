<?php
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($path) {
    case '':
    case 'landing':
        require 'pages/landing.html';
        break;
    case 'dashboard':
        require 'pages/dashboard.html';
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