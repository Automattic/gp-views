<?php
/**
 * Plugin name: GlotPress: Views
 * Plugin author: Automattic
 * Version: 1.0
 *
 * Description: GlotPress plugin to set up predefined views to search for strings using multiple conditions
 */

require_once( dirname(__FILE__) .'/includes/router.php' );
require_once( dirname(__FILE__) .'/things/view.php' );

class GP_Views {

	var $id = 'gp_views';
	var $current_view = null;
	var $project_id = null;
	var $original_ids = null;

	var $views = array();

	private static $instance = null;

	public static function init() {
		self::get_instance();
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'gp_translation_set_filters_form', array( $this, 'translation_set_filters' ) );
		add_filter( 'gp_for_translation_where', array( $this, 'for_translation_where' ), 10, 2 );
		add_filter( 'gp_project_actions', array( $this, 'gp_project_actions' ), 10, 2 );
		add_action( 'gp_translation_saved', array( $this, 'gp_translation_saved' ), 10 );
		$this->add_routes();
	}

	function set_project_id( $project_id ) {
		$this->project_id = $project_id;
		$this->views = get_option( 'views_' . $this->project_id, array() );

		$this->set_current_view();
	}

	function set_current_view() {
		if (  gp_get('view') ) {
			$_GET['filters']['view'] = gp_get('view');
		}

		$filters = gp_get( 'filters' );
		if ( isset( $filters['view'] ) && isset( $this->views[ $filters['view'] ] ) ) {
			if ( isset( $_GET['filters']['term'] ) ) {
				unset( $_GET['filters']['term'] );
			}
			$this->current_view = $filters['view'];
		}
	}

	function save( $view_data, $view_id = null ) {
		if ( ! $this->project_id ) {
			return;
		}

		if ( ! $view_id  ) {
			$view_id = sanitize_title_with_dashes( $view_data['name' ] );
			$i = 1;

			while( in_array( $view_id, array_keys($this->views ) ) && $i < 10 ) {
				$view_id = esc_attr( $view_data['name' ] ) . '_' . $i++;
			}

			//TODO: return error instead of overwriting in case we're out of the loop at 10
		}

		if ( $view_data['terms'] ) {
			$view_data['terms'] = explode( "\n", str_replace( "\r", "", trim( $view_data['terms'] ) ) );
		}

		if ( isset( $view_data['rank'] ) ) {
			$view_data['rank'] = intval( $view_data['rank'] );
			if ( $view_data['rank'] < 0 || $view_data['rank'] > 1000 ) {
				$view_data['rank'] = 100;
			}
		}

		$view = new GP_Views_View( $view_data );
		try {
			$view->validate();
		} catch( Exception $e) {
			return false;
		}

		/**
		 * Fires on saving a view
		 *
		 * @param bool  $can_save   Whether the view can be saved.
		 * @param int   $project_id The project of the view.
		 * @param int   $view_id    The view id.
		 * @param array $new_view   The new view content.
		 * @param array $old_view   The old view content.
		 */
		if ( ! apply_filters( 'gp_views_save', true, $this->project_id, $view_id, $view, $this->views[$view_id] ) ) {
			return false;
		}

		$this->views[$view_id] = $view;

		update_option( 'views_' . $this->project_id ,$this->views );
		return true;
	}

	function delete( $view_id ) {

		if ( ! $this->project_id ) {
			return;
		}

		if ( ! apply_filters( 'gp_views_save', true, $this->project_id, $view_id, null, $this->views[$view_id] ) ) {
			return false;
		}

		unset($this->views[$view_id]);
		update_option( 'views_' . $this->project_id ,$this->views );
	}

	function set_originals_ids_for_view() {
		global $wpdb;

		if ( ! $this->current_view || ! $this->project_id ) {
			return;
		}

		$terms = $this->views[$this->current_view]->terms;

		$priorities = $this->views[$this->current_view]->priorities;


		if ( ! empty( $priorities ) ) {
			$priority_where = 'AND o.priority in( ' . implode( ',', $priorities ) . ')';
		} else {
			$priority_where = "AND o.priority !='-2'";
		}

		$view_where = array();

		foreach ( $terms as $term ) {
			if ( substr( $term, 0, 1 ) === '-' ) {
				$like = "NOT LIKE '%" . ( $wpdb->escape( like_escape ( substr( $term, 1 ) ) ) ) . "%'";
			} else {
				$like = "LIKE '%" . ( $wpdb->escape( like_escape ( $term ) ) ) . "%'";
			}
			$view_where[] = '(' . implode( ' OR ', array_map( lambda('$x', '"($x $like)"', compact('like')), array('o.singular', 'o.plural', 'o.context', 'o.references' ) ) ) . ')';
		}

		$view_where = '(' . implode( ' OR ', $view_where ) . ')';

		$this->originals_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->gp_originals} AS o WHERE $view_where AND o.status LIKE '+%' $priority_where AND o.project_id =" . absint( $this->project_id ) );
	}

	function get_paths_for_project( $project_id ) {
		if ( ! GP::$permission->current_user_can( 'write', 'project', $this->project_id ) ) {
			return [];
		}

		global $wpdb;

		$cache_key = 'project_paths_' . $project_id;
		$paths = wp_cache_get( $cache_key, 'gp_views' );

		if ( true||! $paths ) {
			$paths = array();
			$references = $wpdb->get_col( "SELECT `references` FROM {$wpdb->gp_originals} WHERE `status` LIKE '+%' AND `project_id` =" . absint( $project_id ) );

			foreach ( $references as $reference ) {
				foreach ( explode( ' ', $reference ) as $line ) {
					$dir = trim( dirname( $line ), '/' );
					$dirs = explode( '/', $dir );
					switch ( $dirs[0] ) {
						case '.':
							continue 2;
						case 'client':
						case 'server':
							$dir = implode( '/', array_slice( $dirs, 0, 3 ) );
							break;
					}
					$paths[ $dir . '/' ] = true;
				}
			}

			ksort( $paths );

			wp_cache_set( $cache_key, $paths, 'gp_views' );
		}
		return $paths;
	}

	function translations_count_in_view_for_set_id( $set_id ) {
		global $wpdb;

		if ( $this->originals_ids ) {
			$this->set_originals_ids_for_view();
		}

		if ( ! is_array( $this->originals_ids ) ) {
			return;
		}

		$originals = implode( ', ', $this->originals_ids );

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) as current
				FROM $wpdb->gp_translations WHERE translation_set_id = %d AND original_id IN( $originals ) AND status = 'current'", $set_id ) );

	}

	function for_translation_where( $where, $translation_set ) {

		$this->set_project_id( $translation_set->project_id );

		if ( ! $this->current_view ) {
			return $where;
		}

		global $wpdb;
		$terms = $this->views[$this->current_view]->terms;

		$priorities = $this->views[$this->current_view]->priorities;

		if ( ! empty( $priorities ) ) {
			$priority_where = 'o.priority in( ' . implode( ',', $priorities ) . ')';
		} else {
			$priority_where = "o.priority !='-2'";
		}


		$view_where = array();
		foreach ( $terms as $term ) {
			$like = "LIKE '%" . ( $wpdb->escape( like_escape ( $term ) ) ) . "%'";
			$view_where[] = '(' . implode( ' OR ', array_map( lambda('$x', '"($x $like)"', compact('like')), array( 'o.singular', 'o.plural', 'o.context', 'o.references' ) ) ) . ')';
		}
		$view_where = '(' . implode( ' OR ', $view_where ) . ')';
		$where[] = $view_where;
		$where[] = $priority_where;
		return $where;
	}

	function add_routes() {
		$path = '(.+?)';
		$id = '([^\/]+)';
		$locale = '([^\/]+)/([^\/]+)';
		$projects = 'projects';
		$project = $projects.'/'.$path;

		GP::$router->add( "/views/$project/$id/stats", array( 'GP_Route_Views', 'view_stats' ), 'get' );
		GP::$router->add( "/views/$project/$id/-edit", array( 'GP_Route_Views', 'view_edit_get' ), 'get' );
		GP::$router->add( "/views/$project/$id/-edit", array( 'GP_Route_Views', 'view_edit_post' ), 'post' );
		GP::$router->add( "/views/$project/$id/-delete", array( 'GP_Route_Views', 'view_delete' ), 'get' );
		GP::$router->add( "/views/$project/$locale", array( 'GP_Route_Views', 'locale_stats' ), 'get' );
		GP::$router->add( "/views/$project/coverage", array( 'GP_Route_Views', 'view_coverage' ), 'get' );
		GP::$router->add( "/views/$project/-add", array( 'GP_Route_Views', 'view_new_get' ), 'get' );
		GP::$router->add( "/views/$project/-add", array( 'GP_Route_Views', 'view_new_post' ), 'post' );
		GP::$router->add( "/views/$project", array( 'GP_Route_Views', 'views_get' ), 'get' );
	}

	function gp_project_actions( $actions, $project ) {
		$actions[] = gp_link_get( '/views' . gp_url_project( $project ), __( 'Project Views' ) );
		return $actions;
	}

	function gp_translation_saved( $translation ) {
		$set = GP::$translation_set->get( $translation->translation_set_id );

		$stats_cache_key = "views_stats_{$set->project_id}";
		$cache_key = wp_cache_get( $stats_cache_key, 'gp_views' );
		if ( $cache_key ) {
			$cache_key .= "_{$set->locale}_{$set->slug}";
			wp_cache_delete( $cache_key, 'gp_views' );
		}

		wp_cache_delete( 'project_paths_' . $set->project_id, 'gp_views' );

	}

	function translation_set_filters() {

		if ( empty( $this->views ) ) {
			return;
		}

		$views_for_select = array();
		foreach ( $this->views as $id => $view ) {

			if ( 'true' != $view->public && ! GP::$permission->current_user_can( 'write', 'project', $this->project_id ) ) {
				continue;
			}

			$views_for_select[$id] = $view->name;
		}

		if ( empty( $views_for_select ) ) {
			return;
		}

		$views_for_select = array( '' =>  __('&mdash; Select &mdash;' ) ) + $views_for_select;
		//TODO: use template
	?>
			<dt><?php _e( 'Views' ); ?></dt>
			<dd>
				<?php echo gp_select( 'filters[view]', $views_for_select, $this->current_view ); ?>
			</dd>
	<?php
		}
}

add_action( 'gp_init', array( 'GP_Views', 'init' ) );
