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
		$the_shipping = null;
		if (! self::before_import($mission_id, $the_shipping)) return false;

		// http://84.228.229.231/smartphone/TasksList.aspx#
		$html = file_get_contents($file_name);
		$html = str_replace("charset=windows-1255", "", $html);

		// Create a new DOM Document
		$doc = new DOMDocument();

		// Load the html contents into the DOM
		$doc->loadHTML($html);
		$db_prefix = GetTablePrefix();

		$all_cities = SqlQueryArrayScalar("select city_name from ${db_prefix}cities");
		foreach ($all_cities as $key => $city)
			$all_cities[$key] = str_replace("  ", " ", str_replace("-", " ", $city));

		$deliveries_info = [];
		//Loop through each <li> tag in the dom
		foreach ($doc->getElementsByTagName('li') as $li) {
			$phone = '';
			//Loop through each <h3> tag within the li, then extract the node value
			foreach ($li->getElementsByTagName('h3') as $links) {
				$order_id = $links->nodeValue;
//				print $order_id . "<br/>";
			}
			$client_name = '';

			//Loop through each <p> tag within the li, then extract the node value
			foreach ($li->getElementsByTagName('p') as $links) {
				$order_details = baldar_fix($links->nodeValue);
				$word = strtok($order_details, " ");
				$word_array = [];
				while ($word) {
//					print "$word ";
					if ($word != '-' and $word != 'טל' and $word != '.' and $word != "\t")
						array_unshift($word_array, $word);

					$word = strtok (" ");
				}

				$phone = $word_array[0]; unset($word_array[0]);	$word_array=array_values($word_array);
				$word_array=array_values($word_array);

				$address = Freight_Mission_Manager::geodecode_address($order_details);
				$city = $address['city'];
				$street = $address['address_1'];
				$address_2 = $address['address_2'];

				$delivery_info = array(
					'shipping_first_name' => $client_name,
					'shipping_last_name' => '',
					'shipping_address_1' => $street,
					'shipping_address_2'=> $address_2,
					'shipping_city'=> $city,
					'shipping_postcode'=>'',
					'billing_phone'=>$phone,
					'order_id'=>$order_id
				);
				array_push($deliveries_info, $delivery_info);
			}
		}
		self::end_of_import($deliveries_info, $mission_id, $the_shipping);
	}

	function end_of_import($deliveries_info, $mission_id, $the_shipping)
	{
		print Core_Html::GuiHeader(1, "imported data");
		array_unshift($deliveries_info, array("first name", "last name", "address 1", "address 2", "city", "zip", "phone", "order_id"));
		print Core_Html::gui_table_args($deliveries_info);
		unset($deliveries_info[0]);
		self::create_orders($mission_id,$the_shipping,$deliveries_info);
	}

	function create_orders($mission_id, $the_shipping, $deliveries_info)
	{
		$valid = 0;
		$bad_address = 0;

		foreach ($deliveries_info as $delivery_info)
		{
			$O = Finance_Order::CreateOrder(1, $mission_id,  null, $the_shipping, '', 10, $delivery_info);
			if (isset($delivery_info['order_id']))
				$O->setField('external_order_id', $delivery_info['order_id']);
			$street = $delivery_info['shipping_address_1'];
			$city = $delivery_info['shipping_city'];
//			print "<br/>Created order  " . $O->GetID() . " client $client_name $order_id";
			$long_lat = Freight_Mission_Manager::get_lat_long($street . " " . $city);
			if ((strlen($street) > 1) and (strlen($city) > 1)){ // and (floor($long_lat[0])== 32) and (floor($long_lat[1]) == 34)) {
				$O->update_status( "wc-processing" );
				$valid ++;
			} else {
				$bad_address ++;
			}
		}
		print "Summary:<br/>";
		print $valid . " new orders in processing<br/>";
		if ($bad_address) print $bad_address . " orders to fix address (remain waiting for payment<Br/>";
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