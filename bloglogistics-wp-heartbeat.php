<?php
/**
 * Plugin Name:       BlogLogistics WP Heartbeat
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-wp-heartbeat
 * Description:       Adjusts or disables the WordPress Heartbeat API in the dashboard, post editor, and frontend.
 * Version:           2.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            BlogLogistics
 * Author URI:        https://www.bloglogistics.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/bloglogisticsdev/bloglogistics-wp-heartbeat
 * Text Domain:       bloglogistics-wp-heartbeat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOGLOGISTICS_WPH_VERSION', '2.0.0' );
define( 'BLOGLOGISTICS_WPH_SLUG', 'bloglogistics-wp-heartbeat' );
define( 'BLOGLOGISTICS_WPH_FILE', __FILE__ );
define( 'BLOGLOGISTICS_WPH_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_WPH_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-wp-heartbeat/' );
define( 'BLOGLOGISTICS_WPH_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-wp-heartbeat.json' );

$bloglogistics_wph_puc = BLOGLOGISTICS_WPH_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $bloglogistics_wph_puc ) ) {
	if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory', false ) ) {
		require_once $bloglogistics_wph_puc;
	}

	require_once BLOGLOGISTICS_WPH_DIR . 'includes/class-bloglogistics-wp-heartbeat-updater.php';

	if ( class_exists( 'BlogLogistics_WP_Heartbeat_Updater', false ) ) {
		BlogLogistics_WP_Heartbeat_Updater::init(
			array(
				'repo_url'    => BLOGLOGISTICS_WPH_UPDATE_MANIFEST_URL,
				'plugin_file' => BLOGLOGISTICS_WPH_FILE,
				'slug'        => BLOGLOGISTICS_WPH_SLUG,
			)
		);
	}
}

if ( ! class_exists( 'BlogLogistics_WP_Heartbeat', false ) ) {

	/**
	 * Manage WordPress Heartbeat interval settings.
	 */
	final class BlogLogistics_WP_Heartbeat {

		private const OPTION_NAME = 'bloglogistics_wph_options';
		private const CAPABILITY  = 'manage_options';
		private const MENU_SLUG   = 'bloglogistics-wp-heartbeat';

		/**
		 * Register hooks.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_post_bloglogistics_wph_save', array( $this, 'handle_save' ) );
			add_action( 'admin_post_bloglogistics_wph_restore_defaults', array( $this, 'handle_restore_defaults' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'maybe_disable_admin_heartbeat' ), 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_disable_frontend_heartbeat' ), 1 );
			add_filter( 'heartbeat_settings', array( $this, 'filter_heartbeat_settings' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( BLOGLOGISTICS_WPH_FILE ), array( $this, 'plugin_action_links' ) );
		}

		/**
		 * Plugin defaults.
		 *
		 * A value of 0 disables Heartbeat for that context.
		 *
		 * @return array<string, int>
		 */
		public static function defaults(): array {
			return array(
				'dashboard'   => 0,
				'post_editor' => 60,
				'frontend'    => 0,
			);
		}

		/**
		 * Suggested enabled intervals.
		 *
		 * @return array<string, int>
		 */
		private static function suggested_intervals(): array {
			return array(
				'dashboard'   => 15,
				'post_editor' => 60,
				'frontend'    => 15,
			);
		}

		/**
		 * Plugin activation.
		 */
		public static function activate(): void {
			$options = get_option( self::OPTION_NAME, array() );

			if ( ! is_array( $options ) ) {
				$options = array();
			}

			update_option( self::OPTION_NAME, self::normalise_options( $options ) );
		}

		/**
		 * Add the BlogLogistics admin menu and this plugin's settings page.
		 */
		public function add_admin_menu(): void {
			$this->register_bloglogistics_parent_menu();

			add_submenu_page(
				'bloglogistics',
				esc_html__( 'WP Heartbeat Settings', 'bloglogistics-wp-heartbeat' ),
				esc_html__( 'WP Heartbeat', 'bloglogistics-wp-heartbeat' ),
				self::CAPABILITY,
				self::MENU_SLUG,
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Register the shared BlogLogistics parent menu if another BlogLogistics plugin has not already done so.
		 */
		private function register_bloglogistics_parent_menu(): void {
			if ( $this->bloglogistics_parent_menu_exists() ) {
				return;
			}

			add_menu_page(
				esc_html__( 'BlogLogistics', 'bloglogistics-wp-heartbeat' ),
				esc_html__( 'BlogLogistics', 'bloglogistics-wp-heartbeat' ),
				self::CAPABILITY,
				'bloglogistics',
				array( $this, 'render_bloglogistics_parent_page' ),
				'dashicons-rss',
				58
			);
		}

		/**
		 * Check whether the shared BlogLogistics parent menu already exists.
		 */
		private function bloglogistics_parent_menu_exists(): bool {
			global $menu;

			if ( ! is_array( $menu ) ) {
				return false;
			}

			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'bloglogistics' === $item[2] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Shared parent menu page.
		 */
		public function render_bloglogistics_parent_page(): void {
			if ( ! current_user_can( self::CAPABILITY ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'BlogLogistics', 'bloglogistics-wp-heartbeat' ); ?></h1>
				<p><?php esc_html_e( 'Use the submenu links to configure installed BlogLogistics plugins.', 'bloglogistics-wp-heartbeat' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render settings page.
		 */
		public function render_settings_page(): void {
			if ( ! current_user_can( self::CAPABILITY ) ) {
				return;
			}

			$options      = $this->get_options();
			$defaults     = self::defaults();
			$is_default   = $options === $defaults;
			$message_code = isset( $_GET['bloglogistics_wph_message'] ) ? sanitize_key( wp_unslash( $_GET['bloglogistics_wph_message'] ) ) : '';
			?>
			<div class="wrap bloglogistics-wph-wrap">
				<h1><?php esc_html_e( 'WP Heartbeat Settings', 'bloglogistics-wp-heartbeat' ); ?></h1>

				<?php $this->render_message( $message_code ); ?>

				<p><?php esc_html_e( 'This plugin controls the WordPress Heartbeat API in three common areas: the Dashboard, the post editor, and the frontend.', 'bloglogistics-wp-heartbeat' ); ?></p>

				<div class="notice notice-warning inline">
					<p><?php esc_html_e( 'Disabling Heartbeat can reduce background admin-ajax.php requests, but it can also affect autosave, post locking, editor presence, and collaboration features. Keep the post editor enabled unless you have a clear reason to disable it.', 'bloglogistics-wp-heartbeat' ); ?></p>
				</div>

				<form id="bloglogistics-wph-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 1000px; margin-top: 20px;">
					<input type="hidden" name="action" value="bloglogistics_wph_save" />
					<?php wp_nonce_field( 'bloglogistics_wph_save', 'bloglogistics_wph_nonce' ); ?>

					<h2><?php esc_html_e( 'Heartbeat controls', 'bloglogistics-wp-heartbeat' ); ?></h2>
					<p><?php esc_html_e( 'Set an interval in seconds, or set the value to 0 to disable Heartbeat for that area. WordPress ignores very low Heartbeat values, so enabled intervals are enforced at a minimum of 15 seconds.', 'bloglogistics-wp-heartbeat' ); ?></p>

					<table class="form-table" role="presentation">
						<tbody>
							<?php foreach ( $this->get_context_labels() as $context => $label ) : ?>
								<?php
								$value         = (int) $options[ $context ];
								$is_disabled   = $value <= 0;
								$display_value = $is_disabled ? 0 : $value;
								$interval_id   = 'bloglogistics_wph_' . $context . '_interval';
								$disable_id    = 'bloglogistics_wph_' . $context . '_disable';
								?>
								<tr class="bloglogistics-wph-field" data-context="<?php echo esc_attr( $context ); ?>">
									<th scope="row"><label for="<?php echo esc_attr( $interval_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
									<td>
										<input
											type="number"
											id="<?php echo esc_attr( $interval_id ); ?>"
											class="bloglogistics-wph-interval"
											name="bloglogistics_wph_<?php echo esc_attr( $context ); ?>"
											value="<?php echo esc_attr( (string) $display_value ); ?>"
											min="0"
											step="1"
											data-original-value="<?php echo esc_attr( (string) $display_value ); ?>"
											style="width: 100px;"
											<?php readonly( $is_disabled ); ?>
										/>
										<label for="<?php echo esc_attr( $disable_id ); ?>" style="margin-left: 12px;">
											<input
												type="checkbox"
												id="<?php echo esc_attr( $disable_id ); ?>"
												class="bloglogistics-wph-disable"
												data-original-checked="<?php echo esc_attr( $is_disabled ? '1' : '0' ); ?>"
												<?php checked( $is_disabled ); ?>
											/>
											<?php esc_html_e( 'Disable', 'bloglogistics-wp-heartbeat' ); ?>
										</label>
										<p class="description"><?php echo esc_html( $this->get_context_description( $context ) ); ?></p>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="notice notice-info inline">
						<p><strong><?php esc_html_e( 'Recommended default:', 'bloglogistics-wp-heartbeat' ); ?></strong></p>
						<p><?php esc_html_e( 'Dashboard: Disabled. Post editor: 60 seconds. Frontend: Disabled.', 'bloglogistics-wp-heartbeat' ); ?></p>
					</div>

					<?php submit_button( esc_html__( 'Save Heartbeat Settings', 'bloglogistics-wp-heartbeat' ), 'primary', 'submit', true, array( 'id' => 'bloglogistics-wph-save-settings', 'disabled' => 'disabled' ) ); ?>
					<p id="bloglogistics-wph-status" class="description"><?php esc_html_e( 'No changes to save.', 'bloglogistics-wp-heartbeat' ); ?></p>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 10px;">
					<input type="hidden" name="action" value="bloglogistics_wph_restore_defaults" />
					<?php wp_nonce_field( 'bloglogistics_wph_restore_defaults', 'bloglogistics_wph_defaults_nonce' ); ?>
					<?php submit_button( esc_html__( 'Restore recommended defaults', 'bloglogistics-wp-heartbeat' ), 'secondary', 'submit', false, array_merge( array( 'id' => 'bloglogistics-wph-restore-defaults' ), $is_default ? array( 'disabled' => 'disabled' ) : array() ) ); ?>
					<p class="description"><?php esc_html_e( 'This disables Heartbeat on the Dashboard and frontend, and keeps the post editor Heartbeat at 60 seconds.', 'bloglogistics-wp-heartbeat' ); ?></p>
				</form>
			</div>

			<script>
				document.addEventListener('DOMContentLoaded', function () {
					var form = document.getElementById('bloglogistics-wph-settings-form');
					var saveButton = document.getElementById('bloglogistics-wph-save-settings');
					var statusText = document.getElementById('bloglogistics-wph-status');

					if (!form || !saveButton || !statusText) {
						return;
					}

					var suggested = {
						dashboard: 15,
						post_editor: 60,
						frontend: 15
					};

					function syncField(row) {
						var intervalInput = row.querySelector('.bloglogistics-wph-interval');
						var disableCheckbox = row.querySelector('.bloglogistics-wph-disable');
						var context = row.getAttribute('data-context');

						if (!intervalInput || !disableCheckbox || !context) {
							return;
						}

						if (disableCheckbox.checked) {
							intervalInput.value = 0;
							intervalInput.setAttribute('readonly', 'readonly');
						} else {
							intervalInput.removeAttribute('readonly');

							if (String(intervalInput.value).trim() === '' || Number(intervalInput.value) === 0) {
								intervalInput.value = suggested[context] || 15;
							}
						}
					}

					function hasChanges() {
						var changed = false;

						form.querySelectorAll('.bloglogistics-wph-field').forEach(function (row) {
							var intervalInput = row.querySelector('.bloglogistics-wph-interval');
							var disableCheckbox = row.querySelector('.bloglogistics-wph-disable');
							var originalValue = intervalInput ? intervalInput.getAttribute('data-original-value') : '';
							var originalChecked = disableCheckbox ? disableCheckbox.getAttribute('data-original-checked') : '0';
							var currentValue = intervalInput ? String(intervalInput.value) : '';
							var currentChecked = disableCheckbox && disableCheckbox.checked ? '1' : '0';

							if (currentValue !== originalValue || currentChecked !== originalChecked) {
								changed = true;
							}
						});

						return changed;
					}

					function refresh() {
						form.querySelectorAll('.bloglogistics-wph-field').forEach(syncField);

						if (hasChanges()) {
							saveButton.removeAttribute('disabled');
							statusText.textContent = '<?php echo esc_js( __( 'Unsaved changes.', 'bloglogistics-wp-heartbeat' ) ); ?>';
						} else {
							saveButton.setAttribute('disabled', 'disabled');
							statusText.textContent = '<?php echo esc_js( __( 'No changes to save.', 'bloglogistics-wp-heartbeat' ) ); ?>';
						}
					}

					form.querySelectorAll('input').forEach(function (input) {
						input.addEventListener('input', refresh);
						input.addEventListener('change', refresh);
					});

					refresh();
				});
			</script>
			<?php
		}

		/**
		 * Save settings.
		 */
		public function handle_save(): void {
			if ( ! current_user_can( self::CAPABILITY ) ) {
				wp_die( esc_html__( 'You do not have permission to manage these settings.', 'bloglogistics-wp-heartbeat' ) );
			}

			check_admin_referer( 'bloglogistics_wph_save', 'bloglogistics_wph_nonce' );

			$existing = $this->get_options();
			$options  = array();

			foreach ( array_keys( self::defaults() ) as $context ) {
				$field = 'bloglogistics_wph_' . $context;
				$value = isset( $_POST[ $field ] ) ? (int) wp_unslash( $_POST[ $field ] ) : (int) $existing[ $context ];

				$options[ $context ] = max( 0, $value );
			}

			$options = self::normalise_options( $options );

			if ( $options === $existing ) {
				$this->redirect_with_message( 'no_change' );
			}

			update_option( self::OPTION_NAME, $options );
			$this->redirect_with_message( 'saved' );
		}

		/**
		 * Restore defaults.
		 */
		public function handle_restore_defaults(): void {
			if ( ! current_user_can( self::CAPABILITY ) ) {
				wp_die( esc_html__( 'You do not have permission to manage these settings.', 'bloglogistics-wp-heartbeat' ) );
			}

			check_admin_referer( 'bloglogistics_wph_restore_defaults', 'bloglogistics_wph_defaults_nonce' );

			$defaults = self::defaults();

			if ( $this->get_options() === $defaults ) {
				$this->redirect_with_message( 'no_change' );
			}

			update_option( self::OPTION_NAME, $defaults );
			$this->redirect_with_message( 'defaults_restored' );
		}

		/**
		 * Disable Heartbeat for targeted admin screens.
		 */
		public function maybe_disable_admin_heartbeat( string $hook_suffix ): void {
			$context = $this->admin_context_from_hook( $hook_suffix );

			if ( '' === $context || (int) $this->get_options()[ $context ] > 0 ) {
				return;
			}

			wp_dequeue_script( 'heartbeat' );
			wp_deregister_script( 'heartbeat' );
		}

		/**
		 * Disable Heartbeat on the frontend.
		 */
		public function maybe_disable_frontend_heartbeat(): void {
			if ( (int) $this->get_options()['frontend'] > 0 ) {
				return;
			}

			wp_dequeue_script( 'heartbeat' );
			wp_deregister_script( 'heartbeat' );
		}

		/**
		 * Set enabled Heartbeat intervals.
		 *
		 * @param array<string, mixed> $settings Heartbeat settings.
		 * @return array<string, mixed>
		 */
		public function filter_heartbeat_settings( array $settings ): array {
			$context = $this->current_context();

			if ( '' === $context ) {
				return $settings;
			}

			$interval = (int) $this->get_options()[ $context ];

			if ( $interval <= 0 ) {
				return $settings;
			}

			$settings['interval'] = max( 15, $interval );

			return $settings;
		}

		/**
		 * Add settings link on the Plugins screen.
		 *
		 * @param array<int, string> $links Plugin action links.
		 * @return array<int, string>
		 */
		public function plugin_action_links( array $links ): array {
			$settings_url  = admin_url( 'admin.php?page=' . self::MENU_SLUG );
			$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'bloglogistics-wp-heartbeat' ) . '</a>';

			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Get saved options merged with defaults.
		 *
		 * @return array<string, int>
		 */
		private function get_options(): array {
			$options = get_option( self::OPTION_NAME, array() );

			if ( ! is_array( $options ) ) {
				$options = array();
			}

			return self::normalise_options( $options );
		}

		/**
		 * Normalise options.
		 *
		 * @param array<string, mixed> $options Raw options.
		 * @return array<string, int>
		 */
		private static function normalise_options( array $options ): array {
			$output = array();

			foreach ( self::defaults() as $context => $default ) {
				$value              = array_key_exists( $context, $options ) ? (int) $options[ $context ] : (int) $default;
				$output[ $context ] = max( 0, $value );
			}

			return $output;
		}

		/**
		 * Context labels.
		 *
		 * @return array<string, string>
		 */
		private function get_context_labels(): array {
			return array(
				'dashboard'   => __( 'Dashboard Heartbeat', 'bloglogistics-wp-heartbeat' ),
				'post_editor' => __( 'Post Editor Heartbeat', 'bloglogistics-wp-heartbeat' ),
				'frontend'    => __( 'Frontend Heartbeat', 'bloglogistics-wp-heartbeat' ),
			);
		}

		/**
		 * Context description.
		 */
		private function get_context_description( string $context ): string {
			switch ( $context ) {
				case 'dashboard':
					return __( 'Controls Heartbeat on the main WordPress Dashboard screen.', 'bloglogistics-wp-heartbeat' );
				case 'post_editor':
					return __( 'Controls Heartbeat in the post editor. Autosave and post locking rely on this, so 60 seconds is the recommended setting.', 'bloglogistics-wp-heartbeat' );
				case 'frontend':
					return __( 'Controls Heartbeat on public-facing pages when a theme or plugin enqueues it.', 'bloglogistics-wp-heartbeat' );
				default:
					return '';
			}
		}

		/**
		 * Current Heartbeat context.
		 */
		private function current_context(): string {
			if ( ! is_admin() ) {
				return 'frontend';
			}

			global $pagenow;

			if ( ! is_string( $pagenow ) ) {
				return '';
			}

			return $this->admin_context_from_hook( $pagenow );
		}

		/**
		 * Convert admin hook to managed context.
		 */
		private function admin_context_from_hook( string $hook ): string {
			if ( 'index.php' === $hook ) {
				return 'dashboard';
			}

			if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
				return 'post_editor';
			}

			return '';
		}

		/**
		 * Render a status message after redirect.
		 */
		private function render_message( string $message_code ): void {
			if ( '' === $message_code ) {
				return;
			}

			$messages = array(
				'saved'             => array( 'success', __( 'Heartbeat settings saved.', 'bloglogistics-wp-heartbeat' ) ),
				'defaults_restored' => array( 'success', __( 'Recommended defaults restored.', 'bloglogistics-wp-heartbeat' ) ),
				'no_change'         => array( 'info', __( 'No changes were made.', 'bloglogistics-wp-heartbeat' ) ),
			);

			if ( ! isset( $messages[ $message_code ] ) ) {
				return;
			}

			list( $type, $message ) = $messages[ $message_code ];
			?>
			<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}

		/**
		 * Redirect after handling a form action.
		 */
		private function redirect_with_message( string $message_code ): void {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'                      => self::MENU_SLUG,
						'bloglogistics_wph_message' => $message_code,
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}
}

register_activation_hook( __FILE__, array( 'BlogLogistics_WP_Heartbeat', 'activate' ) );

new BlogLogistics_WP_Heartbeat();
