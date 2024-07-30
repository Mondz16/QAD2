<?php
function storeToken($token) {
    $tokenPath = 'F:/xampp/htdocs/QAD2/secure/token.json';
    if (!file_exists(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0700, true);
    }
    file_put_contents($tokenPath, json_encode($token));
}

function getToken() {
    $tokenPath = 'F:/xampp/htdocs/QAD2/secure/token.json';
    if (file_exists($tokenPath)) {
        return json_decode(file_get_contents($tokenPath), true);
    }
    return null;
}
?>
