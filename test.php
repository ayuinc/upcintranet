<?php

function encrypt($string = '', $key = '') {
    $key = utf8_encode($key);

    //make it 32 chars long. pad with \0 for shorter keys
    $key = str_pad($key, 32, "\0");

    //make the input string length multiples of 16. This is necessary
    $padding = 16 - (strlen($string) % 16);
    $string .= str_repeat(chr($padding), $padding);

    //emtpy IV - initialization vector
    $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv));
    return rtrim($encrypted);
}

function decrypt($string = '', $key = '') {
    $key = $key . "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    $string = base64_decode($string);

    return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
}

echo encrypt('123456');