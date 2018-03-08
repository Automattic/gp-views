<?php
// translators: %s is the project name
gp_title( sprintf( __( 'View Coverage &lt; %s &lt; GlotPress' ), esc_html( $project->name ) ) );

gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
) );
gp_tmpl_header();
?>
	<style type="text/css">
		.gp-views-missing, .gp-views-missing a {
			color: #b00;
		}
		.gp-views-covered, .gp-views-covered a {
			color: #999;
		}
		u {
			text-decoration: underline;
		}
		.site-header {
			position: absolute !important;
		}
	</style>
	<h2><?php // translators: %s is the project name
	printf( __( 'Path coverage for %1$s' ), $project->name ); ?></h2>

	<?php foreach ( $paths as $path => $view_id ) : ?>
		<?php if ( $view_id === true ) : ?>
			<span class="gp-views-missing"><a href="<?php echo $project->source_url( $path, 0 ); ?>"><?php echo esc_html( $path ); ?></a></span>
		<?php else : ?>
			<span class="gp-views-covered"><a href="<?php echo $project->source_url( $path, 0 ); ?>"><?php echo str_replace( esc_html( $match[ $path ] ), '<u>' . esc_html( $match[ $path ] ) . '</u>', esc_html( $path ) ); ?></a> in <span style="color: #<?php echo substr( md5( $views[$view_id]->name ), 0, 6 );?>"><?php echo esc_html( $views[$view_id]->name );?></span> <a href="/views<?php echo gp_url_project( $project );?>/<?php echo esc_attr( $view_id )?>/-edit" class="action edit">(edit)</a></span>
		<?php endif; ?><br/>
	<?php endforeach; ?>
</div>
<?php gp_tmpl_footer();

