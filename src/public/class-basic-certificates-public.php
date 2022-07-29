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
 * @package Badge_Factor_2_Certificates
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound
 * @phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
 */

namespace BadgeFactor2;

use BadgeFactor2\Controllers\Basic_Certificate_Controller;
use BadgeFactor2\Helpers\BuddyPress;
use BadgeFactor2\Models\BadgeClass;
use BadgeFactor2\Models\Issuer;
use setasign\Fpdi\Tcpdf\Fpdi;

class Basic_Certificates_Public {


	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	public static function init_hooks() {

		add_action( 'init', array( self::class, 'add_rewrite_tags' ), 10, 0 );
		add_action( 'init', array( self::class, 'add_rewrite_rules' ), 10, 0 );

		add_filter( 'template_include', array( Basic_Certificate_Controller::class, 'single' ) );

		add_action( 'bf2_assertion_links', array( self::class, 'certificate_link' ) );
	}


	/**
	 * Rewrite tags.
	 *
	 * @return void
	 */
	public static function add_rewrite_tags() {
		add_rewrite_tag( '%certificate%', '([^&]+)' );
	}


	public static function get_certificate_slug() {
		$options = get_option( 'bf2_basic_certificates_settings' );
		return ! empty( $options['bf2_certificate_slug'] ) ? $options['bf2_certificate_slug'] : 'certificate';
	}

	/**
	 * Rewrite rules.
	 *
	 * @return void
	 */
	public static function add_rewrite_rules() {
		if ( BuddyPress::is_active() ) {
			// Members page managed by BuddyPress.
			$members_page = BuddyPress::get_members_page_name();

			// FIXME make certificate variable.
			$certificate_slug = self::get_certificate_slug();

			add_rewrite_rule( "{$members_page}/([^/]+)/badges/([^/]+)/{$certificate_slug}/?$", 'index.php?member=$matches[1]&badge=$matches[2]&certificate=1', 'top' );
		} else {
			// TODO Manage Members page without BuddyPress.
		}
	}


	/**
	 * Generate certificate.
	 */
	public static function generate( $assertion ) {

		$plugin_data = get_plugin_data( BF2_BASIC_CERTIFICATES_FILE );
		$settings    = get_option( 'bf2_basic_certificates_settings' );

		$template_file = get_attached_file( $settings['bf2_certificate_template_id'] );

		if ( $template_file ) {
			$badge          = BadgeClass::get( $assertion->badgeclass );
			$issuer         = Issuer::get( $badge->issuer );
			$recipient      = get_user_by( 'email', $assertion->recipient->plaintextIdentity );
			$portfolio_link = bp_core_get_user_domain( $recipient->ID );

			$pdf = new Fpdi();
			$pdf->AddPage( 'L', 'Letter' );
			$pdf->setSourceFile( $template_file );

			$tpl_id = $pdf->importPage( 1 );
			$pdf->useTemplate( $tpl_id, 0, 0, null, null, true );

			foreach ( $settings as $id => $field_settings ) {

				if ( false === strpos( $id, 'bf2_certificate_template' ) && is_array( $field_settings ) ) {

					$field_settings['link'] = null;

					if ( strpos( $field_settings['text'], '$badge$' ) !== false ) {
						// $badge$
						$image = str_replace( '$badge$', $assertion->image, $field_settings['text'] );
						self::generate_pdf_image( $pdf, $field_settings, $image );
					} else {
						$text = $field_settings['text'];

						// $course$
						// if ( $course && strpos( $text, '$course$' ) !== false ) {
						// 	$text = str_replace( '$course$', $course->post_title, $text );
						// } else {
						// 	// If no course associated to badge, use badge name instead.
						// 	$text = str_replace( '$course$', $badge->name, $text );
						// }

						// $date$
						if ( strpos( $text, '$date$' ) !== false ) {
							$text = str_replace( '$date$', date( 'Y-m-d', strtotime( $assertion->issuedOn ) ), $text );
						}

						// $hours$
						// if ( strpos( $text, '$hours$' ) !== false ) {
						// 	$hours = get_post_meta( $course->ID, 'course_duration', true );
						// 	if ( false === $hours ) {
						// 		$hours = 'N/A';
						// 	}
						// 	if ( '1' === $hours ) {
						// 		$hours .= sprintf( ' %s', __( 'hour', $plugin_data['TextDomain'] ) );
						// 	} else {
						// 		$hours .= sprintf( ' %s', __( 'hours', $plugin_data['TextDomain'] ) );
						// 	}
						// 	$text = str_replace( '$hours$', $hours, $text );
						// }

						// $issuer$
						if ( strpos( $text, '$issuer$' ) !== false ) {
							$text = str_replace( '$issuer$', $issuer->name, $text );
						}

						// $name$
						if ( strpos( $text, '$name$' ) !== false ) {
							$text = str_replace( '$name$', $recipient->display_name, $text );
						}

						// $portfolio$
						if ( strpos( $text, '$portfolio$' ) !== false ) {
							$text                   = str_replace( '$portfolio$', $portfolio_link, $text );
							$field_settings['link'] = $portfolio_link;
						}

						self::generate_pdf_text( $pdf, $field_settings, $text );
					}
				}
			}
			status_header( 200 );
			$pdf->Output();
			die;
		} else {
			echo 'BadgeFactor 2 settings missing!';
		}
	}


	public static function certificate_link() {
		$settings      = get_option( 'bf2_certificates_settings' );
		$template_file = get_attached_file( $settings['bf2_certificate_template_id'] );

		if ( $template_file ) {
			$plugin_data = get_plugin_data( BF2_CERTIFICATES_FILE );

			$certificate_slug = self::get_certificate_slug();

			echo sprintf( '<a target="_blank" href="%s">%s</a>', $certificate_slug, __( 'View certificate', $plugin_data['TextDomain'] ) );
		} else {
			if ( current_user_can( 'manage_badgr' ) ) {
				echo sprintf( '<a href="%s">%s</a>', admin_url() . 'admin.php?page=bf2_certificates_settings', __( 'Missing Certificate Template in settings!', $plugin_data['TextDomain'] ) );
			}
		}
	}


	/**
	 * Generate PDF Image helper function.
	 *
	 * @param Fpdi $pdf Fpdi Class.
	 * @param array $field_settings Field Settings.
	 * @param string $content Field Content.
	 * @return void
	 */
	private static function generate_pdf_image( Fpdi $pdf, array $field_settings, $content ) {
		switch ( $field_settings['align'] ) {
			case 'C':
				$pos_x = (int) $field_settings['pos_x'] - (int) round( $field_settings['width'] / 2 );
				break;
			case 'R':
				$pos_x = (int) $field_settings['pos_x'] - (int) $field_settings['width'];
				break;
			case 'L':
			default:
				$pos_x = (int) $field_settings['pos_x'];
				break;
		}
		$pdf->image( $content, $pos_x, $field_settings['pos_y'], $field_settings['width'] );
	}


	/**
	 * Generate PDF Text helper function.
	 *
	 * @param Fpdi $pdf Fpdi Class.
	 * @param array $field_settings Field Settings.
	 * @param string $content Field Content.
	 * @return void
	 */
	private static function generate_pdf_text( Fpdi $pdf, array $field_settings, $content ) {
		$arr_rgb = [0, 0, 0];
		// $pdf->SetXY( $field_settings['pos_x'], $field_settings['pos_y'] );
		$pdf->setFont( $field_settings['font'], $field_settings['style'] );
		$pdf->setFontSize( $field_settings['size'] );
		
		if ( isset( $field_settings['color'] ) && trim( $field_settings['color'] ) != '' ) {
			list( $r, $g, $b ) = array_map(
				function ( $c ) {
					return hexdec( str_pad( $c, 2, $c ) );
				},
				str_split( ltrim( $field_settings['color'], '#' ), strlen( $field_settings['color'] ) > 4 ? 2 : 1 )
			);
			$arr_rgb = [$r, $g, $b];
		}
		$pdf->SetTextColor( $arr_rgb[0], $arr_rgb[1], $arr_rgb[2] );
		
		$pdf->MultiCell(
			$field_settings['width'], // Width.
			0, // Height.
			$content, // Text.
			1, // Border.
			$field_settings['align'], // Align.
			0,
			1,
			$field_settings['pos_x'], 
			$field_settings['pos_y'],
			true, 
			0, 
			false, 
			true, 
			0
		);
	}
}
