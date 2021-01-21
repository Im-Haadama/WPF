<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class Freight_Actions {

	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function init_hooks(Core_Hook_Handler $loader)
	{
//		print debug_trace(10); print "---------------------<br/>";
		$loader->AddAction("order_save_pri", $this, 'order_save_pri');
		$loader->AddAction("mission_update_type", $this, 'mission_update_type');
//		$loader->AddAction("mission_details", $this, 'mission_details');
		$loader->AddAction("freight_do_add_delivery", $this);
		$loader->AddAction('delivered', $this, "delivered_wrap");
		$loader->AddAction('download_mission',$this, 'download_mission');
		$loader->AddAction('print_mission', $this, 'print_mission');
		$loader->AddAction('order_update_driver_comment', $this, 'order_update_driver_comment');
		$loader->AddAction('order_update_field', $this);
		$loader->AddAction("freight_do_import", $this);
		$loader->AddAction("freight_do_import_baldar", $this);

		$loader->AddAction("mission_clean", $this);
	}

	static function order_save_pri()
	{
		$order_id = GetParam("order_id", true);
		$site_id = GetParam("site_id", true);
		$pri = GetParam("pri", true);

		//			print info_get("mission_order_priority_" . $site_id . '_' .$order_id);
		// TEMP: Remove duplicates.
		Core_Options::info_remove("mission_order_priority_" . $site_id . '_' .$order_id);

		if ($pri > 0)
			return InfoUpdate("mission_order_priority_" . $site_id . '_' .$order_id, $pri);
		return false;

	}

	static function mission_update_type()
	{
		$mission_id = GetParam("mission", true);
		$type = GetParam("type", true);

		$m = new Mission($mission_id);
		return $m->setType($type);
	}

	function freight_do_add_delivery() : bool
	{
		$client = GetParam("client", true);
		$fee = GetParam("fee", true);
		$mission_id = GetParam("mission_id", true);

		$customer = new Fresh_Client($client);
		$zone = $customer->getZone();
		if (! $zone) {
			print "Failed: zone not found";
			return false;
		}
		$the_shipping = null;
		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}
		if (! $the_shipping) {
			print "Failed: no shipping method to zone " . $zone->get_zone_name();
			return false;
		}

		$o = Finance_Order::CreateOrder( $client, $mission_id, null, $the_shipping,
			" משלוח המכולת " . date( 'Y-m-d' ) . " " . $customer->getName(), Israel_Shop::addVat($fee));

		if (! $o)
			return false;
		$o->setStatus( 'wc-processing' );
//		$o->setMissionID($mission_id);

		return true;
	}

	static function delivered_wrap()
	{
		$site_id = GetParam("site_id", false, Core_Db_MultiSite::LocalSiteId());
		$type = GetParam("type", false, "orders");
		$ids = GetParamArray("id", true);

		foreach ($ids as $id)
			if (! Freight_Mission_Manager::delivered($site_id, $type, $id)) return false;
		return true;
	}

	function download_mission()
	{
		$id = GetParam("id", true, "");
		$file = $this->getCSV($id);
		$date = date('Y-m-d');
		$file_name = "mission_${id}_${date}.csv";

		header("Content-Disposition: attachment; filename=\"" . $file_name . "\"");
		header("Content-Type: application/octet-stream");
		header("Content-Length: " . strlen($file));
		header("Connection: close");
		print $file;
		die (0);
	}

	function print_mission()
	{
		$id = GetParam("id", true);
		print Core_Html::HeaderText();
		// The route stops.
		$args = array("print" => true, "edit" => false);
		$m = Freight_Mission_Manager::get_mission_manager($id);
		print $m->dispatcher($args);

		// Supplies to collect
		$supplies = Fresh_Supplies::mission_supplies($id);
		foreach ($supplies as $supply_id) {
			$s = new Fresh_Supply($supply_id);
			print $s->Html($args) ;
		}
		die(0);
	}

	static function order_update_driver_comment()
	{
		$order_id = GetParam("order_id", true);
		$comments = GetParam("comments", true);
//		print "$order_id \'$comments\'";
		$o = new Finance_Order($order_id);
		return $o->UpdateDriverComments($comments);
	}

	static function order_update_field()
	{
		$order_id = GetParam("order_id", true);
		$field = GetParam("field", true);
		$field_value = GetParam("field_value", true);
		$o = new Finance_Order($order_id);
		$o->UpdateField($field, urldecode($field_value));
		Freight_Mission_Manager::clean($o->getField('mission_id'));
	}


	function freight_do_import_wrap($mission_id)
	{
		if (! isset($_FILES["fileToUpload"]["tmp_name"])) {
			print "No file selected";

			return;
		}

		$file_name = $_FILES["fileToUpload"]["tmp_name"];

		$file = fopen( $file_name, "r" );
		$header = fgetcsv( $file ); // Skip header.
		$customer = new Finance_Client(1);
		$zone = $customer->getZone();
		$the_shipping = null;
		$m = new Mission($mission_id);
		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}
		$valid = 0;
		$bad_address = 0;
		while ($line = fgetcsv( $file ))
		{
			$order_id = $line[0];
			$client_name = $line[1];
			$address1 = $line[2];
			$address2 = $line[3];
			$city = $line[4];
			$comments = $order_id . " " . $line[5];
			$phone = $line[6];

			$delivery_info = array(
				'shipping_first_name' => $client_name,
				'shipping_last_name' => '',
				'shipping_address_1' => $address1,
				'shipping_address_2'=> $address2,
				'shipping_city'=>$city,
				'shipping_postcode'=>'',
				'billing_phone'=>$phone
			);

			$O = Finance_Order::CreateOrder(1, $mission_id,  null, $the_shipping, $comments, 10, $delivery_info);
//			print "Created order  " . $O->GetID() . " client $client_name $comments";
			if (! $m->getStartAddress()) {
				print "No start address for mission " . $m->getMissionName();
				return false;
			}
			if (Freight_Mission_Manager::get_distance($m->getStartAddress(), $address1 . " " . $city)) {
				$O->update_status( "wc-processing" );
//				print "processing<br/>";
				$valid ++;
			} else {
				print "bad address<br/>";
				$bad_address ++;
			}
		}
		print "Summary:<br/>";
		print $valid . " new orders in processing<br/>";
		if ($bad_address) print $bad_address . " orders to fix address (remain waiting for payment<Br/>";
		Freight_Mission_Manager::clean($mission_id);

	}

	function mission_clean()
	{
		$id = GetParam("id", true);

		Freight_Mission_Manager::clean($id);
	}

	function freight_do_import_baldar($mission_id, $file_name = null)
	{
		// http://84.228.229.231/smartphone/TasksList.aspx#
		$m = new Mission($mission_id);
		$valid = 0;
		$bad_address = 0;

		if (! $file_name and !isset($_FILES["fileToUpload"]["tmp_name"])) {
			print "No file selected";

			return;
		}

		if (! $file_name) $file_name = $_FILES["fileToUpload"]["tmp_name"];

		$customer = new Finance_Client(1);
		$zone = $customer->getZone();
		$the_shipping = null;
		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}
		$html = file_get_contents($file_name);
		$html = str_replace("charset=windows-1255", "", $html);

		// Create a new DOM Document
		$doc = new DOMDocument();

		// Load the html contents into the DOM
		$doc->loadHTML($html);
		$db_prefix = GetTablePrefix();

		$all_cities = SqlQueryArrayScalar("select city_name from ${db_prefix}cities");

		//Loop through each <li> tag in the dom
		foreach ($doc->getElementsByTagName('li') as $li) {
			$phone = '';
			//Loop through each <h3> tag within the li, then extract the node value
			foreach ($li->getElementsByTagName('h3') as $links) {
				$order_id = $links->nodeValue;
//				print $order_id . "<br/>";
			}
			$client_name = '';
			$street = '';
			$city = '';
			$address_2 = '';

			//Loop through each <p> tag within the li, then extract the node value
			foreach ($li->getElementsByTagName('p') as $links) {
				$order_details = baldar_fix($links->nodeValue);
//				print "X${order_details}X<br/>";
				$word = strtok($order_details, " ");
				$word_array = [];
				while ($word) {
//					print "$word ";
					if ($word != '-' and $word != 'טל' and $word != '.')
						array_unshift($word_array, $word);

					$word = strtok (" ");
				}

				$phone = $word_array[0]; unset($word_array[0]);	$word_array=array_values($word_array);
				$word_array=array_values($word_array);

				for ($i = 0; $i < count($word_array) - 1; $i++) {
					if ( in_array( $word_array[ $i + 1 ] . " " . $word_array[ $i ], $all_cities ) ) {
						// 2 word city
						$city = $word_array[ $i+1 ] . " " . $word_array[ $i ];
//						print "city2 at $i<br/>";
						unset ( $word_array[ $i ] );
						unset ( $word_array[ $i + 1 ] );
						break;
					}
					if ( in_array( $word_array[ $i ], $all_cities ) ) {
//						print "city1 at $i<br/>";
						// 2 word city
						$city = $word_array[ $i ];
						unset ( $word_array[ $i ] );
						break;
					}
				}
//				print "finding city. i=$i<br/>";
				// The street address is between the city and senders phone.
				for ($j = $i + 1; $j < count($word_array); $j++) {
					if (! isset($word_array[$j])) continue; // Two word city name.
//					print "checking $j " . $word_array[$j];
					if ((strlen ($word_array[$j]) > 5) and ($word_array[$j][0] == '0' or $word_array[$j][0] == '5' or $word_array[$j][0] == '+')) { // Sender phone.
//						print "<br/>phone: $j " . $word_array[$j] . "<br/>";
						for ($k = $i+1; $k < $j; $k++) {
							if (isset($word_array[$k])) $street .= $word_array[ $k ] . " ";
							unset ($word_array[$k]);
						}
						$street = trim($street, " ");
						for ($k = 0; $k < $j; $k++) {
							if (isset($word_array[$k])) $address_2 .= $word_array[ $k ] . " ";
							unset ($word_array[$k]);
						}
						$address_2 = trim($address_2, " ");
						break;
					}
				}
//				var_dump($word_array);
//				print "st=$street<br/>";
				if ($street == '') { // No sender phone
					for ($k = 0; $k < count($word_array); $k++) {
						if (isset($word_array[$k])) $street .= $word_array[ $k ] . " ";
						unset ($word_array[$k]);
					}
				}

				$delivery_info = array(
					'shipping_first_name' => $client_name,
					'shipping_last_name' => '',
					'shipping_address_1' => $street,
					'shipping_address_2'=> $address_2,
					'shipping_city'=> $city,
					'shipping_postcode'=>'',
					'billing_phone'=>$phone
				);
//				var_dump($delivery_info);
				$O = Finance_Order::CreateOrder(1, $mission_id,  null, $the_shipping, '', 10, $delivery_info);
				$O->setField('baldar_id', $order_id);
				print "Created order  " . $O->GetID() . " client $client_name $order_id";
				if (-1 != Freight_Mission_Manager::get_distance($m->getStartAddress(), $street . " " . $city)) {
					$O->update_status( "wc-processing" );
					print "processing<br/>";
					$valid ++;
				} else {
					print "bad address<br/>";
					$bad_address ++;
				}
			}
		}
		print "Summary:<br/>";
		print $valid . " new orders in processing<br/>";
		if ($bad_address) print $bad_address . " orders to fix address (remain waiting for payment<Br/>";
		Freight_Mission_Manager::clean($mission_id);

	}
}


function baldar_fix($src)
{
	$hex = bin2hex($src);
	$i =0;
	$dst = "";
	while ($i < strlen($hex)){
		if (($hex[$i] == 'c') and ($hex[$i+1] == '3')) $i += 4;
		else if (($hex[$i] == 'c') and ($hex[$i + 1] == '2')) { $dst .= ('d7' . $hex[$i+2] . $hex[$i+3]); $i +=4;}
		else { $dst .= ($hex[$i] . $hex[$i+1]); $i +=2; }
	}

	return hex2bin($dst);
}