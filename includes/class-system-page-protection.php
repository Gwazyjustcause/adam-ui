<?php
/**
 * Shared access control for ADAM-owned WordPress pages.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Protects registered ADAM system pages consistently across plugins.
 */
final class ADAM_UI_System_Page_Protection {
	public const META_KEY = '_adam_system_page_protected';

	/** @var ADAM_UI_System_Page_Protection|null */
	private static $instance = null;

	/** @var array<string,callable> */
	private $providers = array();

	/** @return ADAM_UI_System_Page_Protection */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Registers all WordPress hooks owned by the service. */
	public function register_hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_block_request' ), -100 );
		add_action( 'send_headers', array( $this, 'send_robots_header' ) );
		add_action( 'pre_get_posts', array( $this, 'exclude_from_search' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_from_sitemaps' ), 10, 2 );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ) );
	}

	/**
	 * Registers a lazy page definition provider.
	 *
	 * Providers return rows containing an `id` and, optionally, an
	 * `allow_access` callback for signed or token-based journeys.
	 *
	 * @param string   $provider Provider identifier.
	 * @param callable $resolver Lazy definitions resolver.
	 * @return bool
	 */
	public function register_provider( $provider, $resolver ) {
		$provider = sanitize_key( $provider );
		if ( '' === $provider || ! is_callable( $resolver ) ) {
			return false;
		}

		$this->providers[ $provider ] = $resolver;
		return true;
	}

	/** Returns whether a registered system page is protected. */
	public function is_protected( $page_id ) {
		$page_id = absint( $page_id );
		return $page_id > 0 && isset( $this->pages()[ $page_id ] ) && '1' === (string) get_post_meta( $page_id, self::META_KEY, true );
	}

	/** Updates the protection flag for a registered system page. */
	public function set_protected( $page_id, $protected ) {
		$page_id = absint( $page_id );
		if ( 0 === $page_id || ! isset( $this->pages()[ $page_id ] ) ) {
			return false;
		}

		if ( $protected ) {
			return false !== update_post_meta( $page_id, self::META_KEY, '1' );
		}

		delete_post_meta( $page_id, self::META_KEY );
		return true;
	}

	/** Renders the friendly access-denied page when required. */
	public function maybe_block_request() {
		$page_id = $this->current_protected_page_id();
		if ( 0 === $page_id || current_user_can( 'manage_options' ) || $this->access_is_allowed( $page_id ) ) {
			return;
		}

		status_header( 403 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );

		get_header();
		require ADAM_UI_PATH . 'templates/protected-system-page.php';
		get_footer();
		exit;
	}

	/** Adds an HTTP-level indexing directive for protected page responses. */
	public function send_robots_header() {
		if ( $this->current_protected_page_id() > 0 ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
		}
	}

	/** Removes protected pages from normal front-end searches. */
	public function exclude_from_search( $query ) {
		if ( is_admin() || ! $query->is_search() ) {
			return;
		}

		$excluded = array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), $this->protected_page_ids() ) ) );
		$query->set( 'post__not_in', $excluded );
	}

	/** Removes protected pages from the WordPress page sitemap. */
	public function exclude_from_sitemaps( $args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $args;
		}

		$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $this->protected_page_ids() ) ) );
		return $args;
	}

	/** Adds noindex directives when a protected page reaches wp_head. */
	public function filter_robots( $robots ) {
		if ( $this->current_protected_page_id() > 0 ) {
			$robots['noindex']   = true;
			$robots['nofollow']  = true;
			$robots['noarchive'] = true;
		}
		return $robots;
	}

	/** @return int */
	private function current_protected_page_id() {
		if ( ! is_page() ) {
			return 0;
		}

		$page_id = absint( get_queried_object_id() );
		return $this->is_protected( $page_id ) ? $page_id : 0;
	}

	/** @return bool */
	private function access_is_allowed( $page_id ) {
		$page = $this->pages()[ $page_id ] ?? array();
		$allowed = isset( $page['allow_access'] ) && is_callable( $page['allow_access'] ) ? (bool) call_user_func( $page['allow_access'], $page_id ) : false;
		return (bool) apply_filters( 'adam_ui_system_page_allow_access', $allowed, $page_id, $page );
	}

	/** @return int[] */
	private function protected_page_ids() {
		return array_values( array_filter( array_keys( $this->pages() ), array( $this, 'is_protected' ) ) );
	}

	/** @return array<int,array<string,mixed>> */
	private function pages() {
		$pages = array();
		foreach ( $this->providers as $provider => $resolver ) {
			$definitions = call_user_func( $resolver );
			if ( ! is_array( $definitions ) ) {
				continue;
			}
			foreach ( $definitions as $definition ) {
				$page_id = absint( is_array( $definition ) ? ( $definition['id'] ?? 0 ) : $definition );
				if ( 0 === $page_id ) {
					continue;
				}
				$pages[ $page_id ]             = is_array( $definition ) ? $definition : array( 'id' => $page_id );
				$pages[ $page_id ]['id']       = $page_id;
				$pages[ $page_id ]['provider'] = $provider;
			}
		}
		return $pages;
	}

	private function __construct() {}
}
