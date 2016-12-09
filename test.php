<?php

$length = 8;
echo bin2hex(openssl_random_pseudo_bytes($length / 2));