<html>
<body>
<table>
	<?php
	/**
	 * Created by PhpStorm.
	 * User: agla
	 * Date: 16/06/16
	 * Time: 20:11
	 */

	$filename = '../uploads/sadot.txt';
	$handle   = fopen( $filename, "r" );
	if ( $handle ) {
		while ( ( $line = fgets( $handle ) ) !== false ) {
			// process the line read.
			if ( strstr( $line, "₪" ) ) {
				print "<tr>";
				print "<td>" . strstr( $line, "₪", true ) . "</td>";
				preg_match_all( "/[א-ת]+$/", $line, $out );
				print "<td>out00: " . $out[0][0] . "</td>";

				print "<td>line: " . $line . "</td>";
				print "</tr>";
			}
		}

		fclose( $handle );
	} else {
		// error opening the file.
	}

	?>
</table>
</body>
</html>

