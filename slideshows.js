jQuery(document).ready(function($){

	// console.log( typeof media );
	// console.log( slideshows.image_ids );

	//	Prepare the variable that holds our custom media manager.
	var kdl_media_frame;
	var kdl_selection = [];


	if ( '' != slideshows.image_ids ) {
		imgs = slideshows.image_ids.replace(/['"\]\[]/g, '');
		imgs = imgs.split(',');
		var query = wp.media.query({ post__in: imgs, orderby: 'post__in' });
	    promise = query.more();

		promise.done( function() {
	 		kdl_selection = new wp.media.model.Selection( query.models, {
				multiple: true
			} );

		});
	}

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