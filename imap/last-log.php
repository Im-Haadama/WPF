<html>
<header>

</header>
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/06/17
 * Time: 12:59
 */

$file = shell_exec( "cd /home/agla/store/imap/log; ls -lrt | tail -1 | awk '{ print $9}'" );
print "<h1>" . $file . "</h1>";

print shell_exec( "cd /home/agla/store/imap/log; cat $file" );

?>
</html>

