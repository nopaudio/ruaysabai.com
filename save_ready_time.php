<?php
session_start();
$_SESSION['ready_time'] = time();
http_response_code(200);
