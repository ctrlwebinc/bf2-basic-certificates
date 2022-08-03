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
 * @phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
 */

namespace BadgeFactor2\Controllers;

use BadgeFactor2\Basic_Certificates_Public;
use BadgeFactor2\Models\Assertion;
use BadgeFactor2\Page_Controller;
use BadgeFactor2\Post_Types\BadgePage;


/**
 * Certificate Controller Class.
 */
class Basic_Certificate_Controller extends Page_Controller {

	/**
	 * Post Type.
	 *
	 * @var string
	 */
	protected static $post_type = 'certificate';


	/**
	 * Outputs single template with $fields array.
	 *
	 * @param string $default_template Default template (for filter hook).
	 * @return void
	 */
	public static function single( $default_template = null ) {
		if ( get_query_var( 'member' ) && get_query_var( 'badge' ) && get_query_var( 'certificate' ) ) {
			$user       = get_user_by( 'slug', get_query_var( 'member' ) );
			$assertions = Assertion::all_for_user( $user );

			foreach ( $assertions as $a ) {
				$badgepage = BadgePage::get_by_badgeclass_id( $a->badgeclass );
				if ( false !== $badgepage ) {
					if ( get_query_var( 'badge' ) === $badgepage->post_name ) {
						Basic_Certificates_Public::generate( $a );
						die;
					}
				}
			}
		}
		if ( $default_template ) {
			return $default_template;
		}
	}

}
