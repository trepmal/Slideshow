<?php
/*
 * Plugin Name: Slideshows
 * Plugin URI: trepmal.com
 * Description: Replace Featured Image with Slideshow
 * Version: 0
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * TextDomain: slideshows
 * DomainPath:
 * Network:
 */

$kdl_slideshows = new KDL_Slideshows();

class KDL_Slideshows {

	function __construct() {
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );
		add_action( 'wp_ajax_set_slides', array( &$this, 'wp_ajax_set_slides') );
	}

	function add_meta_boxes() {
		add_meta_box( 'kdl-slideshow', __( 'Slideshow', 'slideshows' ), array( &$this, 'add_meta_box' ), 'page', 'normal', 'low');
	}

	function add_meta_box( $post ) {

		$slides = (array) get_post_meta( get_the_ID(), 'slideshowimage_ids', true );
		$html = '';
		foreach( $slides as $id ) {
			$html .= wp_get_attachment_image( $id, 'thumbnail' );
		}
		echo '<div class="slides-container">'. $html .'</div>';
		$set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set Slides', 'slideshows' ) . '" href="#" id="set-slides" class="button">%s</a></p>';
		$content = sprintf( $set_thumbnail_link, esc_html__( 'Set Slides', 'slideshows' ) );

		echo $content;

		wp_enqueue_script( 'slideshows', plugins_url('slideshows.js', __FILE__ ), array( 'jquery' ), 0, false );

		$img_ids = get_post_meta( get_the_ID(), 'slideshowimage_ids', true );
		$image_ids = empty( $img_ids ) ? '' : json_encode( $img_ids );
		wp_localize_script( 'slideshows', 'slideshows', array(
			'image_ids' => $image_ids,
		) );
	}

	// ajax callback
	function wp_ajax_set_slides() {
		$json = ! empty( $_REQUEST['json'] );

		$post_ID = intval( $_POST['post_id'] );
		if ( ! current_user_can( 'edit_post', $post_ID ) )
			wp_die( -1 );

		$slideshowimage_ids = array_map( 'intval', $_POST['slideshowimage_ids'] );

		if ( $json )
			check_ajax_referer( "update-post_$post_ID" );
		else
			check_ajax_referer( "set-slides-$post_ID" );

		if ( $slideshowimage_ids == array() ) {
			// if ( delete_post_thumbnail( $post_ID ) ) {
			// 	$return = _wp_post_thumbnail_html( null, $post_ID );
			// 	$json ? wp_send_json_success( $return ) : wp_die( $return );
			// } else {
			// 	wp_die( 0 );
			// }
			wp_send_json_success( 'remove' );
		}
		else {
			$return = '';
			update_post_meta( $post_ID, 'slideshowimage_ids', $slideshowimage_ids );
			foreach( $slideshowimage_ids as $thumbnail_id ) {
				// $return .= _wp_post_thumbnail_html( $thumbnail_id, $post_ID );
				$return .= wp_get_attachment_image( $thumbnail_id, 'thumbnail' );
			}
			$json ? wp_send_json_success( $return ) : wp_die( $return );
			// wp_send_json_success( print_r( $slideshowimage_ids, true ) );
		}
		// if ( set_post_thumbnail( $post_ID, $thumbnail_id ) ) {
		// 	$return = _wp_post_thumbnail_html( $thumbnail_id, $post_ID );
		// 	$json ? wp_send_json_success( $return ) : wp_die( $return );
		// }

		wp_die( 0 );
	}

}
function has_slideshow( $id=false ) {
	if ( '' == get_post_meta( $id, 'slideshowimage_ids', true ) )
		return false;
	return true;
}
function slideshow( $id=false, $size='thumbnail' ) {
	echo get_slideshow( $id, $size );
}
function get_slideshow( $id=false, $size='thumbnail' ) {
		if ( !$id ) $id = get_the_ID();
		$slides = (array) get_post_meta( $id, 'slideshowimage_ids', true );
		$html = '';
		foreach( $slides as $sid ) {
			$html .= wp_get_attachment_image( $sid, $size );
		}
		return $html;
		echo '<div class="slides-container">'. $html .'</div>';
}
