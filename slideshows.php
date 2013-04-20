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

		add_action('admin_print_footer_scripts', array( &$this, 'js' ), 99 );
	}

	function js() {
		?><script>
jQuery(document).ready(function($){

	//	Prepare the variable that holds our custom media manager.
	var kdl_media_frame;
	var kdl_selection = [];

	<?php $slides = get_post_meta( get_the_ID(), 'slideshowimage_ids', true ); if ( $slides ) : ?>
	var query = wp.media.query({ post__in: <?php echo json_encode( $slides );?>, orderby: 'post__in' });
    promise = query.more();

	promise.done( function() {
 		kdl_selection = new wp.media.model.Selection( query.models, {
			multiple: true
		} );

	});
	<?php endif; ?>

	// Bind to our click event in order to open up the new media experience.
	$(document.body).on('click', '#set-slides', function(ev) {

		ev.preventDefault();

		// Get frame if ready
		if ( kdl_media_frame ) {
			kdl_media_frame.open();
			return;
		}

		args = {
			className: 'media-frame kdl-media-frame',
			frame: 'post',
			state: 'gallery',
			states: [

					new wp.media.controller.Library({
						id:         'insert',
						title:      '' // use blank title to hide link in modal
					}),
					new wp.media.controller.Library({
						id:         'gallery',
						// title:      l10n.createGalleryTitle,
						title:      'Choose Slides',
						priority:   40,
						toolbar:    'main-gallery',
						filterable: 'uploaded',
						multiple:   'add',
						editable:   false,

						library:  wp.media.query( _.defaults({
							type: 'image'
						}, this.library ) )
					}),

					// Embed states.
					new wp.media.controller.Embed({title:''}), // use blank title to hide link in modal

					// Gallery states.
					new wp.media.controller.GalleryEdit({
						library: kdl_selection.length == 0 ? this.selection : kdl_selection,
						editing: this.editing,
						menu:    'gallery',
						describe: false
					}),

					new wp.media.controller.GalleryAdd({
						title: 'Add Slides',
					}),
					new wp.media.controller.FeaturedImage({title:''}) // use blank title to hide link in modal
			],
			multiple: true,
			library: {
				type: 'image'
			}
		};

		if ( kdl_selection.length > 0 ) {
			args.state = 'gallery-edit';
		}

	 	kdl_media_frame = wp.media.frames.kdl_media_frame = wp.media( args );

		kdl_media_frame.on('insert update', function( selection ){

			// Grab our attachment selection and construct a JSON representation of the model.
			kdl_selection = selection;
			var media_attachment = kdl_selection.toJSON();

			// console.log( 'selection', kdl_selection );

			ids = _.pluck( media_attachment, 'id' );
			// console.log( 'ids', ids )

			var settings = wp.media.view.settings;
			//ajax request
			wp.media.post( 'set_slides', {
				json:         true,
				post_id:      settings.post.id,
				slideshowimage_ids:    ids,
				_wpnonce:     settings.post.nonce
			}).done( function( html ) {
				$( '.slides-container', '#kdl-slideshow' ).html( html );
			});

		});

		// Now that everything has been set, let's open up the frame.
		kdl_media_frame.open();
	});


});
</script><?php
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