<?php


class Core_Pages {
	static function CreateIfNeeded($title, $url, $shortcode)
	{
		if (get_page_by_title($title)) return true;

		$new_post = array('post_title' => $title,
		    'post_content' => '[' . $shortcode . ']',
            'post_status' => 'publish',
			'post_author' => get_user_id(),
			'post_category' => array(1),
			'post_type' => 'page',
			'post_name' => $url);

		return wp_insert_post($new_post);
	}
}