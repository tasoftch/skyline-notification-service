<?php

function setupPDO(\TASoft\Util\PDO $PDO) {
    $PDO->setAttribute( \TASoft\Util\PDO::ATTR_ERRMODE, \TASoft\Util\PDO::ERRMODE_EXCEPTION );

    $PDO->exec("DELETE FROM SKY_NS_ENTRY_TAG");
    $PDO->exec("DELETE FROM SKY_NS_ENTRY");
    $PDO->exec("DELETE FROM SKY_NS_DOMAIN");
    $PDO->exec("DELETE FROM SKY_NS_ENTRY_PENDENT");
    $PDO->exec("DELETE FROM SKY_NS_USER");
    $PDO->exec("DELETE FROM SKY_NS_USER_DOMAIN");

    $PDO->exec("INSERT INTO SKY_NS_DOMAIN (id, name) VALUES (1, 'User Changed'), (2, 'Page Changed'), (3, 'Role Changed'), (4, 'Page Added'), (5, 'Logout')");
}

global $MySQL_PDO;
global $SQLite_PDO;

$SQLite_PDO = new \TASoft\Util\PDO("sqlite:Tests/tests.sqlite");
setupPDO($SQLite_PDO);

$MySQL_PDO = new \TASoft\Util\PDO("mysql:host=localhost;dbname=TASOFT_TEST;unix_socket=/tmp/mysql.sock", 'root', 'tasoftapps');
setupPDO($MySQL_PDO);
