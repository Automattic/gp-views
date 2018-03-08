<?php
// translators: %1$s is the translation set name, %2$s is the project name
gp_title( sprintf( __( 'Locales &lt; %1$s &lt; View Stats &lt; %2$s &lt; GlotPress' ), esc_html( $set->name ), esc_html( $project->name ) ) );

gp_breadcrumb( array(
	gp_link_get( gp_url( '/languages' ), __( 'Locales', 'glotpress' ) ),
	gp_link_get( gp_url_join( gp_url( '/languages' ), $set->locale, $set->slug ), esc_html( $set->name ) ),
	__( 'View Stats' ),
	gp_project_links_from_root( $project ),
) );
gp_tmpl_header();
?>
	<h2><?php // translators: %1$s is the project name, %2$s is the translation set name
	printf( __( 'Translation Breakdown for %1$s in %2$s' ), $project->name, $set->name ); ?></h2>

	<div class="gp-views-progress-bar"><div class="<?php $set->percent = $set->percent_translated(); if ( $set->percent < 30 ) echo 'low'; elseif ( $set->percent < 85 ) echo 'medium'; else echo 'high'; ?>" style="width: <?php echo $set->percent; ?>%"></div></div>
	<p style="text-align: center"><?php echo number_format( $set->current_count() ); ?> / <?php echo number_format( $set->all_count() ); ?> Strings (<?php echo esc_html( $set->percent ); ?> %)</p>

	<div class="gp-views-views">
	<?php foreach ( $views as $id => $view ) : ?>
		<div class="gp-views-view">
			<a href="<?php echo $view->url; ?>">
			<?php if ( $view->screenshot ) {
				echo wp_get_attachment_image( $view->screenshot, 'medium', false, [
					'id' => 'gp-views-preview-screenshot',
				] );
			} ?></a>
			<h3><a href="<?php echo $view->url; ?>"><?php echo esc_html( $view->name ); ?></a></h3>
			<div class="gp-views-progress-bar"><div class="<?php if ( $view->percent < 30 ) echo 'low'; elseif ( $view->percent < 85 ) echo 'medium'; else echo 'high'; ?>" style="width: <?php echo $view->percent; ?>%"></div></div>
			<p><?php echo number_format( $view->current ); ?> / <?php echo number_format( $view->total ); ?> Strings (<?php echo esc_html( $view->percent ); ?> %)</p>
		</div>
	<?php endforeach; ?>
</div>
<style type="text/css">
	div.gp-views-views {
		display: flex;
		justify-content: space-around;
		align-items: flex-end;
		flex-wrap: wrap;
	}
	div.gp-views-view {
		padding: 1em;
		text-align: center;
		flex-basis: 30%;
	}
	div.gp-views-view h3 {
		background: none;
		padding: 0;
		margin: .5em;
	}
	div.gp-views-view a {
		color: #2e4453;
	}
	.gp-views-progress-bar {
		margin-top: 1em;
		height: 16px;
		border: 1px solid black;
		padding: 2px;
	}
	.gp-views-progress-bar div {
		height: 10px;
	}
	.gp-views-progress-bar .low {
		background-color: #ea999a;
	}
	.gp-views-progress-bar .medium {
		background-color: #ffe699;
	}
	.gp-views-progress-bar .high {
		background-color: #b6d7a8;
	}
</style>
<?php gp_tmpl_footer();

