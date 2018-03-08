<style type="text/css">
	.gp-views-progress-bar {
		margin-top: 1em;
		height: 16px;
		line-height: 1px;
		border: 1px solid black;
		padding: 2px;
		text-align: left;
	}
	.gp-views-progress-bar div {
		height: 10px;
		display: inline-block;
		margin: 0;
	}
	.gp-views-progress-bar .color-1 {
		background-color: #ea999a;
	}
	.gp-views-progress-bar .color-2 {
		background-color: #ffe699;
	}
	.gp-views-progress-bar .color-3 {
		background-color: #b6d7a8;
	}
</style>
<?php
$percents = array();
$remaining_percent = $percent_translated = $set->percent_translated();
foreach ( $views as $view ) {
	$class = 'color-3';
	if ( $view->percent < 30 ) {
		$class = 'color-1';
	} elseif ( $view->percent < 100 ) {
		$class = 'color-2';
	}
	if ( ! isset( $percents[ $class ] ) ) {
		$percents[ $class ] = 0;
	}
	$percents[ $class ] += $view->untranslated;
}
ksort( $percents );
foreach ( $percents as $class => $count ) {
	if ( $class !== 'color-3' ) {
		$percent = max( 1, $count / $set->all_count * 100 );
		$percents[ $class ] = $percent;
		$remaining_percent -= $percent;
	}
}
$percents[ 'color-3' ] = $remaining_percent;
?><a href="<?php echo esc_url( gp_url_join( gp_url( '/views' ), gp_url_project( $project ), $set->locale, $set->slug ) ); ?>">
<div class="gp-views-progress-bar"><?php
	foreach ( $percents as $class => $percent ) {
		?><div class="<?php echo $class; ?>" style="width: <?php echo $percent; ?>%"></div><?php
	}
?></div>
<p style="text-align: center"><?php echo number_format( $set->current_count() ); ?> / <?php echo number_format( $set->all_count() ); ?> Strings (<?php echo esc_html( $set->percent_translated() ); ?> %)</p>
</a>
