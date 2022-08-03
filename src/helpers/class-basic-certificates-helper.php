<?php
/**
 * Badge Factor 2
 * Copyright (C) 2021 ctrlweb
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package Badge_Factor_2
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound 
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound
 */

namespace BadgeFactor2\Helpers;

use BadgeFactor2\Basic_Certificates_Public;
use BadgeFactor2\Models\Assertion;
use BadgeFactor2\Post_Types\BadgePage;

/**
 * Basic certificate helper class.
 */
class BasicCerficateHelper {

	/**
	 * Generates and saves certificate pdf file in disk.
	 *
     * @param string $badge_page_slug
	 * @return string
	 */
	public static function generate_and_save_certificate( $badge_page_slug ) {
		error_log( 'generate_and_save_certificate' );	
        $user       = wp_get_current_user();
        $assertions = Assertion::all_for_user( $user );
        $filename = '';

        foreach ( $assertions as $a ) {
            $badgepage = BadgePage::get_by_badgeclass_id( $a->badgeclass );
            if ( false !== $badgepage ) {
                if ( $badge_page_slug === $badgepage->post_name ) {
                    $filename = Basic_Certificates_Public::generate( $a, true );
                }
            }
        }
		
        return $filename;
	}

    /**
	 * generate filename based on user and badge page.
	 * 
     * @param WP_User $user
     * @param \BadgeFactor2\Post_Types\BadgePage $badge_page
	 * @return string
	 */
	public static function generate_filename( $user, $badge_page ) {
        error_log( 'generating filename...' );
		return $user->user_login . '_' . $badge_page->post_name . '.pdf';
	}
}
