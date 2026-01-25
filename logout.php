<?php

//logouts (HARDEST EVER?)
session_start();
session_destroy();

header("Location: landing.html");
exit();



?>