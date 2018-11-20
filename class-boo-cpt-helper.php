<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boo_CPT_Helper
 *
 * This is helper class for CPTs Custom Post Types
 *
 * A lot of code borrowed from:
 * https://github.com/WebDevStudios/CPT_Core/
 *
 * @version 1.0
 *
 * @author RaoAbid | BooSpot
 * @link https://github.com/boospot/boo-cpt-helper
 */
if ( ! class_exists( 'Boo_CPT_Helper' ) ):

	class Boo_CPT_Helper {

		/**
		 * Singular CPT label
		 * @var string
		 */
		protected $singular;
		/**
		 * Plural CPT label
		 * @var string
		 */
		protected $plural;
		/**
		 * Registered CPT name/slug
		 * @var string
		 */
		protected $post_type;
		/**
		 * Optional argument overrides passed in from the constructor.
		 * @var array
		 */
		protected $arg_overrides = array();
		/**
		 * All CPT registration arguments
		 * @var array
		 */
		protected $cpt_args = array();


		public $text_domain = '';

		public $options = array();

		public $cpt_to_register = array();

		/**
		 * An array of each CPT_Core object registered with this class
		 * @var array
		 */
		protected static $custom_post_types = array();
		/**
		 * Whether text-domain has been registered
		 * @var boolean
		 */
		protected static $l10n_done = false;

		/**
		 * Constructor. Builds our CPT.
		 * @since 0.1.0
		 *
		 * @param mixed $cpt Array with Singular, Plural, and Registered (slug)
		 * @param array $arg_overrides CPT registration override arguments
		 */
		public function __construct( $config = array(), $options_override = array() ) {

//			if ( empty( $config ) ) {
//				return null;
//			}

			$this->set_properties( $options_override );

			$this->configure_post_types( $config, $options_override );

		}

		public function is_assoc( array $arr ) {
			if ( array() === $arr ) {
				return false;
			}

			return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
		}

		public function configure_post_types( $config, $options_override ) {

			if ( is_string( $config ) ) {
				$this->add_cpt_single( $config );
			}

			if ( is_array( $config ) ) {

//				if ( ! $this->is_assoc( $config ) ) {
//					wp_die( __( 'array key missing for custom post type configuration array', $this->text_domain ) );
//				}

				foreach ( $config as $cpt => $args ) {
//
//
//					var_dump_die( $args);

					$this->add_cpt_single( $cpt, $args );
				}

			}

			// load text domain
			add_action( 'plugins_loaded', array( $this, 'l10n' ), 5 );
			add_action( 'init', array( $this, 'register_post_type' ) );


		}

		public function set_properties( $options_override ) {

			// Here we will set properties


		}

		public function add_cpt_single( $cpt, $args = array() ) {

			$cpt = $this->normalize_cpt_name( $cpt );

//			$args = wp_parse_args( $args, $this->normalize_cpt_args( $cpt, $args ) );
			$args = $this->normalize_cpt_args( $cpt, $args );

			$this->cpt_to_register[ $cpt ] = $args;


//			var_dump_die( $args);

			$this->singular  = $args['singular'];
			$this->plural    = ! isset( $args['plural'] ) || ! is_string( $args['plural'] ) ? $args['singular'] . 's' : $args['plural'];
			$this->post_type = $cpt;

//			$this->post_type = ! isset( $args['slug'] ) || ! is_string( $args['slug'] ) ? sanitize_title( $this->plural ) : $args['slug'];


//			$this->cpt_args = (array) $args;

//			add_filter( 'post_updated_messages', array( $this, 'messages' ) );
//			add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_messages' ), 10, 2 );
//			add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'columns' ) );
//			add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'sortable_columns' ) );
//			// Different column registration for pages/posts
//			$h = isset( $arg_overrides['hierarchical'] ) && $args['hierarchical'] ? 'pages' : 'posts';
//			add_action( "manage_{$h}_custom_column", array( $this, 'columns_display' ), 10, 2 );
//			add_filter( 'enter_title_here', array( $this, 'title' ) );
		}


		public function normalize_cpt_name( $cpt_name ) {
			return strtolower( str_replace( ' ', '_', $cpt_name ) );
		}

		public function get_slug_from_cpt_name( $cpt_name ) {
			return str_replace( '_', '-', $cpt_name );
		}

		public function normalize_cpt_args( $cpt, $args ) {

			$labels_args = isset( $args['labels'] ) && is_array( $args['labels'] ) ? $args['labels'] : array();

			$args['slug'] = isset( $args['slug'] ) ? $args['slug'] : $this->get_slug_from_cpt_name( $cpt );

			$args['singular']  = isset( $args['singular'] ) ? $args['singular'] : str_replace( '_', ' ', $cpt );
			$args['plural']    = isset( $args['plural'] ) ? $args['plural'] : ucwords( $args['singular'] ) . 's';
			$args['menu_name'] = isset( $args['plural'] ) ? $args['plural'] : str_replace( '_', ' ', $args['singular'] );


			$default_labels = $this->get_cpt_labels_array( $args );

			$args['labels'] = array_merge( $default_labels, $labels_args );

			if ( ! isset( $args['rewrite'] ) ) {
				$args['rewrite'] = array( 'slug' => $args['slug'] );

			}

			$args = array_merge(
			// Default
				$this->get_default_cpt_args()
				,
				// Given args
				$args
			);


			return $args;
		}

		public function get_default_cpt_args() {

			$args = array(
				'description'        => __( '', $this->text_domain ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array(
					'title',
					'editor',
					'excerpt',
					'author',
					'thumbnail',
					'comments',
					'trackbacks',
					'custom-fields',
					'revisions',
					'page-attributes',
					'post-formats',
				),
				'menu_icon'          => 'dashicons-admin-generic',
				'show_in_nav_menus'  => true,
			);

			return $args;
		}


		public function get_cpt_labels_array( $fields ) {
			/**
			 * Labels used when displaying the posts in the admin and sometimes on the front end.  These
			 * labels do not cover post updated, error, and related messages.  You'll need to filter the
			 * 'post_updated_messages' hook to customize those.
			 */
			$labels = array(
				'name'                  => $fields['plural'],
				'singular_name'         => $fields['singular'],
				'menu_name'             => $fields['menu_name'],
				'new_item'              => sprintf( __( 'New %s', 'plugin-name' ), $fields['singular'] ),
				'add_new_item'          => sprintf( __( 'Add new %s', 'plugin-name' ), $fields['singular'] ),
				'edit_item'             => sprintf( __( 'Edit %s', 'plugin-name' ), $fields['singular'] ),
				'view_item'             => sprintf( __( 'View %s', 'plugin-name' ), $fields['singular'] ),
				'view_items'            => sprintf( __( 'View %s', 'plugin-name' ), $fields['plural'] ),
				'search_items'          => sprintf( __( 'Search %s', 'plugin-name' ), $fields['plural'] ),
				'not_found'             => sprintf( __( 'No %s found', 'plugin-name' ), strtolower( $fields['plural'] ) ),
				'not_found_in_trash'    => sprintf( __( 'No %s found in trash', 'plugin-name' ), strtolower( $fields['plural'] ) ),
				'all_items'             => sprintf( __( 'All %s', 'plugin-name' ), $fields['plural'] ),
				'archives'              => sprintf( __( '%s Archives', 'plugin-name' ), $fields['singular'] ),
				'attributes'            => sprintf( __( '%s Attributes', 'plugin-name' ), $fields['singular'] ),
				'insert_into_item'      => sprintf( __( 'Insert into %s', 'plugin-name' ), strtolower( $fields['singular'] ) ),
				'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'plugin-name' ), strtolower( $fields['singular'] ) ),

				/* Labels for hierarchical post types only. */
				'parent_item'           => sprintf( __( 'Parent %s', 'plugin-name' ), $fields['singular'] ),
				'parent_item_colon'     => sprintf( __( 'Parent %s:', 'plugin-name' ), $fields['singular'] ),

				/* Custom archive label.  Must filter 'post_type_archive_title' to use. */
				'archive_title'         => $fields['plural'],
			);

			return $labels;

		}

		/**
		 * Gets the requested CPT argument
		 *
		 * @param string $arg
		 *
		 * @since  0.2.1
		 * @return array|false  CPT argument
		 */
		public function get_arg( $arg ) {
			$args = $this->get_args();
			if ( isset( $args->{$arg} ) ) {
				return $args->{$arg};
			}
			if ( is_array( $args ) && isset( $args[ $arg ] ) ) {
				return $args[ $arg ];
			}

			return false;
		}

		/**
		 * Gets the passed in arguments combined with our defaults.
		 * @since  0.2.0
		 * @return array  CPT arguments array
		 */
		public function get_args() {
			if ( ! empty( $this->cpt_args ) ) {
				return $this->cpt_args;
			}
			// Generate CPT labels
			$labels = array(
				'name'                  => $this->plural,
				'singular_name'         => $this->singular,
				'add_new'               => sprintf( __( 'Add New %s', 'cpt-core' ), $this->singular ),
				'add_new_item'          => sprintf( __( 'Add New %s', 'cpt-core' ), $this->singular ),
				'edit_item'             => sprintf( __( 'Edit %s', 'cpt-core' ), $this->singular ),
				'new_item'              => sprintf( __( 'New %s', 'cpt-core' ), $this->singular ),
				'all_items'             => sprintf( __( 'All %s', 'cpt-core' ), $this->plural ),
				'view_item'             => sprintf( __( 'View %s', 'cpt-core' ), $this->singular ),
				'search_items'          => sprintf( __( 'Search %s', 'cpt-core' ), $this->plural ),
				'not_found'             => sprintf( __( 'No %s', 'cpt-core' ), $this->plural ),
				'not_found_in_trash'    => sprintf( __( 'No %s found in Trash', 'cpt-core' ), $this->plural ),
				'parent_item_colon'     => isset( $this->arg_overrides['hierarchical'] ) && $this->arg_overrides['hierarchical'] ? sprintf( __( 'Parent %s:', 'cpt-core' ), $this->singular ) : null,
				'menu_name'             => $this->plural,
				'insert_into_item'      => sprintf( __( 'Insert into %s', 'cpt-core' ), strtolower( $this->singular ) ),
				'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'cpt-core' ), strtolower( $this->singular ) ),
				'items_list'            => sprintf( __( '%s list', 'cpt-core' ), $this->plural ),
				'items_list_navigation' => sprintf( __( '%s list navigation', 'cpt-core' ), $this->plural ),
				'filter_items_list'     => sprintf( __( 'Filter %s list', 'cpt-core' ), strtolower( $this->plural ) )
			);
			// Set default CPT parameters
			$defaults                 = array(
				'labels'             => array(),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'has_archive'        => true,
				'supports'           => array( 'title', 'editor', 'excerpt' ),
			);
			$this->cpt_args           = wp_parse_args( $this->arg_overrides, $defaults );
			$this->cpt_args['labels'] = wp_parse_args( $this->cpt_args['labels'], $labels );

			return $this->cpt_args;
		}

		/**
		 * Actually registers our CPT with the merged arguments
		 * @since  0.1.0
		 */
		public function register_post_type() {


			foreach ( $this->cpt_to_register as $cpt => $args ) {

//			var_dump_die( $args);

//				register_post_type( $cpt, $this->get_args() );

//				var_dump_die( $this->get_args() );
				// Register our CPT
				$args = register_post_type( $cpt, $args );
				// If error, yell about it.
				if ( is_wp_error( $args ) ) {
					wp_die( $args->get_error_message() );
				}
			}


			// Success. Set args to what WP returns
//			$this->cpt_args = $args;
			// Add this post type to our custom_post_types array
//			self::$custom_post_types[ $this->post_type ] = $this;
		}

		/**
		 * Modifies CPT based messages to include our CPT labels
		 * @since  0.1.0
		 *
		 * @param  array $messages Array of messages
		 *
		 * @return array            Modified messages array
		 */
		public function messages( $messages ) {
			global $post, $post_ID;
			$cpt_messages = array(
				0 => '', // Unused. Messages start at index 1.
				2 => __( 'Custom field updated.' ),
				3 => __( 'Custom field deleted.' ),
				4 => sprintf( __( '%1$s updated.', 'cpt-core' ), $this->singular ),
				/* translators: %s: date and time of the revision */
				5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s', 'cpt-core' ), $this->singular, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				7 => sprintf( __( '%1$s saved.', 'cpt-core' ), $this->singular ),
			);
			if ( $this->get_arg( 'public' ) ) {
				$cpt_messages[1] = sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>', 'cpt-core' ), $this->singular, esc_url( get_permalink( $post_ID ) ) );
				$cpt_messages[6] = sprintf( __( '%1$s published. <a href="%2$s">View %1$s</a>', 'cpt-core' ), $this->singular, esc_url( get_permalink( $post_ID ) ) );
				$cpt_messages[8] = sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>', 'cpt-core' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) );
				// translators: Publish box date format, see http://php.net/date
				$cpt_messages[9]  = sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>', 'cpt-core' ), $this->singular, date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) );
				$cpt_messages[10] = sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>', 'cpt-core' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) );
			} else {
				$cpt_messages[1] = sprintf( __( '%1$s updated.', 'cpt-core' ), $this->singular );
				$cpt_messages[6] = sprintf( __( '%1$s published.', 'cpt-core' ), $this->singular );
				$cpt_messages[8] = sprintf( __( '%1$s submitted.', 'cpt-core' ), $this->singular );
				// translators: Publish box date format, see http://php.net/date
				$cpt_messages[9]  = sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>.', 'cpt-core' ), $this->singular, date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) );
				$cpt_messages[10] = sprintf( __( '%1$s draft updated.', 'cpt-core' ), $this->singular );
			}
			$messages[ $this->post_type ] = $cpt_messages;

			return $messages;
		}

		/**
		 * Custom bulk actions messages for this post type
		 * @author    Neil Lowden
		 *
		 * @param  array $bulk_messages Array of messages
		 * @param  array $bulk_counts Array of counts under keys 'updated', 'locked', 'deleted', 'trashed' and 'untrashed'
		 *
		 * @return array                  Modified array of messages
		 */
		function bulk_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ $this->post_type ] = array(
				'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'cpt-core' ), $bulk_counts['updated'], $this->singular, $this->plural ),
				'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'cpt-core' ), $bulk_counts['locked'], $this->singular, $this->plural ),
				'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'cpt-core' ), $bulk_counts['deleted'], $this->singular, $this->plural ),
				'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'cpt-core' ), $bulk_counts['trashed'], $this->singular, $this->plural ),
				'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'cpt-core' ), $bulk_counts['untrashed'], $this->singular, $this->plural ),
			);

			return $bulk_messages;
		}

		/**
		 * Registers admin columns to display. To be overridden by an extended class.
		 * @since  0.1.0
		 *
		 * @param  array $columns Array of registered column names/labels
		 *
		 * @return array           Modified array
		 */
		public function columns( $columns ) {
			// placeholder
			return $columns;
		}

		/**
		 * Registers which columns are sortable. To be overridden by an extended class.
		 *
		 * @since  0.1.0
		 *
		 * @param  array $sortable_columns Array of registered column keys => data-identifier
		 *
		 * @return array           Modified array
		 */
		public function sortable_columns( $sortable_columns ) {
			// placeholder
			return $sortable_columns;
		}

		/**
		 * Handles admin column display. To be overridden by an extended class.
		 *
		 * @since  0.1.0
		 *
		 * @param array $column Array of registered column names
		 * @param int $post_id The Post ID
		 */
		public function columns_display( $column, $post_id ) {
			// placeholder
		}

		/**
		 * Filter CPT title entry placeholder text
		 * @since  0.1.0
		 *
		 * @param  string $title Original placeholder text
		 *
		 * @return string        Modified placeholder text
		 */
		public function title( $title ) {
			$screen = get_current_screen();
			if ( isset( $screen->post_type ) && $screen->post_type == $this->post_type ) {
				return sprintf( __( '%s Title', 'cpt-core' ), $this->singular );
			}

			return $title;
		}

		/**
		 * Provides access to protected class properties.
		 * @since  0.2.0
		 *
		 * @param  string $key Specific CPT parameter to return
		 *
		 * @return mixed       Specific CPT parameter or array of singular, plural and registered name
		 */
		public function post_type( $key = 'post_type' ) {
			return isset( $this->$key ) ? $this->$key : array(
				'singular'  => $this->singular,
				'plural'    => $this->plural,
				'post_type' => $this->post_type,
			);
		}

		/**
		 * Provides access to all CPT_Core taxonomy objects registered via this class.
		 * @since  0.1.0
		 *
		 * @param  string $post_type Specific CPT_Core object to return, or 'true' to specify only names.
		 *
		 * @return mixed             Specific CPT_Core object or array of all
		 */
		public static function post_types( $post_type = '' ) {
			if ( true === $post_type && ! empty( self::$custom_post_types ) ) {
				return array_keys( self::$custom_post_types );
			}

			return isset( self::$custom_post_types[ $post_type ] ) ? self::$custom_post_types[ $post_type ] : self::$custom_post_types;
		}

		/**
		 * Magic method that echos the CPT registered name when treated like a string
		 * @since  0.2.0
		 * @return string CPT registered name
		 */
		public function __toString() {
			return $this->post_type();
		}

		/**
		 * Load this library's text domain
		 * @since  0.2.1
		 */
		public function l10n() {
			// Only do this one time
			if ( self::$l10n_done ) {
				return;
			}
			$locale = apply_filters( 'plugin_locale', get_locale(), 'cpt-core' );
			$mofile = dirname( __FILE__ ) . '/languages/cpt-core-' . $locale . '.mo';
			load_textdomain( 'cpt-core', $mofile );
		}

	} // end class


endif; // end class exist check
