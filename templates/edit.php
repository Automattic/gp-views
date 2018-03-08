<?php
gp_title( sprintf( __('Edit View &lt; %s &lt; GlotPress'), esc_html( $project->name ) ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( '/views' . gp_url_project( $project ), 'Views' ),
	__('Edit')
) );

gp_tmpl_header();
?>
	<h2><?php printf( _x( 'Edit view %1$s for %2$s', 'view, project'), $view->name, $project->name ); ?> <a onclick="return confirm('Are you sure?')" href="-delete" class="action delete">(delete)</a></h2>
	<form action="" method="post" enctype="multipart/form-data">
		<dl>
			<dt><label for="view[name]"><?php _e( 'Name:' ); ?></label></dt>
			<dd><input type="text" name="view[name]" value="<?php echo esc_attr($view->name);?>"/></dd>
			<dt><?php _e( 'Screenshot' ); ?></dt>
			<dd>
				<input type="hidden" name="view[screenshot]" value="<?php if ( $view->screenshot ) echo esc_attr( $view->screenshot ); ?>" />
				<div id="gp-views-screenshot-holder"><?php
					if ( $view->screenshot ) {
						echo wp_get_attachment_image( $view->screenshot, 'medium', false, array(
							'id' => 'gp-views-preview-screenshot',
							'class' => 'choose-screenshot',
						) );
					}
				?></div>
				<img src="/wp-admin/images/no.png" id="gp-views-remove-screenshot" <?php if ( ! $view->screenshot ) echo ' style="display: none"'; ?> />
				<button id="gp-views-select-screenshot" class="choose-screenshot" <?php if ( $view->screenshot ) echo ' style="display: none"'; ?>><?php _e( 'Select Screenshot' ); ?></button>
			</dd>
			<dt><label for="view[terms]"><?php _e( 'Search terms' ); ?></label></dt>
			<dd><textarea rows="<?php echo max( 10, count( $view->terms)+1 ); ?>" name="view[terms]"><?php echo esc_textarea( implode("\n", (array) $view->terms ) );?></textarea></dd>
			<dt><label><?php _e('Originals priority:'); ?></label></dt>
			<dd>
			<?php
				$valid_priorities = GP::$original->get_static( 'priorities' );
				krsort( $valid_priorities);
				foreach ( $valid_priorities as $priority => $label ) {
			?>
					<label><input <?php gp_checked( in_array( (string) $priority, (array) $view->priorities, true ) );?> type="checkbox" name="view[priorities][]" value="<?php echo esc_attr( $priority );?>"><?php echo esc_html( $label );?></label><br />
			<?php } ?>
			</dd>
			<dt><?php _e( 'Is view public' ); ?></dt>
			<dd>
			<?php echo gp_radio_buttons( 'view[public]', array(
				'true' => 'Yes, visible to all users',
				'false' => 'No, only visible to super admins',
			), $view->public ); ?>
			</dd>
			<dt><?php _e( 'Sort index' ); ?></dt>
			<dd>
				<dd><input type="number" name="view[rank]" value="<?php echo esc_attr($view->rank ?: 100);?>" style="width: 5em"/></dd>
			</dd>
		</dl>
		<dl>
			<dt><input type="submit" value="<?php echo esc_attr( __( 'Save' ) ); ?>"><span class="or-cancel"><?php _e('or'); ?> <a href="javascript:history.back();"><?php _e('Cancel'); ?></a></span></dt>
		</dl>

	</form>
	<script type="text/javascript">
		jQuery( document ).on( 'click', '#gp-views-remove-screenshot', function( e ) {
			e.preventDefault();
			jQuery( 'input[name="view[screenshot]"]' ).val( '' );
			jQuery( '#gp-views-screenshot-holder' ).hide().html( '' );
			jQuery( '#gp-views-select-screenshot' ).show();
			jQuery( '#gp-views-remove-screenshot' ).hide();
		} );

		jQuery( document ).on( 'click', '.choose-screenshot', function( e ) {
			e.preventDefault();
			var screenshot_picker;

			if ( ! screenshot_picker ) {
				screenshot_picker = wp.media({
					title: 'Select a screenshot',
					multiple : false,
					library : {
						type : 'image',
					}
				});

				screenshot_picker.on( 'close', function() {
					var selection = screenshot_picker.state().get( 'selection' );
					var attachment;
					selection.each( function( i ) {
						attachment = i;
					} );

					jQuery( 'input[name="view[screenshot]"]' ).val( attachment.id );
					jQuery( '#gp-views-screenshot-holder' ).show().html( '<img src="' + attachment.attributes.sizes.medium.url + '" id="gp-views-preview-screenshot" class="choose-screenshot" />' );
					jQuery( '#gp-views-select-screenshot' ).hide();
					jQuery( '#gp-views-remove-screenshot' ).show();
				});

				screenshot_picker.on( 'open', function() {
					var selection = screenshot_picker.state().get( 'selection' );
					attachment = wp.media.attachment( jQuery( 'input[name="view[screenshot]"]' ).val() );
					attachment.fetch();
					selection.add( attachment );
				});
				screenshot_picker.open();
			}
		});
	</script>
	<style type="text/css">
		#gp-views-preview-screenshot, #gp-views-remove-screenshot {
			cursor: pointer;
		}
	</style>
<?php gp_tmpl_footer();
