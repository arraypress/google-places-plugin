<?php
/**
 * Google Places API Client Class
 *
 * A comprehensive PHP library for interacting with the Google Places API.
 * Supports geocoding, place details, search, autocomplete, and more.
 *
 * @package     ArrayPress\Google\Places
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Places;

use ArrayPress\Google\Places\Traits\Parameters;
use WP_Error;

/**
 * Class Client
 *
 * Main client class for interacting with the Google Places API.
 *
 * @package ArrayPress\Google\Places
 */
class Client {
	use Parameters;

	/**
	 * The Geocoding API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/geocoding
	 * @var string
	 */
	private const API_GEOCODE = 'https://maps.googleapis.com/maps/api/geocode/json';

	/**
	 * The Place Details API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/details
	 * @var string
	 */
	private const API_PLACE_DETAILS = 'https://maps.googleapis.com/maps/api/place/details/json';

	/**
	 * The Place Search API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/search-find-place
	 * @var string
	 */
	private const API_PLACE_SEARCH = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';

	/**
	 * The Places Nearby API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/search-nearby
	 * @var string
	 */
	private const API_PLACE_NEARBY = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';

	/**
	 * The Place Autocomplete API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/autocomplete
	 * @var string
	 */
	private const API_AUTOCOMPLETE = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';

	/**
	 * The Query Autocomplete API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/query
	 * @var string
	 */
	private const API_QUERY_AUTOCOMPLETE = 'https://maps.googleapis.com/maps/api/place/queryautocomplete/json';

	/**
	 * The Place Photo API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/photos
	 * @var string
	 */
	private const API_PHOTO = 'https://maps.googleapis.com/maps/api/place/photo';

	/**
	 * The Text Search API URL endpoint
	 *
	 * @link https://developers.google.com/maps/documentation/places/web-service/search-text
	 * @var string
	 */
	private const API_TEXT_SEARCH = 'https://maps.googleapis.com/maps/api/place/textsearch/json';

	/**
	 * Initialize the Google Places client
	 *
	 * @param string $api_key          API key for Google Places
	 * @param bool   $enable_cache     Whether to enable caching (default: true)
	 * @param int    $cache_expiration Cache expiration in seconds (default: 24 hours)
	 */
	public function __construct( string $api_key, bool $enable_cache = true, int $cache_expiration = DAY_IN_SECONDS ) {
		$this->set_api_key( $api_key );
		$this->set_cache_enabled( $enable_cache );
		$this->set_cache_expiration( $cache_expiration );
	}

	/**
	 * Geocode an address
	 *
	 * @param string|array $address Address to geocode
	 * @param array        $params  Additional parameters
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function geocode( $address, array $params = [] ) {
		if ( is_array( $address ) ) {
			$address = implode( ',', array_filter( $address ) );
		}

		$params['address'] = urlencode( $address );
		$cache_key         = $this->get_cache_key( 'geocode_' . md5( serialize( $params ) ) );

		if ( $this->is_cache_enabled() ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( self::API_GEOCODE, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->is_cache_enabled() ) {
			set_transient( $cache_key, $response, $this->get_cache_expiration() );
		}

		return new Response( $response );
	}

	/**
	 * Get place details by Place ID
	 *
	 * @param string $place_id Google Place ID
	 * @param array  $fields   Optional. Specific fields to retrieve
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function get_place_details( string $place_id, array $fields = [] ) {
		$cache_key = $this->get_cache_key( 'place_' . $place_id );

		if ( $this->is_cache_enabled() ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$params = [ 'place_id' => $place_id ];
		if ( ! empty( $fields ) ) {
			$params['fields'] = implode( ',', $fields );
		}

		$response = $this->make_request( self::API_PLACE_DETAILS, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->is_cache_enabled() ) {
			set_transient( $cache_key, $response, $this->get_cache_expiration() );
		}

		return new Response( $response );
	}

	/**
	 * Find places by text query
	 *
	 * @param string $query Search query
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function find_places( string $query ) {
		// Merge query with stored search parameters
		$params = array_merge(
			[
				'input'     => $query,
				'inputtype' => 'textquery'
			],
			array_filter( $this->search_params ) // Using stored parameters
		);

		$cache_key = $this->get_cache_key( 'find_places_' . md5( serialize( $params ) ) );

		if ( $this->is_cache_enabled() ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( self::API_PLACE_SEARCH, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->is_cache_enabled() ) {
			set_transient( $cache_key, $response, $this->get_cache_expiration() );
		}

		return new Response( $response );
	}

	/**
	 * Search for nearby places
	 *
	 * @param float $lat    Latitude
	 * @param float $lng    Longitude
	 * @param int   $radius Radius in meters (max 50000)
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function nearby_search( float $lat, float $lng, int $radius = 1000 ) {
		// Merge location parameters with stored search parameters
		$params = array_merge(
			[
				'location' => "$lat,$lng",
				'radius'   => min( $radius, 50000 )
			],
			array_filter( $this->search_params ) // Using stored parameters
		);

		$cache_key = $this->get_cache_key( 'nearby_' . md5( serialize( $params ) ) );

		if ( $this->is_cache_enabled() ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( self::API_PLACE_NEARBY, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->is_cache_enabled() ) {
			set_transient( $cache_key, $response, $this->get_cache_expiration() );
		}

		return new Response( $response );
	}

	/**
	 * Get place predictions for autocomplete
	 *
	 * @param string $input The text string to search
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function get_autocomplete_predictions( string $input ) {
		// Merge input with stored autocomplete parameters
		$params = array_merge(
			[ 'input' => $input ],
			array_filter( $this->autocomplete_params ) // Using stored parameters
		);

		$cache_key = $this->get_cache_key( 'autocomplete_' . md5( serialize( $params ) ) );

		if ( $this->is_cache_enabled() ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( self::API_AUTOCOMPLETE, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->is_cache_enabled() ) {
			set_transient( $cache_key, $response, $this->get_cache_expiration() );
		}

		return new Response( $response );
	}

	/**
	 * Get photo URL for a place
	 *
	 * @param string $photo_reference The photo reference from place details
	 *
	 * @return string Photo URL
	 */
	public function get_place_photo_url( string $photo_reference ): string {
		// Merge photo reference with stored photo parameters
		$params = array_merge(
			[
				'photo_reference' => $photo_reference,
				'key'             => $this->get_api_key()
			],
			array_filter( $this->photo_params ) // Using stored parameters
		);

		return add_query_arg( $params, self::API_PHOTO );
	}

	/**
	 * Text search for places
	 *
	 * @param string $query The text string to search
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function text_search( string $query ) {
		// Merge query with stored search parameters
		$params = array_merge(
			[ 'query' => $query ],
			array_filter( $this->search_params ) // Using stored parameters
		);

		$cache_key = $this->get_cache_key( 'text_search_' . md5( serialize( $params ) ) );

		if ( $this->is_cache_enabled() ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( self::API_TEXT_SEARCH, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->is_cache_enabled() ) {
			set_transient( $cache_key, $response, $this->get_cache_expiration() );
		}

		return new Response( $response );
	}

	/**
	 * Make a request to the Google Places API
	 *
	 * @param string $endpoint API endpoint
	 * @param array  $params   Request parameters
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 */
	private function make_request( string $endpoint, array $params = [] ) {
		$params['key'] = $this->get_api_key();

		$url      = add_query_arg( $params, $endpoint );
		$response = wp_remote_get( $url, [
			'timeout' => 15,
			'headers' => [
				'Accept' => 'application/json'
			]
		] );

		return $this->handle_response( $response );
	}

	/**
	 * Handle API response
	 *
	 * @param array|WP_Error $response API response
	 *
	 * @return array|WP_Error Processed response or WP_Error
	 */
	private function handle_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Google Places API request failed: %s', 'arraypress' ),
					$response->get_error_message()
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse Google Places API response', 'arraypress' )
			);
		}

		if ( $data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS' ) {
			$error_message = $data['error_message'] ?? $data['status'];

			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Google Places API returned error: %s', 'arraypress' ),
					$error_message
				)
			);
		}

		return $data;
	}

	/**
	 * Generate cache key
	 *
	 * @param string $identifier Unique identifier
	 *
	 * @return string Cache key
	 */
	private function get_cache_key( string $identifier ): string {
		return 'places_' . md5( $identifier . $this->get_api_key() );
	}

	/**
	 * Clear cached data
	 *
	 * @param string|null $identifier Optional specific cache to clear
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache( ?string $identifier = null ): bool {
		if ( $identifier !== null ) {
			return delete_transient( $this->get_cache_key( $identifier ) );
		}

		global $wpdb;
		$pattern = $wpdb->esc_like( '_transient_places_' ) . '%';

		return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			) !== false;
	}

}