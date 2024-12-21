<?php
/**
 * ArrayPress - Google Places API Tester
 *
 * @package     ArrayPress\Google\Places\Tester
 * @author      David Sherlock
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @link        https://arraypress.com/
 * @since       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:         ArrayPress - Google Places API Tester
 * Plugin URI:          https://github.com/arraypress/google-places-tester
 * Description:         A comprehensive testing plugin for the Google Places API integration.
 * Version:             1.0.0
 * Requires at least:   6.7.1
 * Requires PHP:        7.4
 * Author:              David Sherlock
 * Author URI:          https://arraypress.com/
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         arraypress-places-tester
 * Domain Path:         /languages
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Places\Tester;

use ArrayPress\Google\Places\Client;
use Exception;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Plugin {
	/**
	 * API Client instance
	 *
	 * @var Client|null
	 */
	private ?Client $client = null;

	/**
	 * Hook name for the admin page.
	 *
	 * @var string
	 */
	const MENU_HOOK = 'google_page_arraypress-google-places';

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Initialize client if API key exists
		$this->initialize_client();
	}

	/**
	 * Initialize API client
	 */
	private function initialize_client(): void {
		$api_key = get_option( 'google_places_api_key' );
		if ( ! empty( $api_key ) ) {
			$this->client = new Client(
				$api_key,
				(bool) get_option( 'google_places_enable_cache', true ),
				(int) get_option( 'google_places_cache_duration', DAY_IN_SECONDS )
			);
		}
	}

	/**
	 * Process results and render output
	 */
	private function process_form_submissions(): void {
		// Handle settings form
		if ( isset( $_POST['submit_api_key'] ) ) {
			check_admin_referer( 'places_api_key' );

			$api_key        = sanitize_text_field( $_POST['google_places_api_key'] );
			$enable_cache   = isset( $_POST['google_places_enable_cache'] );
			$cache_duration = (int) sanitize_text_field( $_POST['google_places_cache_duration'] );

			update_option( 'google_places_api_key', $api_key );
			update_option( 'google_places_enable_cache', $enable_cache );
			update_option( 'google_places_cache_duration', $cache_duration );

			$this->initialize_client();

			add_settings_error(
				'places_api_settings',
				'settings_updated',
				__( 'Settings saved successfully.', 'arraypress-places-tester' ),
				'success'
			);
		}

// Handle place search
		if ( isset( $_POST['submit_search'] ) ) {
			check_admin_referer( 'places_api_search' );

			$query = sanitize_text_field( $_POST['search_query'] );
			$type  = sanitize_text_field( $_POST['search_type'] );

			if ( empty( $query ) ) {
				add_settings_error(
					'places_api_search',
					'search_error',
					__( 'Please enter a search query.', 'arraypress-places-tester' ),
					'error'
				);

				return;
			}

			try {
				// Set any search parameters
				if ( ! empty( $type ) ) {
					$this->client->set_search_type( $type );
				}

				// Debug output
				error_log( 'Search Query: ' . $query );

				$results = $this->client->find_places( $query );

				// Debug the results
				error_log( 'Search Results: ' . print_r( $results, true ) );

				if ( is_wp_error( $results ) ) {
					add_settings_error(
						'places_api_search',
						'search_error',
						$results->get_error_message(),
						'error'
					);

					return;
				}

				$this->render_search_results( $results );
			} catch ( Exception $e ) {
				add_settings_error(
					'places_api_search',
					'search_error',
					$e->getMessage(),
					'error'
				);
			}
		}

		// Handle place details
// Handle place details
		if ( isset( $_POST['submit_details'] ) ) {
			check_admin_referer( 'places_api_details' );

			$place_id = sanitize_text_field( $_POST['place_id'] );

			if ( empty( $place_id ) ) {
				add_settings_error(
					'places_api_details',
					'details_error',
					__( 'Please enter a Place ID.', 'arraypress-places-tester' ),
					'error'
				);

				return;
			}

			try {
				// Debug output
				error_log( 'Place ID: ' . $place_id );

				$results = $this->client->get_place_details( $place_id );

				// Debug the results
				error_log( 'Place Details Results: ' . print_r( $results, true ) );

				if ( is_wp_error( $results ) ) {
					add_settings_error(
						'places_api_details',
						'details_error',
						$results->get_error_message(),
						'error'
					);

					return;
				}

				$this->render_place_details( $results );
			} catch ( Exception $e ) {
				add_settings_error(
					'places_api_details',
					'details_error',
					$e->getMessage(),
					'error'
				);
			}
		}

		// Handle nearby search
		if ( isset( $_POST['submit_nearby'] ) ) {
			check_admin_referer( 'places_api_nearby' );

			$lat    = (float) $_POST['lat'];
			$lng    = (float) $_POST['lng'];
			$radius = (int) $_POST['radius'];

			if ( empty( $lat ) || empty( $lng ) ) {
				add_settings_error(
					'places_api_nearby',
					'nearby_error',
					__( 'Please enter valid coordinates.', 'arraypress-places-tester' ),
					'error'
				);

				return;
			}

			try {
				$results = $this->client->nearby_search( $lat, $lng, $radius );
				$this->render_nearby_results( $results );
			} catch ( Exception $e ) {
				add_settings_error(
					'places_api_nearby',
					'nearby_error',
					$e->getMessage(),
					'error'
				);
			}
		}

		// Handle autocomplete
		if ( isset( $_POST['submit_autocomplete'] ) ) {
			check_admin_referer( 'places_api_autocomplete' );

			$input = sanitize_text_field( $_POST['input'] );
			$types = isset( $_POST['types'] ) ? (array) $_POST['types'] : [];

			if ( empty( $input ) ) {
				add_settings_error(
					'places_api_autocomplete',
					'autocomplete_error',
					__( 'Please enter text to search.', 'arraypress-places-tester' ),
					'error'
				);

				return;
			}

			try {
				if ( ! empty( $types ) ) {
					$this->client->set_autocomplete_types( $types );
				}
				$results = $this->client->get_autocomplete_predictions( $input );
				$this->render_autocomplete_results( $results );
			} catch ( Exception $e ) {
				add_settings_error(
					'places_api_autocomplete',
					'autocomplete_error',
					$e->getMessage(),
					'error'
				);
			}
		}

		// Handle geocoding
		if ( isset( $_POST['submit_geocode'] ) ) {
			check_admin_referer( 'places_api_geocode' );

			$address = sanitize_text_field( $_POST['address'] );

			if ( empty( $address ) ) {
				add_settings_error(
					'places_api_geocode',
					'geocode_error',
					__( 'Please enter an address.', 'arraypress-places-tester' ),
					'error'
				);

				return;
			}

			try {
				$results = $this->client->geocode( $address );
				$this->render_geocode_results( $results );
			} catch ( Exception $e ) {
				add_settings_error(
					'places_api_geocode',
					'geocode_error',
					$e->getMessage(),
					'error'
				);
			}
		}

		// Handle cache clearing
		if ( isset( $_POST['clear_cache'] ) ) {
			check_admin_referer( 'places_api_cache' );

			$this->client->clear_cache();
			add_settings_error(
				'places_api_cache',
				'cache_cleared',
				__( 'Cache cleared successfully.', 'arraypress-places-tester' ),
				'success'
			);
		}
	}

	/**
	 * Render error message
	 */
	private function render_error( $error ): void {
		?>
        <div class="notice notice-error">
            <p><?php echo esc_html( $error->get_error_message() ); ?></p>
        </div>
		<?php
	}

	/**
	 * Render search results
	 */
	private function render_search_results( $response ): void {
		if ( is_wp_error( $response ) ) {
			$this->render_error( $response );

			return;
		}

		$data    = $response->get_all();
		$results = $data['results'] ?? [];

		if ( empty( $results ) ) {
			echo '<p class="no-results">' . esc_html__( 'No results found.', 'arraypress-places-tester' ) . '</p>';

			return;
		}
		?>
        <div class="places-results">
			<?php foreach ( $results as $place ): ?>
                <div class="place-item">
                    <h4><?php echo esc_html( $place['name'] ?? '' ); ?></h4>

					<?php if ( ! empty( $place['formatted_address'] ) ): ?>
                        <p><?php echo esc_html( $place['formatted_address'] ); ?></p>
					<?php endif; ?>

                    <!-- Rating & Reviews -->
					<?php if ( ! empty( $place['rating'] ) ): ?>
                        <div class="place-meta">
                        <span class="place-rating">
                            <?php printf(
	                            esc_html__( '%s ★ (%d reviews)', 'arraypress-places-tester' ),
	                            number_format_i18n( $place['rating'], 1 ),
	                            $place['user_ratings_total'] ?? 0
                            ); ?>
                        </span>
                        </div>
					<?php endif; ?>

                    <!-- Place Types -->
					<?php if ( ! empty( $place['types'] ) ): ?>
                        <div class="place-types">
							<?php foreach ( $place['types'] as $type ): ?>
                                <span class="type-tag">
                                <?php echo esc_html( str_replace( '_', ' ', $type ) ); ?>
                            </span>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>

                    <!-- View Details Button -->
                    <form method="post" class="inline-form">
						<?php wp_nonce_field( 'places_api_details' ); ?>
                        <input type="hidden" name="place_id" value="<?php echo esc_attr( $place['place_id'] ); ?>">
                        <button type="submit" name="submit_details" class="button button-secondary">
							<?php esc_html_e( 'View Details', 'arraypress-places-tester' ); ?>
                        </button>
                    </form>
                </div>
			<?php endforeach; ?>
        </div>
		<?php
	}

	/**
	 * Render place details
	 */
	private function render_place_details( $response ): void {
		if ( is_wp_error( $response ) ) {
			$this->render_error( $response );

			return;
		}

		$data  = $response->get_all();
		$place = $data['result'] ?? null;

		if ( empty( $place ) ) {
			echo '<p class="no-results">' . esc_html__( 'No details found.', 'arraypress-places-tester' ) . '</p>';

			return;
		}
		?>
        <div class="place-details">
            <!-- Basic Information -->
            <div class="detail-section">
                <h4><?php echo esc_html( $place['name'] ?? '' ); ?></h4>
				<?php if ( ! empty( $place['formatted_address'] ) ): ?>
                    <p><?php echo esc_html( $place['formatted_address'] ); ?></p>
				<?php endif; ?>

                <!-- Rating & Price Level -->
                <div class="place-meta">
					<?php if ( ! empty( $place['rating'] ) ): ?>
                        <span class="place-rating">
                        <?php printf(
	                        esc_html__( '%s ★ (%d reviews)', 'arraypress-places-tester' ),
	                        number_format_i18n( $place['rating'], 1 ),
	                        $place['user_ratings_total'] ?? 0
                        ); ?>
                    </span>
					<?php endif; ?>

					<?php if ( isset( $place['price_level'] ) ): ?>
                        <span class="price-level">
                        <?php echo str_repeat( '$', intval( $place['price_level'] ) ); ?>
                    </span>
					<?php endif; ?>
                </div>
            </div>

            <!-- Contact Information -->
			<?php if ( ! empty( $place['formatted_phone_number'] ) || ! empty( $place['website'] ) ): ?>
                <div class="detail-section">
                    <h5><?php esc_html_e( 'Contact Information', 'arraypress-places-tester' ); ?></h5>

					<?php if ( ! empty( $place['formatted_phone_number'] ) ): ?>
                        <p>
                            <strong><?php esc_html_e( 'Phone:', 'arraypress-places-tester' ); ?></strong>
							<?php echo esc_html( $place['formatted_phone_number'] ); ?>
                        </p>
					<?php endif; ?>

					<?php if ( ! empty( $place['website'] ) ): ?>
                        <p>
                            <strong><?php esc_html_e( 'Website:', 'arraypress-places-tester' ); ?></strong>
                            <a href="<?php echo esc_url( $place['website'] ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $place['website'] ); ?>
                            </a>
                        </p>
					<?php endif; ?>
                </div>
			<?php endif; ?>

            <!-- Address Components -->
			<?php if ( ! empty( $place['address_components'] ) ): ?>
                <div class="detail-section">
                    <h5><?php esc_html_e( 'Address Components', 'arraypress-places-tester' ); ?></h5>
                    <dl class="address-components">
						<?php foreach ( $place['address_components'] as $component ): ?>
                            <dt>
								<?php echo esc_html( implode( ', ', array_map( function ( $type ) {
									return str_replace( '_', ' ', $type );
								}, $component['types'] ) ) ); ?>
                            </dt>
                            <dd><?php echo esc_html( $component['long_name'] ); ?></dd>
						<?php endforeach; ?>
                    </dl>
                </div>
			<?php endif; ?>

            <!-- Opening Hours -->
			<?php if ( ! empty( $place['opening_hours'] ) ): ?>
                <div class="detail-section">
                    <h5><?php esc_html_e( 'Opening Hours', 'arraypress-places-tester' ); ?></h5>

					<?php if ( ! empty( $place['opening_hours']['weekday_text'] ) ): ?>
                        <div class="opening-hours">
							<?php foreach ( $place['opening_hours']['weekday_text'] as $hours ): ?>
                                <p><?php echo esc_html( $hours ); ?></p>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>

                    <p class="open-status <?php echo ! empty( $place['opening_hours']['open_now'] ) ? 'open' : 'closed'; ?>">
						<?php echo ! empty( $place['opening_hours']['open_now'] )
							? esc_html__( 'Currently Open', 'arraypress-places-tester' )
							: esc_html__( 'Currently Closed', 'arraypress-places-tester' );
						?>
                    </p>
                </div>
			<?php endif; ?>

            <!-- Photos -->
			<?php if ( ! empty( $place['photos'] ) ): ?>
                <div class="detail-section">
                    <h5><?php esc_html_e( 'Photos', 'arraypress-places-tester' ); ?></h5>
                    <div class="photo-grid">
						<?php foreach ( array_slice( $place['photos'], 0, 6 ) as $photo ): ?>
                            <div class="photo-preview">
                                <img src="<?php echo esc_url( sprintf(
									'https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=%s&key=%s',
									$photo['photo_reference'],
									get_option( 'google_places_api_key' )
								) ); ?>" alt="<?php echo esc_attr( $place['name'] ?? '' ); ?>">
                            </div>
						<?php endforeach; ?>
                    </div>
                </div>
			<?php endif; ?>

            <!-- Reviews -->
			<?php if ( ! empty( $place['reviews'] ) ): ?>
                <div class="detail-section">
                    <h5><?php esc_html_e( 'Recent Reviews', 'arraypress-places-tester' ); ?></h5>
                    <div class="reviews-container">
						<?php foreach ( $place['reviews'] as $review ): ?>
                            <div class="review-item">
                                <div class="review-header">
                                <span class="reviewer">
                                    <?php echo esc_html( $review['author_name'] ); ?>
                                </span>
                                    <span class="review-rating">
                                    <?php printf(
	                                    esc_html__( '%s ★', 'arraypress-places-tester' ),
	                                    number_format_i18n( $review['rating'], 1 )
                                    ); ?>
                                </span>
                                    <span class="review-time">
                                    <?php echo esc_html( date_i18n(
	                                    get_option( 'date_format' ),
	                                    $review['time']
                                    ) ); ?>
                                </span>
                                </div>
                                <div class="review-text">
									<?php echo wp_kses_post( $review['text'] ); ?>
                                </div>
                            </div>
						<?php endforeach; ?>
                    </div>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Render nearby search results
	 */
	private function render_nearby_results( $response ): void {
		if ( is_wp_error( $response ) ) {
			$this->render_error( $response );

			return;
		}

		$results = $response->get_results();
		if ( empty( $results ) ) {
			echo '<p class="no-results">' . esc_html__( 'No nearby places found.', 'arraypress-places-tester' ) . '</p>';

			return;
		}
		?>
        <div class="places-results">
			<?php foreach ( $results as $place ): ?>
                <div class="place-item">
                    <h4><?php echo esc_html( $place['name'] ?? '' ); ?></h4>

					<?php if ( ! empty( $place['vicinity'] ) ): ?>
                        <p><?php echo esc_html( $place['vicinity'] ); ?></p>
					<?php endif; ?>

                    <div class="place-meta">
						<?php if ( ! empty( $place['rating'] ) ): ?>
                            <span class="place-rating">
                            <?php
                            printf(
	                            esc_html__( '%s ★ (%d reviews)', 'arraypress-places-tester' ),
	                            number_format_i18n( $place['rating'], 1 ),
	                            $place['user_ratings_total'] ?? 0
                            );
                            ?>
                        </span>
						<?php endif; ?>

						<?php if ( isset( $place['price_level'] ) ): ?>
                            <span class="price-level">
                            <?php echo str_repeat( '$', intval( $place['price_level'] ) ); ?>
                        </span>
						<?php endif; ?>
                    </div>

					<?php if ( ! empty( $place['types'] ) ): ?>
                        <div class="place-types">
							<?php foreach ( $place['types'] as $type ): ?>
                                <span class="type-tag">
                                <?php echo esc_html( str_replace( '_', ' ', $type ) ); ?>
                            </span>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>

                    <form method="post" class="inline-form">
						<?php wp_nonce_field( 'places_api_details' ); ?>
                        <input type="hidden" name="place_id" value="<?php echo esc_attr( $place['place_id'] ); ?>">
                        <button type="submit" name="submit_details" class="button button-secondary">
							<?php esc_html_e( 'View Details', 'arraypress-places-tester' ); ?>
                        </button>
                    </form>
                </div>
			<?php endforeach; ?>
        </div>
		<?php
	}

	/**
	 * Render autocomplete results
	 */
	private function render_autocomplete_results( $response ): void {
		if ( is_wp_error( $response ) ) {
			$this->render_error( $response );

			return;
		}

		$predictions = $response->get_predictions();
		if ( empty( $predictions ) ) {
			echo '<p class="no-results">' . esc_html__( 'No predictions found.', 'arraypress-places-tester' ) . '</p>';

			return;
		}
		?>
        <div class="places-results">
			<?php foreach ( $predictions as $prediction ): ?>
                <div class="prediction-item">
                    <h4><?php echo esc_html( $prediction['description'] ); ?></h4>

					<?php if ( ! empty( $prediction['structured_formatting'] ) ): ?>
                        <p class="secondary-text">
							<?php echo esc_html( $prediction['structured_formatting']['secondary_text'] ?? '' ); ?>
                        </p>
					<?php endif; ?>

					<?php if ( ! empty( $prediction['types'] ) ): ?>
                        <div class="prediction-types">
							<?php foreach ( $prediction['types'] as $type ): ?>
                                <span class="type-tag">
                                <?php echo esc_html( str_replace( '_', ' ', $type ) ); ?>
                            </span>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>

                    <form method="post" class="inline-form">
						<?php wp_nonce_field( 'places_api_details' ); ?>
                        <input type="hidden" name="place_id" value="<?php echo esc_attr( $prediction['place_id'] ); ?>">
                        <button type="submit" name="submit_details" class="button button-secondary">
							<?php esc_html_e( 'View Details', 'arraypress-places-tester' ); ?>
                        </button>
                    </form>
                </div>
			<?php endforeach; ?>
        </div>
		<?php
	}

	/**
	 * Render geocode results
	 */
	private function render_geocode_results( $response ): void {
		if ( is_wp_error( $response ) ) {
			$this->render_error( $response );

			return;
		}

		$result = $response->get_first_result();
		if ( empty( $result ) ) {
			echo '<p class="no-results">' . esc_html__( 'No results found.', 'arraypress-places-tester' ) . '</p>';

			return;
		}

		$location = $response->get_coordinates();
		?>
        <div class="geocode-result">
            <h4><?php echo esc_html( $response->get_formatted_address() ); ?></h4>

            <div class="coordinates-section">
                <h5><?php esc_html_e( 'Coordinates', 'arraypress-places-tester' ); ?></h5>
                <p>
                    <strong><?php esc_html_e( 'Latitude:', 'arraypress-places-tester' ); ?></strong>
					<?php echo esc_html( $location['latitude'] ); ?>
                    <br>
                    <strong><?php esc_html_e( 'Longitude:', 'arraypress-places-tester' ); ?></strong>
					<?php echo esc_html( $location['longitude'] ); ?>
                </p>
            </div>

			<?php
			$address = $response->get_structured_address();
			if ( ! empty( $address ) ):
				?>
                <div class="address-section">
                    <h5><?php esc_html_e( 'Address Components', 'arraypress-places-tester' ); ?></h5>
                    <dl class="address-components">
						<?php foreach ( $address as $key => $value ):
							if ( ! empty( $value ) ):
								?>
                                <dt><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></dt>
                                <dd><?php echo esc_html( $value ); ?></dd>
							<?php
							endif;
						endforeach;
						?>
                    </dl>
                </div>
			<?php endif; ?>

			<?php
			// Display place type information if available
			$types = $response->get_types();
			if ( ! empty( $types ) ):
				?>
                <div class="types-section">
                    <h5><?php esc_html_e( 'Place Types', 'arraypress-places-tester' ); ?></h5>
                    <div class="type-tags">
						<?php foreach ( $types as $type ): ?>
                            <span class="type-tag">
                            <?php echo esc_html( str_replace( '_', ' ', $type ) ); ?>
                        </span>
						<?php endforeach; ?>
                    </div>
                </div>
			<?php endif; ?>

			<?php
			// If this is a business location, show additional details
			if ( $response->get_business_status() ): ?>
                <div class="business-section">
                    <h5><?php esc_html_e( 'Business Information', 'arraypress-places-tester' ); ?></h5>
                    <p>
                        <strong><?php esc_html_e( 'Status:', 'arraypress-places-tester' ); ?></strong>
						<?php echo esc_html( $response->get_formatted_business_status() ); ?>
                    </p>
					<?php if ( $response->get_formatted_price_level() ): ?>
                        <p>
                            <strong><?php esc_html_e( 'Price Level:', 'arraypress-places-tester' ); ?></strong>
							<?php echo esc_html( $response->get_formatted_price_level() ); ?>
                        </p>
					<?php endif; ?>
                </div>
			<?php endif; ?>

			<?php
			// Display viewport information if available
			$viewport = $response->get_viewport();
			if ( ! empty( $viewport ) ):
				?>
                <div class="viewport-section">
                    <h5><?php esc_html_e( 'Viewport Bounds', 'arraypress-places-tester' ); ?></h5>
                    <div class="viewport-bounds">
                        <div class="northeast">
                            <strong><?php esc_html_e( 'Northeast:', 'arraypress-places-tester' ); ?></strong>
                            <p>
								<?php
								printf(
									esc_html__( 'Lat: %1$s, Lng: %2$s', 'arraypress-places-tester' ),
									esc_html( $viewport['northeast']['lat'] ),
									esc_html( $viewport['northeast']['lng'] )
								);
								?>
                            </p>
                        </div>
                        <div class="southwest">
                            <strong><?php esc_html_e( 'Southwest:', 'arraypress-places-tester' ); ?></strong>
                            <p>
								<?php
								printf(
									esc_html__( 'Lat: %1$s, Lng: %2$s', 'arraypress-places-tester' ),
									esc_html( $viewport['southwest']['lat'] ),
									esc_html( $viewport['southwest']['lng'] )
								);
								?>
                            </p>
                        </div>
                    </div>
                </div>
			<?php endif; ?>

			<?php
			// If plus code is available, display it
			$plus_code = $response->get_plus_code();
			if ( ! empty( $plus_code ) ):
				?>
                <div class="plus-code-section">
                    <h5><?php esc_html_e( 'Plus Code', 'arraypress-places-tester' ); ?></h5>
                    <p><?php echo esc_html( $plus_code ); ?></p>
                </div>
			<?php endif; ?>

			<?php
			// Display place URL if available
			$url = $response->get_place_url();
			if ( ! empty( $url ) ):
				?>
                <div class="map-link-section">
                    <a href="<?php echo esc_url( $url ); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="button button-secondary">
						<?php esc_html_e( 'View on Google Maps', 'arraypress-places-tester' ); ?>
                    </a>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'arraypress-places-tester',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add admin menu page
	 */
	public function add_menu_page(): void {
		global $admin_page_hooks;

		if ( ! isset( $admin_page_hooks['arraypress-google'] ) ) {
			add_menu_page(
				__( 'Google', 'arraypress-places-tester' ),
				__( 'Google', 'arraypress-places-tester' ),
				'manage_options',
				'arraypress-google',
				null,
				'dashicons-google',
				30
			);
		}

		add_submenu_page(
			'arraypress-google',
			__( 'Places API', 'arraypress-places-tester' ),
			__( 'Places API', 'arraypress-places-tester' ),
			'manage_options',
			'arraypress-google-places',
			[ $this, 'render_test_page' ]
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings(): void {
		register_setting( 'places_settings', 'google_places_api_key' );
		register_setting( 'places_settings', 'google_places_enable_cache', 'bool' );
		register_setting( 'places_settings', 'google_places_cache_duration', 'int' );
	}

	/**
	 * Render settings form
	 */
	private function render_settings_form(): void {
		?>
        <h2><?php _e( 'Settings', 'arraypress-places-tester' ); ?></h2>
        <form method="post" class="places-form">
			<?php wp_nonce_field( 'places_api_key' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_places_api_key"><?php _e( 'API Key', 'arraypress-places-tester' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="google_places_api_key"
                               id="google_places_api_key"
                               class="regular-text"
                               value="<?php echo esc_attr( get_option( 'google_places_api_key' ) ); ?>"
                               placeholder="<?php esc_attr_e( 'Enter your Google Places API key...', 'arraypress-places-tester' ); ?>">
                        <p class="description">
							<?php _e( 'Your Google Places API key. Required for making API requests.', 'arraypress-places-tester' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_places_enable_cache"><?php _e( 'Enable Cache', 'arraypress-places-tester' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="google_places_enable_cache"
                                   id="google_places_enable_cache"
                                   value="1" <?php checked( get_option( 'google_places_enable_cache', true ) ); ?>>
							<?php _e( 'Cache API responses', 'arraypress-places-tester' ); ?>
                        </label>
                        <p class="description">
							<?php _e( 'Caching results can help reduce API usage and improve performance.', 'arraypress-places-tester' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_places_cache_duration"><?php _e( 'Cache Duration', 'arraypress-places-tester' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="google_places_cache_duration"
                               id="google_places_cache_duration"
                               class="regular-text"
                               value="<?php echo esc_attr( get_option( 'google_places_cache_duration', DAY_IN_SECONDS ) ); ?>"
                               min="300" step="300">
                        <p class="description">
							<?php _e( 'How long to cache results in seconds. Default is 86400 (24 hours).', 'arraypress-places-tester' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
			<?php submit_button(
				empty( get_option( 'google_places_api_key' ) )
					? __( 'Save Settings', 'arraypress-places-tester' )
					: __( 'Update Settings', 'arraypress-places-tester' ),
				'primary',
				'submit_api_key'
			); ?>
        </form>
		<?php
	}

	/**
	 * Render the main test page
	 */
	public function render_test_page(): void {
		// Process any form submissions
		$this->process_form_submissions();
		?>
        <div class="wrap places-api-test">
            <h1><?php _e( 'Google Places API Test', 'arraypress-places-tester' ); ?></h1>

			<?php settings_errors( 'places_api_settings' ); ?>

			<?php if ( empty( get_option( 'google_places_api_key' ) ) ): ?>
                <div class="notice notice-warning">
                    <p><?php _e( 'Please enter your Google Places API key to begin testing.', 'arraypress-places-tester' ); ?></p>
                </div>
				<?php $this->render_settings_form(); ?>
			<?php else: ?>
				<?php $this->render_test_forms(); ?>

                <div class="places-test-section">
                    <h3><?php _e( 'Settings', 'arraypress-places-tester' ); ?></h3>
					<?php $this->render_settings_form(); ?>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}


	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ): void {
		if ( $hook !== self::MENU_HOOK ) {
			return;
		}

		wp_enqueue_style(
			'google-places-test-admin',
			plugins_url( 'assets/css/admin.css', __FILE__ ),
			[],
			'1.0.0'
		);

//		wp_enqueue_script(
//			'google-places-test-admin',
//			plugins_url( 'assets/js/admin.js', __FILE__ ),
//			[ 'jquery' ],
//			'1.0.0',
//			true
//		);
//
//		wp_localize_script( 'google-places-test-admin', 'placesApiTester', [
//			'ajaxurl' => admin_url( 'admin-ajax.php' ),
//			'nonce'   => wp_create_nonce( 'places_api_tester' ),
//		] );
	}

	/**
	 * Handle Places Search AJAX request
	 */
	public function handle_places_search(): void {
		check_ajax_referer( 'places_api_tester', 'nonce' );

		if ( ! $this->client ) {
			wp_send_json_error( [ 'message' => 'API client not initialized' ] );
		}

		$query = sanitize_text_field( $_POST['query'] ?? '' );
		if ( empty( $query ) ) {
			wp_send_json_error( [ 'message' => 'Query is required' ] );
		}

		$results = $this->client->find_places( $query );
		wp_send_json_success( $results );
	}

	/**
	 * Handle Place Details AJAX request
	 */
	public function handle_places_details(): void {
		check_ajax_referer( 'places_api_tester', 'nonce' );

		if ( ! $this->client ) {
			wp_send_json_error( [ 'message' => 'API client not initialized' ] );
		}

		$place_id = sanitize_text_field( $_POST['place_id'] ?? '' );
		if ( empty( $place_id ) ) {
			wp_send_json_error( [ 'message' => 'Place ID is required' ] );
		}

		$fields  = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', $_POST['fields'] ) : [];
		$results = $this->client->get_place_details( $place_id, $fields );
		wp_send_json_success( $results );
	}

	/**
	 * Handle Nearby Search AJAX request
	 */
	public function handle_places_nearby(): void {
		check_ajax_referer( 'places_api_tester', 'nonce' );

		if ( ! $this->client ) {
			wp_send_json_error( [ 'message' => 'API client not initialized' ] );
		}

		$lat    = (float) ( $_POST['lat'] ?? 0 );
		$lng    = (float) ( $_POST['lng'] ?? 0 );
		$radius = (int) ( $_POST['radius'] ?? 1000 );

		if ( ! $lat || ! $lng ) {
			wp_send_json_error( [ 'message' => 'Location coordinates required' ] );
		}

		$results = $this->client->nearby_search( $lat, $lng, $radius );
		wp_send_json_success( $results );
	}

	/**
	 * Handle Autocomplete AJAX request
	 */
	public function handle_places_autocomplete(): void {
		check_ajax_referer( 'places_api_tester', 'nonce' );

		if ( ! $this->client ) {
			wp_send_json_error( [ 'message' => 'API client not initialized' ] );
		}

		$input = sanitize_text_field( $_POST['input'] ?? '' );
		if ( empty( $input ) ) {
			wp_send_json_error( [ 'message' => 'Input is required' ] );
		}

		$results = $this->client->get_autocomplete_predictions( $input );
		wp_send_json_success( $results );
	}

	/**
	 * Handle Geocoding AJAX request
	 */
	public function handle_places_geocode(): void {
		check_ajax_referer( 'places_api_tester', 'nonce' );

		if ( ! $this->client ) {
			wp_send_json_error( [ 'message' => 'API client not initialized' ] );
		}

		$address = sanitize_text_field( $_POST['address'] ?? '' );
		if ( empty( $address ) ) {
			wp_send_json_error( [ 'message' => 'Address is required' ] );
		}

		$results = $this->client->geocode( $address );
		wp_send_json_success( $results );
	}

	/**
	 * Render test forms
	 */
	private function render_test_forms(): void {
		?>
        <div class="places-test-container">
            <!-- Place Search Form -->
            <div class="places-test-section">
                <h3><?php _e( 'Place Search', 'arraypress-places-tester' ); ?></h3>
                <form method="post" class="places-form">
					<?php wp_nonce_field( 'places_api_search' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="search-query"><?php _e( 'Search Query', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="search-query"
                                       name="search_query"
                                       class="regular-text"
                                       value="<?php echo isset( $_POST['search_query'] ) ? esc_attr( $_POST['search_query'] ) : ''; ?>"
                                       placeholder="<?php esc_attr_e( 'e.g., restaurants in Seattle', 'arraypress-places-tester' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="search-type"><?php _e( 'Place Type', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <select id="search-type" name="search_type">
                                    <option value=""><?php _e( 'Any', 'arraypress-places-tester' ); ?></option>
                                    <option value="restaurant" <?php selected( isset( $_POST['search_type'] ) && $_POST['search_type'] === 'restaurant' ); ?>>
                                        Restaurant
                                    </option>
                                    <option value="cafe" <?php selected( isset( $_POST['search_type'] ) && $_POST['search_type'] === 'cafe' ); ?>>
                                        Cafe
                                    </option>
                                    <option value="store" <?php selected( isset( $_POST['search_type'] ) && $_POST['search_type'] === 'store' ); ?>>
                                        Store
                                    </option>
                                    <option value="hotel" <?php selected( isset( $_POST['search_type'] ) && $_POST['search_type'] === 'hotel' ); ?>>
                                        Hotel
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="submit_search" class="button button-primary">
						<?php _e( 'Search Places', 'arraypress-places-tester' ); ?>
                    </button>
                </form>
            </div>

            <!-- Place Details Form -->
            <div class="places-test-section">
                <h3><?php _e( 'Place Details', 'arraypress-places-tester' ); ?></h3>
                <form method="post" class="places-form">
					<?php wp_nonce_field( 'places_api_details' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="place-id"><?php _e( 'Place ID', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="place-id"
                                       name="place_id"
                                       class="regular-text"
                                       value="<?php echo isset( $_POST['place_id'] ) ? esc_attr( $_POST['place_id'] ) : ''; ?>"
                                       placeholder="<?php esc_attr_e( 'Enter Place ID', 'arraypress-places-tester' ); ?>">
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="submit_details" class="button button-primary">
						<?php _e( 'Get Details', 'arraypress-places-tester' ); ?>
                    </button>
                </form>
            </div>

            <!-- Nearby Search Form -->
            <div class="places-test-section">
                <h3><?php _e( 'Nearby Search', 'arraypress-places-tester' ); ?></h3>
                <form method="post" class="places-form">
					<?php wp_nonce_field( 'places_api_nearby' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="nearby-lat"><?php _e( 'Latitude', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="number"
                                       id="nearby-lat"
                                       name="lat"
                                       class="regular-text"
                                       step="any"
                                       value="<?php echo isset( $_POST['lat'] ) ? esc_attr( $_POST['lat'] ) : '47.6062'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nearby-lng"><?php _e( 'Longitude', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="number"
                                       id="nearby-lng"
                                       name="lng"
                                       class="regular-text"
                                       step="any"
                                       value="<?php echo isset( $_POST['lng'] ) ? esc_attr( $_POST['lng'] ) : '-122.3321'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nearby-radius"><?php _e( 'Radius (meters)', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="number"
                                       id="nearby-radius"
                                       name="radius"
                                       class="regular-text"
                                       min="1"
                                       max="50000"
                                       value="<?php echo isset( $_POST['radius'] ) ? esc_attr( $_POST['radius'] ) : '1000'; ?>">
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="submit_nearby" class="button button-primary">
						<?php _e( 'Search Nearby', 'arraypress-places-tester' ); ?>
                    </button>
                </form>
            </div>

            <!-- Autocomplete Form -->
            <div class="places-test-section">
                <h3><?php _e( 'Place Autocomplete', 'arraypress-places-tester' ); ?></h3>
                <form method="post" class="places-form">
					<?php wp_nonce_field( 'places_api_autocomplete' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="autocomplete-input"><?php _e( 'Input', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="autocomplete-input"
                                       name="input"
                                       class="regular-text"
                                       value="<?php echo isset( $_POST['input'] ) ? esc_attr( $_POST['input'] ) : ''; ?>"
                                       placeholder="<?php esc_attr_e( 'Start typing a place name...', 'arraypress-places-tester' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="autocomplete-types"><?php _e( 'Types', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <select id="autocomplete-types" name="types[]" multiple>
                                    <option value="establishment" <?php selected( isset( $_POST['types'] ) && in_array( 'establishment', $_POST['types'] ) ); ?>>
										<?php _e( 'Establishment', 'arraypress-places-tester' ); ?>
                                    </option>
                                    <option value="address" <?php selected( isset( $_POST['types'] ) && in_array( 'address', $_POST['types'] ) ); ?>>
										<?php _e( 'Address', 'arraypress-places-tester' ); ?>
                                    </option>
                                    <option value="geocode" <?php selected( isset( $_POST['types'] ) && in_array( 'geocode', $_POST['types'] ) ); ?>>
										<?php _e( 'Geocode', 'arraypress-places-tester' ); ?>
                                    </option>
                                    <option value="cities" <?php selected( isset( $_POST['types'] ) && in_array( 'cities', $_POST['types'] ) ); ?>>
										<?php _e( 'Cities', 'arraypress-places-tester' ); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="submit_autocomplete" class="button button-primary">
						<?php _e( 'Get Predictions', 'arraypress-places-tester' ); ?>
                    </button>
                </form>
            </div>

            <!-- Geocoding Form -->
            <div class="places-test-section">
                <h3><?php _e( 'Geocoding', 'arraypress-places-tester' ); ?></h3>
                <form method="post" class="places-form">
					<?php wp_nonce_field( 'places_api_geocode' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="geocode-address"><?php _e( 'Address', 'arraypress-places-tester' ); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="geocode-address"
                                       name="address"
                                       class="regular-text"
                                       value="<?php echo isset( $_POST['address'] ) ? esc_attr( $_POST['address'] ) : ''; ?>"
                                       placeholder="<?php esc_attr_e( 'Enter an address...', 'arraypress-places-tester' ); ?>">
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="submit_geocode" class="button button-primary">
						<?php _e( 'Geocode', 'arraypress-places-tester' ); ?>
                    </button>
                </form>
            </div>

            <!-- Cache Management -->
            <div class="places-test-section">
                <h3><?php _e( 'Cache Management', 'arraypress-places-tester' ); ?></h3>
                <form method="post" class="places-form">
					<?php wp_nonce_field( 'places_api_cache' ); ?>
                    <p class="description">
						<?php _e( 'Clear the cached Places API responses to force new API requests.', 'arraypress-places-tester' ); ?>
                    </p>
                    <button type="submit" name="clear_cache" class="button button-secondary">
						<?php _e( 'Clear Cache', 'arraypress-places-tester' ); ?>
                    </button>
                </form>
            </div>
        </div>
		<?php
	}

}

new Plugin();