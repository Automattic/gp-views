<?php
gp_title( sprintf( __('Views &lt; %s &lt; GlotPress'), esc_html( $project->name ) ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	__('Views')
) );
gp_tmpl_header();
?>
	<h2><?php printf( __('Preset views for %s'), $project->name ); ?> <a href="/views<?php echo gp_url_project( $project);?>/-add" class="action add">(add new)</a></h2>
	<?php foreach( $views as $view_id => $view ): ?>
	<div id="view-<?php echo esc_attr($view_id);?>">
		<h3><?php echo esc_html($view->name);?> <a href="/views<?php echo gp_url_project( $project );?>/<?php echo esc_attr($view_id)?>/-edit" class="action edit">(edit)</a></h3>
		<dl>
			<dt><?php echo _n( 'Term:', 'Terms', count( $view->terms ) ); ?></dt>
			<dd><?php echo ( nl2br( esc_html( implode("\n", $view->terms ) ) ) );?></dd>
		<?php if ( $view->priorities ) : ?>
			<dt><?php echo _n( 'Priority:', 'Priorities', count( $view->priorities ) ); ?></dt>
			<dd>
			<?php
				$valid_priorities = GP::$original->get_static( 'priorities' );
				foreach( (array) $view->priorities as $priority ){
					echo esc_html( $valid_priorities[$priority] );
				}
			?>
			</dd>
		<?php endif;?>
			<dt>View visibility:</dt>
			<dd>
			<?php
				if ( $view->public == 'true' ) {
					echo 'Public';
				} else {
					echo 'Super admins only';
				}
			?>
			</dd>
		</dl>
	</div>
	<?php endforeach; ?>


<?php gp_tmpl_footer();
