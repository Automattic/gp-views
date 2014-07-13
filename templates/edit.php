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
			<dt><label for="view[name]">Name:</label></dt>
			<dd><input type="text" name="view[name]" value="<?php echo esc_attr($view->name);?>"/></dd>
			<dt><label for="view[terms]">Search terms</label></dt>
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
		</dl>
		<dl>
			<dt><input type="submit" value="<?php echo esc_attr( __( 'Save' ) ); ?>"><span class="or-cancel"><?php _e('or'); ?> <a href="javascript:history.back();"><?php _e('Cancel'); ?></a></span></dt>
		</dl>

	</form>

<?php gp_tmpl_footer();