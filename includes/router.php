<?php
class GP_Route_Views extends GP_Route_Main {

	function __construct() {
		$this->template_path = dirname( dirname( __FILE__ ) ) . '/templates/';
	}

	function views_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		GP::$plugins->views->set_project_id( $project->id );
		$views = GP::$plugins->views->views;

		$this->tmpl( 'views', get_defined_vars() );

	}

	function view_new_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		GP::$plugins->views->set_project_id( $project->id );

		$view = new GP_Views_View( array() );
		$this->tmpl( 'edit', get_defined_vars() );
	}

	function view_delete( $project_path, $view_id ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		GP::$plugins->views->set_project_id( $project->id );

		if ( ! isset( GP::$plugins->views->views[$view_id] ) ) {
			$this->die_with_404();
		}

		GP::$plugins->views->delete( $view_id );
		$this->notices[] =  __( 'View deleted' );

		$this->redirect( gp_url( '/views' . gp_url_project( $project) ) );
	}

	function view_new_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		GP::$plugins->views->set_project_id( $project->id );

		if ( ! GP::$plugins->views->save( gp_post('view') ) ) {
			$this->errors[] = __( 'Error creating view' );
		} else {
			$this->notices[] =  __( 'View Created' );
		}
		$this->redirect( gp_url( '/views' . gp_url_project( $project) ) );
	}

	function view_edit_get( $project_path, $view_id ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		GP::$plugins->views->set_project_id( $project->id );

		if ( ! isset( GP::$plugins->views->views[$view_id] ) ) {
			$this->die_with_404();
		}

		$view = GP::$plugins->views->views[$view_id];
		$this->tmpl( 'edit', get_defined_vars() );
	}

	function view_edit_post( $project_path, $view_id ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		GP::$plugins->views->set_project_id( $project->id );

		if ( ! isset( GP::$plugins->views->views[$view_id] ) ) {
			$this->die_with_404();
		}

		if ( ! GP::$plugins->views->save( gp_post('view'), $view_id ) ) {
			$this->errors[] = __( 'Error creating view' );
		} else {
			$this->notices[] =  __( 'View saved' );
		}

		$this->redirect( gp_url( '/views' . gp_url_project( $project ) ) );
	}

	function view_stats( $project_path, $id ) {

		if ( ! $this->api ) {
			$this->die_with_404();
		}

		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		GP::$plugins->views->set_project_id( $project->id );

		if ( ! isset( GP::$plugins->views->views[$id] ) ) {
			$this->die_with_404();
		}

		$cache_key = "views_{$project->id}_$id";

		$stats = wp_cache_get( $cache_key, 'gp_views' );

		if ( false === $stats ) {
			$sets = GP::$translation_set->by_project_id( $project->id );
			GP::$plugins->views->current_view = $id;

			$stats = array();
			GP::$plugins->views->set_originals_ids_for_view();
			$stats['originals'] = count( GP::$plugins->views->originals_ids  );

			foreach ( $sets as $set ) {
				$translated_count = GP::$plugins->views->translations_count_in_view_for_set_id( $set->id );
				$stats['translation_sets'][$set->locale]['current'] = $translated_count;
				$stats['translation_sets'][$set->locale]['untranslated'] = $stats['originals'] - $translated_count;
				$stats['translation_sets'][$set->locale]['percent'] = floor( $translated_count/$stats['originals']*100 );
			}
			wp_cache_set( $cache_key, $stats, 'gp_views', 6 * 60 * 60 );
		}

		$this->tmpl( 'stats', get_defined_vars() );
	}

}