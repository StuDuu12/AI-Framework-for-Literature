<?php
session_destroy();
header('Location: ' . URL_ROOT . '/login');