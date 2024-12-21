<?php
/**
 * Google Geocoding API Parameters Trait
 *
 * @package     ArrayPress\Google\Geocoding
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Places\Traits;

use WP_Error;

trait Parameters {

	/**
	 * API key for Google Places
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Cache settings
	 *
	 * @var array
	 */
	private array $cache_settings = [
		'enabled'    => true,
		'expiration' => DAY_IN_SECONDS
	];

	/**
	 * Photo parameters
	 *
	 * @var array
	 */
	private array $photo_params = [
		'maxwidth'  => 400,
		'maxheight' => 400
	];

	/**
	 * Search parameters
	 *
	 * @var array
	 */
	private array $search_params = [
		'type'      => '',
		'keyword'   => '',
		'language'  => '',
		'minprice'  => null,
		'maxprice'  => null,
		'opennow'   => false,
		'rankby'    => '',
		'pagetoken' => ''
	];

	/**
	 * Autocomplete parameters
	 *
	 * @var array
	 */
	private array $autocomplete_params = [
		'types'        => [],
		'components'   => '',
		'language'     => '',
		'location'     => null,
		'radius'       => null,
		'strictbounds' => false,
		'sessiontoken' => ''
	];

	/** API Key ******************************************************************/

	/**
	 * Set API key
	 *
	 * @param string $api_key The API key to use
	 *
	 * @return self
	 */
	public function set_api_key( string $api_key ): self {
		$this->api_key = $api_key;

		return $this;
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/** Cache ********************************************************************/

	/**
	 * Set cache status
	 *
	 * @param bool $enable Whether to enable caching
	 *
	 * @return self
	 */
	public function set_cache_enabled( bool $enable ): self {
		$this->cache_settings['enabled'] = $enable;

		return $this;
	}

	/**
	 * Get cache status
	 *
	 * @return bool
	 */
	public function is_cache_enabled(): bool {
		return $this->cache_settings['enabled'];
	}

	/**
	 * Set cache expiration time
	 *
	 * @param int $seconds Cache expiration time in seconds
	 *
	 * @return self|WP_Error
	 */
	public function set_cache_expiration( int $seconds ) {
		if ( $seconds < 0 ) {
			return new WP_Error(
				'invalid_expiration',
				__( 'Cache expiration time cannot be negative', 'arraypress' )
			);
		}
		$this->cache_settings['expiration'] = $seconds;

		return $this;
	}

	/**
	 * Get cache expiration time in seconds
	 *
	 * @return int
	 */
	public function get_cache_expiration(): int {
		return $this->cache_settings['expiration'];
	}

	/**
	 * Get all cache settings
	 *
	 * @return array Current cache settings
	 */
	public function get_cache_settings(): array {
		return $this->cache_settings;
	}

	/** Options ******************************************************************/

	/**
	 * Set photo maximum width
	 *
	 * @param int $width Maximum width in pixels
	 *
	 * @return $this
	 */
	public function set_photo_max_width( int $width ): self {
		$this->photo_params['maxwidth'] = $width;

		return $this;
	}

	/**
	 * Get photo maximum width
	 *
	 * @return int|null
	 */
	public function get_photo_max_width(): ?int {
		return $this->photo_params['maxwidth'] ?? null;
	}

	/**
	 * Set photo maximum height
	 *
	 * @param int $height Maximum height in pixels
	 *
	 * @return $this
	 */
	public function set_photo_max_height( int $height ): self {
		$this->photo_params['maxheight'] = $height;

		return $this;
	}

	/**
	 * Get photo maximum height
	 *
	 * @return int|null
	 */
	public function get_photo_max_height(): ?int {
		return $this->photo_params['maxheight'] ?? null;
	}

	/**
	 * Set search type filter
	 *
	 * @param string $type Place type (e.g., 'restaurant', 'cafe')
	 *
	 * @return $this
	 */
	public function set_search_type( string $type ): self {
		$this->search_params['type'] = $type;

		return $this;
	}

	/**
	 * Get search type filter
	 *
	 * @return string|null
	 */
	public function get_search_type(): ?string {
		return $this->search_params['type'] ?? null;
	}

	/**
	 * Set search keyword
	 *
	 * @param string $keyword Search keyword
	 *
	 * @return $this
	 */
	public function set_search_keyword( string $keyword ): self {
		$this->search_params['keyword'] = $keyword;

		return $this;
	}

	/**
	 * Get search keyword
	 *
	 * @return string|null
	 */
	public function get_search_keyword(): ?string {
		return $this->search_params['keyword'] ?? null;
	}

	/**
	 * Set minimum price level
	 *
	 * @param int $level Price level (0-4)
	 *
	 * @return $this
	 */
	public function set_min_price( int $level ): self {
		$this->search_params['minprice'] = max( 0, min( 4, $level ) );

		return $this;
	}

	/**
	 * Get minimum price level
	 *
	 * @return int|null
	 */
	public function get_min_price(): ?int {
		return $this->search_params['minprice'] ?? null;
	}

	/**
	 * Set maximum price level
	 *
	 * @param int $level Price level (0-4)
	 *
	 * @return $this
	 */
	public function set_max_price( int $level ): self {
		$this->search_params['maxprice'] = max( 0, min( 4, $level ) );

		return $this;
	}

	/**
	 * Get maximum price level
	 *
	 * @return int|null
	 */
	public function get_max_price(): ?int {
		return $this->search_params['maxprice'] ?? null;
	}

	/**
	 * Set open now filter
	 *
	 * @param bool $open_now Whether to return only places that are open now
	 *
	 * @return $this
	 */
	public function set_open_now( bool $open_now ): self {
		$this->search_params['opennow'] = $open_now;

		return $this;
	}

	/**
	 * Get open now filter
	 *
	 * @return bool
	 */
	public function get_open_now(): bool {
		return $this->search_params['opennow'] ?? false;
	}

	/**
	 * Set ranking method
	 *
	 * @param string $rankby Either 'prominence' or 'distance'
	 *
	 * @return $this
	 */
	public function set_rank_by( string $rankby ): self {
		if ( in_array( $rankby, [ 'prominence', 'distance' ] ) ) {
			$this->search_params['rankby'] = $rankby;
		}

		return $this;
	}

	/**
	 * Get ranking method
	 *
	 * @return string|null
	 */
	public function get_rank_by(): ?string {
		return $this->search_params['rankby'] ?? null;
	}

	/**
	 * Set language for results
	 *
	 * @param string $language Language code (e.g., 'en', 'es')
	 *
	 * @return $this
	 */
	public function set_language( string $language ): self {
		$this->search_params['language']       = $language;
		$this->autocomplete_params['language'] = $language;

		return $this;
	}

	/**
	 * Get language for results
	 *
	 * @return string|null
	 */
	public function get_language(): ?string {
		return $this->search_params['language'] ?? null;
	}

	/**
	 * Set page token for pagination
	 *
	 * @param string $token Next page token
	 *
	 * @return $this
	 */
	public function set_page_token( string $token ): self {
		$this->search_params['pagetoken'] = $token;

		return $this;
	}

	/**
	 * Get page token
	 *
	 * @return string|null
	 */
	public function get_page_token(): ?string {
		return $this->search_params['pagetoken'] ?? null;
	}

	/**
	 * Set autocomplete types
	 *
	 * @param array $types Array of place types
	 *
	 * @return $this
	 */
	public function set_autocomplete_types( array $types ): self {
		$this->autocomplete_params['types'] = $types;

		return $this;
	}

	/**
	 * Get autocomplete types
	 *
	 * @return array
	 */
	public function get_autocomplete_types(): array {
		return $this->autocomplete_params['types'] ?? [];
	}

	/**
	 * Set autocomplete components
	 *
	 * @param string $components Components filter
	 *
	 * @return $this
	 */
	public function set_autocomplete_components( string $components ): self {
		$this->autocomplete_params['components'] = $components;

		return $this;
	}

	/**
	 * Get autocomplete components
	 *
	 * @return string|null
	 */
	public function get_autocomplete_components(): ?string {
		return $this->autocomplete_params['components'] ?? null;
	}

	/**
	 * Set autocomplete location bias
	 *
	 * @param float $lat    Latitude
	 * @param float $lng    Longitude
	 * @param int   $radius Radius in meters
	 *
	 * @return $this
	 */
	public function set_autocomplete_location( float $lat, float $lng, int $radius = 50000 ): self {
		$this->autocomplete_params['location'] = "$lat,$lng";
		$this->autocomplete_params['radius']   = $radius;

		return $this;
	}

	/**
	 * Get autocomplete location
	 *
	 * @return array|null ['location' => string, 'radius' => int]
	 */
	public function get_autocomplete_location(): ?array {
		if ( isset( $this->autocomplete_params['location'] ) ) {
			return [
				'location' => $this->autocomplete_params['location'],
				'radius'   => $this->autocomplete_params['radius']
			];
		}

		return null;
	}

	/**
	 * Set strict bounds for autocomplete
	 *
	 * @param bool $strict Whether to return only results within bounds
	 *
	 * @return $this
	 */
	public function set_strict_bounds( bool $strict ): self {
		$this->autocomplete_params['strictbounds'] = $strict;

		return $this;
	}

	/**
	 * Get strict bounds setting
	 *
	 * @return bool
	 */
	public function get_strict_bounds(): bool {
		return $this->autocomplete_params['strictbounds'] ?? false;
	}

	/**
	 * Set session token for autocomplete
	 *
	 * @param string $token Session token
	 *
	 * @return $this
	 */
	public function set_session_token( string $token ): self {
		$this->autocomplete_params['sessiontoken'] = $token;

		return $this;
	}

	/**
	 * Get session token
	 *
	 * @return string|null
	 */
	public function get_session_token(): ?string {
		return $this->autocomplete_params['sessiontoken'] ?? null;
	}

	/**
	 * Reset all search parameters
	 *
	 * @return $this
	 */
	public function reset_search_params(): self {
		$this->search_params = [
			'type'      => '',
			'keyword'   => '',
			'language'  => '',
			'minprice'  => null,
			'maxprice'  => null,
			'opennow'   => false,
			'rankby'    => '',
			'pagetoken' => ''
		];

		return $this;
	}

	/**
	 * Reset all autocomplete parameters
	 *
	 * @return $this
	 */
	public function reset_autocomplete_params(): self {
		$this->autocomplete_params = [
			'types'        => [],
			'components'   => '',
			'language'     => '',
			'location'     => null,
			'radius'       => null,
			'strictbounds' => false,
			'sessiontoken' => ''
		];

		return $this;
	}

	/**
	 * Reset all photo parameters
	 *
	 * @return $this
	 */
	public function reset_photo_params(): self {
		$this->photo_params = [
			'maxwidth'  => 400,
			'maxheight' => 400
		];

		return $this;
	}

	/**
	 * Reset all parameters
	 *
	 * @return $this
	 */
	public function reset_all_params(): self {
		$this->reset_search_params();
		$this->reset_autocomplete_params();
		$this->reset_photo_params();

		return $this;
	}

}