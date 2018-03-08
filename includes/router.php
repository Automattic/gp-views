<?php
class GP_Route_Views extends GP_Route {

	var $plugin;

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
		$views = $this->sort_by_rank( $views );

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
		wp_enqueue_media();

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

		if ( ! isset( $this->plugin->views[ $view_id ] ) ) {
			$this->die_with_404();
		}

		$this->plugin->delete( $view_id );
		$this->notices[] = __( 'View deleted' );

		$this->redirect( gp_url( '/views' . gp_url_project( $project ) ) );
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

		if ( ! $this->plugin->save( gp_post( 'view' ) ) ) {
			$this->errors[] = __( 'Error creating view' );
		} else {
			$this->notices[] = __( 'View Created' );
		}

		wp_cache_delete( "views_stats_{$project->id}", 'gp_views' );

		$this->redirect( gp_url( '/views' . gp_url_project( $project ) ) );
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

		if ( ! isset( $this->plugin->views[ $view_id ] ) ) {
			$this->die_with_404();
		}
		wp_enqueue_media();

		$view = $this->plugin->views[ $view_id ];

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

		if ( ! isset( $this->plugin->views[ $view_id ] ) ) {
			$this->die_with_404();
		}

		if ( ! $this->plugin->save( gp_post( 'view' ), $view_id ) ) {
			$this->errors[] = __( 'Error creating view' );
		} else {
			$this->notices[] = __( 'View saved' );
		}

		wp_cache_delete( "views_stats_{$project->id}", 'gp_views' );

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

		if ( ! isset( $this->plugin->views[ $id ] ) ) {
			$this->die_with_404();
		}

		$cache_key = "views_{$project->id}_$id";

		$stats = wp_cache_get( $cache_key, 'gp_views' );

		if ( false === $stats ) {
			$sets = GP::$translation_set->by_project_id( $project->id );
			$this->plugin->current_view = $id;

			$stats = array();
			$this->plugin->set_originals_ids_for_view();
			$stats['originals'] = count( $this->plugin->originals_ids );

			foreach ( $sets as $set ) {
				$translated_count = $this->plugin->translations_count_in_view_for_set_id( $set->id );
				$set_stats = array(
					'current' => $translated_count,
					'untranslated' => $stats['originals'] - $translated_count,
					'percent' => floor( $translated_count / $stats['originals'] * 100 ),
				);

				$set_key = ( 'default' !== $set->slug ) ? "{$set->locale}_$set->slug" : $set->locale;
				$stats['translation_sets'][ $set_key ] = $set_stats;
			}
			wp_cache_set( $cache_key, $stats, 'gp_views', 6 * 60 * 60 );
		}

		$this->tmpl( 'stats', get_defined_vars() );
	}

	function locale_stats( $project_path, $locale_slug, $slug ) {

		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}
		$set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $slug, $locale_slug );
		if ( ! $set ) {
			$this->die_with_404();
		}

		$this->plugin->set_project_id( $project->id );

		$views = [];
		foreach ( $this->plugin->views as $id => $view ) {

			if ( 'true' != $view->public ) {
				if (
					! GP::$permission->current_user_can( 'write', 'project', $this->project_id )
					|| isset( $_GET['public'] ) ) {
					continue;
				}
			}

			$views[ $id ] = $view;
		}

		if ( empty( $views ) ) {
			echo 'No views found.';
			return;
		}

		// Allows invalidating all stats when changing views in a project.
		$stats_cache_key = "views_stats_{$project->id}";
		$cache_key = wp_cache_get( $stats_cache_key, 'gp_views' );
		if ( false === $cache_key ) {
			$cache_key = "views_stats_{$project->id}_" . time();
			wp_cache_set( $stats_cache_key, $cache_key, 'gp_views' );
		}

		$cache_key .= "_{$locale_slug}_{$slug}";

		$stats = wp_cache_get( $cache_key, 'gp_views' );

		if ( false === $stats ) {
			$stats = [];
			foreach ( $views as $id => $view ) {
				$stats[ $id ] = [];
				$this->plugin->current_view = $id;

				$this->plugin->set_originals_ids_for_view();
				$originals = max( count( $this->plugin->originals_ids ), 1 );
				$translated_count = $this->plugin->translations_count_in_view_for_set_id( $set->id );
				$stats[ $id ]['current'] = $translated_count;
				$stats[ $id ]['total'] = $originals;
				$stats[ $id ]['untranslated'] = $originals - $translated_count;
				$stats[ $id ]['percent'] = floor( $translated_count / $originals * 100 );
			}
		}
		wp_cache_set( $cache_key, $stats, 'gp_views' );

		foreach ( $views as $id => $view ) {
			$views[ $id ] = (object) array_merge( (array) $view, $stats[ $id ] );
			$views[ $id ]->url = gp_url_project_locale( $project->path, $locale_slug, $slug ) . '?filters[status]=untranslated&filters[view]=' . rawurlencode( $id );
		}
		$views = $this->sort_by_rank( $views );

		unset( $stats );

		$this->tmpl( 'locale-stats', get_defined_vars() );
	}

	private function sort_by_rank( $views ) {
		uasort( $views, function( $a, $b ) {
			if ( ! $a->rank ) {
				$a->rank = 100;
			}
			if ( ! $b->rank ) {
				$b->rank = 100;
			}
			if ( $a->rank === $b->rank ) {
				return $a->name <=> $b->name;
			}
			return $a->rank <=> $b->rank;
		} );

		return $views;
	}
}
