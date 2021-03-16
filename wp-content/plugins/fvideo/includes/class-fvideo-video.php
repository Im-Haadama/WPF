<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class FVideo_Video {

	private $id;

	/**
	 * FVideo_Video constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	public function get_video()
	{
		return get_post_meta($this->id, 'וידאו', 1);
	}

	public function get_title()
	{
		return get_post_meta($this->id, 'שם התוכנית / שם הפרק', 1);
	}

	public function get_torrent()
	{
		$file_name = basename($this->get_video()) . '.torrent';
		return "https://site.weact.live" . AddParamToUrl(Flavor::getPost(), array("operation"=>"get_video", "file_name"=>$file_name));
	}

	public function get_seed()
	{
//		return $this->get_video();
		$file_name = basename($this->get_video());
		return "https://site.weact.live" . AddParamToUrl(Flavor::getPost(), array("operation"=>"get_video", "file_name"=>$file_name));
	}


	public function create_if_needed()
	{
//		$tracker = 'wss://tracker.openwebtorrent.com';

		$torrent_abs_path = FVideo_Torrent_Folder . basename($this->get_video()) . '.torrent';
//		print "checking for $torrent_abs_path<br/>";
		if (! file_exists($torrent_abs_path)) {
//			print "not found<br/>";
			$video          = $this->get_video();
			$filename_parts = explode( '/', $video );
			$file_name      = end( $filename_parts );

//			$orig_server = $filename_parts[0] . '/' . $filename_parts[1] . '/' . $filename_parts[2]. "<br/>";
//			return null;
			shell_exec( "cd " . FVideo_TEMP_Folder . "; wget $video" );
			if ( ! file_exists( FVideo_TEMP_Folder . $file_name ) ) {
				print "Error: can't create torrent. Download file failed<br/>";

				return null;
			}
//
			$tracker = 'wss://tracker.openwebtorrent.com';
			// wss://tracker.btorrent.xyz
//			$command = "cd " . FVideo_TEMP_Folder . "; node /usr/local/lib/node_modules/webtorrent-cli/bin/cmd.js create -a \"$tracker\" " .
//			           "\"$file_name\" > " . $torrent_abs_path;

			$command = "cd " . FVideo_TEMP_Folder . "; create-torrent --urlList '" . $this->get_seed() . "' $file_name -o $torrent_abs_path  --announce wss://tracker.openwebtorrent.com";
			MyLog($command);

			shell_exec( $command );

			if ( ! file_exists( $torrent_abs_path ) ) {
				print "Error: can't create torrent. webtorrent-cli failed.";

				return null;
			}
		}
		$result = get_site_url() . "/wp-content/uploads/torrents/" .  $this->id . '.torrent'; // ?tr='  .$tracker;
//		print $result;
		return $result;
//		return 'https://site.weact.live/wp-content/uploads/videos/Trump.torrent?tr=wss://tracker.webtorrent.io';
	}
}
