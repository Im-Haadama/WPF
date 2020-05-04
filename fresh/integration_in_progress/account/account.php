<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/04/16
 * Time: 07:58
 */

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

require_once( FRESH_INCLUDES . '/orders/orders-common.php' );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );

#############################
# legacy_users              #
# 1) Classical store client #
# 2) Chef clients           #
#############################

function get_daily_rate( $user_id ) {
//	return get_user_meta($user_id, 'day_rate');
	return sql_query_single_scalar( "SELECT day_rate FROM im_working WHERE user_id = " . $user_id );
}

$total = 0;

function balance_email( $date, $email ) {
	$client_id = get_customer_by_email( strtolower( $email ) );

	return balance( $date, $client_id );
}

function balance( $date, $client_id ) {
	$sql = 'select sum(transaction_amount) '
	       . ' from im_client_accounts where date <= "' . $date
	       . '" and client_id = ' . $client_id;

	return round( sql_query_single_scalar( $sql ), 2 );

}

// View_type:

// [displa-posts][su_posts posts_per_page="3"][su_posts posts_per_page="3" tax_term="21" order="desc"]



