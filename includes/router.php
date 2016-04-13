<?php
class GP_Route_Views extends GP_Route_Main {

	function __construct() {
		$this->plugin = GP_Views::get_instance();
		$this->template_path = dirname( dirname( __FILE__ ) ) . '/templates/';
	}

	function views_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$this->plugin->set_project_id( $project->id );

		$views = $this->plugin->views;

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

		$this->plugin->set_project_id( $project->id );

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

		$this->plugin->set_project_id( $project->id );

		if ( ! isset( $this->plugin->views[$view_id] ) ) {
			$this->die_with_404();
		}

		$this->plugin->delete( $view_id );
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

		$this->plugin->set_project_id( $project->id );

		if ( ! $this->plugin->save( gp_post('view') ) ) {
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

		$this->plugin->set_project_id( $project->id );

		if ( ! isset( $this->plugin->views[$view_id] ) ) {
			$this->die_with_404();
		}

		$view = $this->plugin->views[$view_id];
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

		$this->plugin->set_project_id( $project->id );

		if ( ! isset( $this->plugin->views[$view_id] ) ) {
			$this->die_with_404();
		}

		if ( ! $this->plugin->save( gp_post('view'), $view_id ) ) {
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

		$this->plugin->set_project_id( $project->id );

		if ( ! isset( $this->plugin->views[$id] ) ) {
			$this->die_with_404();
		}

		$cache_key = "views_{$project->id}_$id";

		$stats = wp_cache_get( $cache_key, 'gp_views' );

		if ( false === $stats ) {
			$sets = GP::$translation_set->by_project_id( $project->id );
			$this->plugin->current_view = $id;

			$stats = array();
			$this->plugin->set_originals_ids_for_view();
			$stats['originals'] = count( $this->plugin->originals_ids  );

			foreach ( $sets as $set ) {
				$translated_count = $this->plugin->translations_count_in_view_for_set_id( $set->id );
				$stats['translation_sets'][$set->locale]['current'] = $translated_count;
				$stats['translation_sets'][$set->locale]['untranslated'] = $stats['originals'] - $translated_count;
				$stats['translation_sets'][$set->locale]['percent'] = floor( $translated_count/$stats['originals']*100 );
			}
			wp_cache_set( $cache_key, $stats, 'gp_views', 6 * 60 * 60 );
		}

		$this->tmpl( 'stats', get_defined_vars() );
	}

}
