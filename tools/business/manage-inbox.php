<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/01/19
 * Time: 07:46
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/niver/data/attachment_folder.php' );
require_once( ROOT_DIR . '/tools/im_tools.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/suppliers/Supplier.php' );
require_once( ROOT_DIR . '/tools/business/business.php' );

$filename = ROOT_DIR . '/tools/mail-config.php';
if ( ! file_exists( $filename ) ) {
	print "config file " . $filename . " not found<br/>";
	die ( 1 );
}
require_once( $filename );


$suppler_id = get_param( "supplier_id" );
$operation  = get_param( "operation" );

if ( isset( $operation ) ) {
	switch ( $operation ) {
		case "add_document":
			$m = new Imap();

			if ( ! $m->Connect( $hostname, $mail_user, $password ) ) {
				die( "can't connect to mail" );
			}
			$m->Read();

			$supplier_id = get_param( "supplier_id" );
			$date        = get_param( "date" );
			$amount      = get_param( "amount" );
			$net_amount  = get_param( "net_amount" );
			$ref         = get_param( "ref" );
			$index       = get_param( "index" );

			// print "reading message $index<br/>";
			$mm = $m->ReadMessage( $index );

			$attach_file = $attach_folder . '/' . $mm->GetAttachmentName();

			// Move file to archive.

			// Add the transation
			business_add_transaction( $supplier_id, $date, $amount, 0, $ref, 0, $net_amount, 4,
				$attach_file );

			// Move inbox to archive
			$m->MoveMessage( $index, "invoice_folder" );

			break;

	}
	die ( 0 );
}

?>
    <script type="text/javascript" src="/niver/gui/client_tools.js"></script>

    <script>
        function insert_invoice(index, supplier_id) {
            var ref = get_value_by_name("ref_" + index);
            if (ref.length < 2) {
                alert("יש לרשום מספר חשבונית");
                return;
            }
            var tot = get_value_by_name("tot_" + index);
            if (tot.length < 2) {
                alert("יש לרשום סכום חשבונית, כולל מעמ");
                return;
            }
            var net = get_value_by_name("net_" + index);
            if (net.length < 2) {
                alert("יש לרשום סכום חשבונית נטו, ללא מעמ");
                return;
            }
            var date = get_value_by_name("dat_" + index);

            var request = "manage-inbox.php?operation=add_document" +
                "&index=" + index +
                "&supplier_id=" + supplier_id +
                "&date=" + date +
                "&amount=" + tot +
                "&net_amount=" + net +
                "&ref=" + ref;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    if (xmlhttp.response.length) {
                        alert(xmlhttp.response);
                        return;
                    }
                    location.reload();
                }
            }

            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
    </script>
<?php

$table = inbox_files( $hostname, $mail_user, $password, $attach_folder, $folder_url,
	array( "חשבונית", "חוזה" ) );

if ( ! $table ) {
	return;
}
//var_dump($table);

$by_supplier_table = array();

$header = array( "נושא", "מסמך", "תאריך שליחה", "תאריך מסמך", "מספר חשבונית", "סכום כולל", "סכום נטו", "פעולה" );

// -1 - not related to supplier

// print gui_table($table);
foreach ( $table as $row ) {
	// group by supplier

	$subject        = $row[0];
	$sender         = $row[1];
	$doc_name       = $row[2];
	$date           = $row[3];
	$message_number = $row[4];
	//print "mn=" . $row[4]. "<br/>";
	$supplier = Supplier::getByInvoiceSender( $sender );

	if ( $supplier ) {
		$supplier_id = $supplier->getId();
	} else {
		$supplier_id = - 1;
	} // Not found

	if ( ! isset( $by_supplier_table[ $supplier_id ] ) ) {
		$by_supplier_table[ $supplier_id ] = array();
		array_push( $by_supplier_table[ $supplier_id ], $header );
	}

	$link = null;
	$line = array( substr( $subject, 0, 20 ), $doc_name, $date );
	if ( $supplier_id == - 1 ) {
		print $row[1];
		array_push( $line, $row[1] );
	} else {
		$end_of_month = strtotime( 'last day of ' . date( 'F-Y', strtotime( $date ) ) ) . "<br/>";
		array_push( $line, gui_input_date( "dat_" . $message_number, "", date( 'Y-m-d', $end_of_month ) ) );
		// date('Y-m-d', strtotime('last day of ' . date('F-y', $date)))));

		array_push( $line, gui_input( "ref_" . $message_number, "" ) );
		array_push( $line, gui_input( "tot_" . $message_number, "" ) );
		array_push( $line, gui_input( "net_" . $message_number, "" ) );
		array_push( $line, gui_button( "btn_" . $message_number, "insert_invoice(" . $message_number . "," . $supplier_id . ")", "הזן" ) );
	}
	array_push( $by_supplier_table[ $supplier_id ], $line );
}

foreach ( $by_supplier_table as $supplier_id => $table ) {
	// print "sid= " . $supplier_id . "<br/>";
	if ( $supplier_id > - 1 ) {
		print gui_header( 1, get_supplier_name( $supplier_id ) );
	} else {
		print gui_header( 1, "לא משויכים" );
	}

	print gui_table( $by_supplier_table[ $supplier_id ] );
}

