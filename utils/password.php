<?php
print "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '";
$symbols = array("qwertyuiopasdfghjklzxcvbnm",
	"!@#$%^&*()1234567890",
	"QWERTYUIOPLKJHGFDSAZXCVBNM");
$letters = "qwertyuiopasdfghjklzxcvbnm";

print substr($letters, rand(0, strlen($letters)), 1);
print substr($letters, rand(0, strlen($letters)), 1);
for ($i = 0; $i < 30; $i++){
	$k = rand(0, 2);
	print substr($symbols[$k], rand(0, strlen($symbols[$k])), 1);

}
print "';\n";