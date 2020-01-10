<?php

global $PDO;

$PDO = new \TASoft\Util\PDO("sqlite:Tests/tests.sqlite");
$PDO->setAttribute( \TASoft\Util\PDO::ATTR_ERRMODE, \TASoft\Util\PDO::ERRMODE_EXCEPTION );

$PDO->exec("DELETE FROM SKY_NS_AFFECTED_ELEMENT");
$PDO->exec("DELETE FROM SKY_NS_ENTRY");
$PDO->exec("DELETE FROM SKY_NS_KIND");
$PDO->exec("DELETE FROM SKY_NS_PENDENT");
$PDO->exec("DELETE FROM SKY_NS_REGISTER");
$PDO->exec("DELETE FROM SKY_NS_REGISTER_KIND");

$PDO->exec("INSERT INTO SKY_NS_KIND (id, name) VALUES (1, 'User Changed'), (2, 'Page Changed'), (3, 'Role Changed'), (4, 'Page Added'), (5, 'Logout')");
