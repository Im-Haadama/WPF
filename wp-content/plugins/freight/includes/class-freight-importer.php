<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class Freight_Importer {
	function import_csv($file_name, $mission_id)
	{
		$the_shipping = null;
		if (! self::before_import($mission_id, $the_shipping)) return false;

		$file = fopen( $file_name, "r" );
		$header = fgetcsv( $file ); // Skip header.
		$valid = 0;
		$bad_address = 0;
		$data = array("header"=>array());
		$deliveries_info = [];

		while ($line = fgetcsv( $file ))
		{
			$order_id = $line[0];
			$client_name = $line[1];
			$address2 = $line[2];
			$address1 = $line[3];
			$city = $line[4];
			$comments = $line[5];
			$phone = $line[6];

			$delivery_info = array(
				'shipping_first_name' => $client_name,
				'shipping_last_name' => '',
				'shipping_address_1' => $address1,
				'shipping_address_2'=> $address2,
				'shipping_city'=>$city,
				'shipping_postcode'=>'',
				'billing_phone'=>$phone,
				'comments' => $comments,
				'order_id'=>$order_id
			);
			array_push($deliveries_info, $delivery_info);
		}
		self::end_of_import($deliveries_info, $mission_id, $the_shipping);
	}

	function import_baldar($file_name, $mission_id)
	{
		FreightLog(__FUNCTION__);
		$the_shipping = null;
		if (! self::before_import($mission_id, $the_shipping)) return false;

		// http://84.228.229.231/smartphone/TasksList.aspx#
		$html = GetContent($file_name);
		$html = str_replace("charset=windows-1255", "", $html);

		if (! class_exists("DOMDocument")) {
			print "DOMDocument not installed. <br/>" .
		"sudo apt-get install php7.4-xml";
			die(1);
		}
		// Create a new DOM Document
		$doc = new DOMDocument();

		// Load the html contents into the DOM
		$doc->loadHTML($html);
		$db_prefix = GetTablePrefix();

//		$all_cities = SqlQueryArrayScalar("select city_name from ${db_prefix}cities");
//		foreach ($all_cities as $key => $city)
//			$all_cities[$key] = str_replace("  ", " ", str_replace("-", " ", $city));

		$deliveries_info = [];
		//Loop through each <li> tag in the dom
		foreach ($doc->getElementsByTagName('li') as $li) {
			$phone = '';
			//Loop through each <h3> tag within the li, then extract the node value
			foreach ($li->getElementsByTagName('h3') as $links) {
				$order_id = $links->nodeValue;
//				print $order_id . "<br/>";
			}
			FreightLog($order_id);
			$client_name = '';

			//Loop through each <p> tag within the li, then extract the node value
			foreach ($li->getElementsByTagName('p') as $links) {
				$order_details = baldar_fix($links->nodeValue);
				$word = strtok($order_details, " ");
				$word_array = [];
				while ($word) {
					$word = trim($word, " -");
//					print "$word ";
					if (strlen($word) and $word != '-' and $word != 'טל' and $word != '.' and $word != "\t" and $word != 'מ' and $word != 'רגי')
						$word_array[] = $word;

					$word = strtok (" -");
				}

				$phone = array_pop($word_array);
				$order_details = implode (" ", $word_array);

				$address = Freight_Mission_Manager::geodecode_address($order_details);
				if ($address) {
					$city      = $address['city'];
					$street    = $address['address_1'];
					$address_2 = $address['address_2'];
				} else {
					$city = '';
					$street = '';
					$address_2 = '';
				}

				$delivery_info = array(
					'shipping_first_name' => $client_name,
					'shipping_last_name' => '',
					'shipping_address_1' => $street,
					'shipping_address_2'=> $address_2,
					'shipping_city'=> $city,
					'shipping_postcode'=>'',
					'billing_phone'=>$phone,
					'order_id'=>$order_id,
					'raw_info' => $order_details
				);
				array_push($deliveries_info, $delivery_info);
			}
		}
		self::end_of_import($deliveries_info, $mission_id, $the_shipping);
	}

	function end_of_import($deliveries_info, $mission_id, $the_shipping)
	{
		$result = Core_Html::GuiHeader(1, "imported data");
		array_unshift($deliveries_info, array("first name", "last name", "address 1", "address 2", "city", "zip", "phone", "order_id","raw data", "baldar id"));
//		unset($deliveries_info[0]);
		self::create_orders($mission_id,$the_shipping,$deliveries_info);
		$args = array("links" => array("order_id" => "/wp-admin/post.php?post=%d&action=edit"));
		$result .= Core_Gem::GemArray($deliveries_info, $args, "imported_orders");
		InfoUpdate("import_result.". time(), $result);
		print $result;
	}

	function create_orders($mission_id, $the_shipping, &$deliveries_info)
	{
		$valid = 0;
		$bad_address = 0;
		$duplicate = 0;

		foreach ($deliveries_info as $key => $delivery_info)
		{
			if ($key == 0) continue;
			$external_id = $delivery_info['order_id'];
			if ($external_id and SqlQuerySingleScalar("select count(*) 
					from wp_postmeta pm
					join wp_posts p
					where meta_key='external_order_id'
					  and meta_value=$external_id
					  and pm.post_id = p.id 
					  and p.post_status != 'trash'")) {

				$deliveries_info[$key]['order_id'] = SqlQuerySingleScalar("select post_id from wp_postmeta where meta_key='external_order_id' and meta_value=$external_id");
				$deliveries_info[$key]['status'] = 'D' . $external_id;
				$duplicate++;
				continue;
			}
			$O = Finance_Order::CreateOrder(1, $mission_id,  null, $the_shipping, '', 10, $delivery_info);
			if (! $O) {
				print "Can't create order<br/>";
				continue;
			}
			$deliveries_info[$key]['external_order_id'] = $deliveries_info[$key]['order_id'];
			$deliveries_info[$key]['order_id'] = $O->GetID();
			if (isset($delivery_info['order_id']))
				$O->setField('external_order_id', $delivery_info['order_id']);
			if (isset($deliveries_info['raw_info']))
				$O->SetComments($deliveries_info['raw_info']);
			$street = $delivery_info['shipping_address_1'];
			$city = $delivery_info['shipping_city'];
//			print "<br/>Created order  " . $O->GetID() . " client $client_name $order_id";
//			$long_lat = Freight_Mission_Manager::get_lat_long($street . " " . $city);
			if (self::check_import($delivery_info['raw_info'], $city, $street)){ // and (floor($long_lat[0])== 32) and (floor($long_lat[1]) == 34)) {
				$O->update_status( "wc-processing" );
				$deliveries_info[$key]['status'] ='V';
				$valid ++;
			} else {
				$deliveries_info[$key]['status'] = 'X';
				$bad_address ++;
			}
		}
		print "Summary:<br/>";
		print $valid . " new orders in processing<br/>";
		if ($bad_address) print $bad_address . " orders to fix address (remain waiting for payment<Br/>";
		if ($duplicate) print $duplicate . " duplicate<br/>";

		// So next time all points would be read.
		Freight_Mission_Manager::clean($mission_id);
	}

	function before_import($mission_id, &$the_shipping)
	{
		$m = new Mission($mission_id);
		if (! $m->getStartAddress()) {
			print "No start address for mission " . $m->getMissionName();
			return false;
		}
		$customer = new Finance_Client(1);
		$zone = $customer->getZone();

		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}

		return true;
	}

	function check_import($raw_info, $city, $street)
	{
		return (strlen($street) > 1)
		       and (strlen($city) > 1) and
		           strstr($raw_info, strtok($city, " ")) and
		           strstr($raw_info, strtok($street, " " ));

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