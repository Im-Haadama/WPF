<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/03/18
 * Time: 05:33
 */

require_once( "../im_tools.php" );
require_once( "../gui/sql_table.php" );
print header_text( false, true, false );

$sql = "SELECT post_title, post_content FROM wp_posts WHERE length(post_content) > 10
                                      AND post_type = 'product' ";

print table_content( $sql );