<?php
/**
 * Author Migration functions
 *
 * @package erikdmitchell\bcmigration
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

/**
 * Migrate author social channels repeater field to a single URL.
 *
 * Given the array of social media channels, find the first instance of the
 * target channel (in this case, LinkedIn) and return its URL.
 *
 * @param array $value The array of social media channels.
 * @param string $key The field name of the social media channels.
 *
 * @return string The URL of the target social media channel.
 */
function migrate_author_social_channels($value, $key) {
	if ('social_media' !== $key || empty($value)) {
		return $value;
	}

	$target_channel = 'linkedin';

	foreach ($value as $social) {
		if ($social['social_channel'] === $target_channel) {
			$value = $social['social_url'];
			break;
		}
	}

	return $value;	
}
add_filter( 'bcm_convert_repeater_to_url', __NAMESPACE__ . '\migrate_author_social_channels', 10, 2 );
