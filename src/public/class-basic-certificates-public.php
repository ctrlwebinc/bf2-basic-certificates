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
use BadgeFactor2\Helpers\BasicCerficateHelper;
use BadgeFactor2\Helpers\BuddyPress;
use BadgeFactor2\Models\BadgeClass;
use BadgeFactor2\Models\Issuer;
use setasign\Fpdi\Tcpdf\Fpdi;
use BadgeFactor2\Post_Types\BadgePage;

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
			
			add_rewrite_rule( "{$certificate_slug}/([^/]+)/([^/]+)/?$", 'index.php?member=$matches[1]&badge=$matches[2]&certificate=1', 'top' );
		} else {
			// TODO Manage Members page without BuddyPress.
		}
	}


	/**
	 * Generate certificate.
	 * 
	 * @param \BadgeFactor2\Models\Assertion $assertion;
	 * @param bool $save save to disk
	 * 
	 * @return mixed void|string 
	 */
	public static function generate( $assertion, $save = false ) {

		$plugin_data = get_plugin_data( BF2_BASIC_CERTIFICATES_FILE );
		$settings    = get_option( 'bf2_basic_certificates_settings' );

		$template_file = get_attached_file( $settings['bf2_certificate_template_id'] );
		$storage_root = WP_CONTENT_DIR . '/attachments/';

		if ( $template_file ) {
			$badge          = BadgeClass::get( $assertion->badgeclass );
			$badgepage      = BadgePage::get_by_badgeclass_id( $assertion->badgeclass );
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

						// $date$
						if ( strpos( $text, '$date$' ) !== false ) {
							$text = str_replace( '$date$', date( 'Y-m-d', strtotime( $assertion->issuedOn ) ), $text );
						}

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

			if ( $save ) {
				$filename = BasicCerficateHelper::generate_filename( $recipient, $badgepage );
				$filename = $storage_root . $filename;
				
				if ( ! file_exists( $filename ) ) {
					$pdf->Output($filename,'F');
				} else {
				}
				
				return $filename;
			} else {
				status_header( 200 );
				$pdf->Output();
				die;
			}
		} else {
			echo 'BadgeFactor 2 settings missing!';
		}
	}


	public static function certificate_link() {
		$settings      = get_option( 'bf2_certificates_settings' );
		$template_file = get_attached_file( $settings['bf2_certificate_template_id'] );
		$current_user = wp_get_current_user();

		if ( $template_file ) {
			if ( $current_user->ID > 0 && get_query_var( 'badge' ) != '' ) {
				$plugin_data = get_plugin_data( BF2_CERTIFICATES_FILE );

				$home = get_bloginfo( 'url' );
				$certificate_slug = self::get_certificate_slug();
				$username = $current_user->user_login;
				$badge = get_query_var( 'badge' );
				echo sprintf( 
					'<a target="_blank" href="%s/%s/%s/%s/">%s</a>', 
					$home,
					$certificate_slug,$username,
					$badge,
					__( 'Download the certificate', $plugin_data['TextDomain'] )
				);
			}
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

	
	/**
	 * Sets From email for wp_mail headers
	 */
	public static function new_mail_from() {
		$current_user = wp_get_current_user();

		$from_email = get_bloginfo( 'admin_email' );

		if ( $current_user->ID > 0 ) {
			$from_email = $current_user->user_email;
		}

		return $from_email;
	}

	/**
	 * Sets From name for wp_mail headers
	 */
	public static function new_mail_from_name() {
		$current_user = wp_get_current_user();

		$from_name = get_bloginfo( 'blogname' );

		if ( $current_user->ID > 0 ) {
			$from_name = $current_user->first_name . ' ' . $current_user->last_name;
			$from_name = ( $from_name != '' ) ? $from_name : $current_user->user_nicename;
			$from_name = ( $from_name != '' ) ? $from_name : get_bloginfo( 'blogname' );
		}

		return $from_name;
	}
}
