/*
 * Badge Factor 2
 * Copyright (C) 2019 ctrlweb
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
 */
jQuery(document).ready(function ($) {
    
    /* Show form popup */
    $('body').on('click', '.send-certificate', function (e) {
        e.preventDefault();
        $('.send_email_popup_action_message').html('');
        $('.send_email_popup_action_message').hide();
        var popupOverlay = $('.send_email_popup_overlay');
        popupOverlay.css('opacity','1');
        popupOverlay.css('display','flex');
    });

    /* Close form popup */
    $('body').on('click', '.close_send_email', function (e) {
        e.preventDefault();
        var popupOverlay = $('.send_email_popup_overlay');
        $('.send_email_popup_action_message').html('');
        $('.send_email_popup_action_message').hide();
        popupOverlay.css('display','none');
    });

    $('body').on('submit', '#send_email_form', function (e) {
        e.preventDefault();
        var popupOverlay = $('.send_email_popup_overlay');
        $('#send_email_btn_confirm').text('Sending...');
        $('#send_email_btn_confirm').prop('disabled', true);
        var nonce = $('#send-basic-certificate-nonce').val();
        var toEmail = $('#send_to_email_address').val();
        var badgePage = $('#badge_page').val();
        var message = '';

        $('.send_email_popup_action_message').removeClass('error');
        $('.send_email_popup_action_message').removeClass('success');
        $('.send_email_popup_action_message').html('');
        $('.send_email_popup_action_message').hide();

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: "send_basic_certificate_email",
                badge_page: badgePage,
                nonce: nonce,
                to_email: toEmail
            },
            success: function (response) {
                if (response.success === false) {
                    message += '<ul>'
                    if (typeof response.errors.email != 'undefined') {
                        message += '<li>' + response.errors.email + '</li>';
                    }
                    if (typeof response.errors.system != 'undefined') {
                        message += '<li>' + response.errors.system + '</li>';
                    }
                    message += '</ul>';
                    $('.send_email_popup_action_message').addClass('error');
                    $('.send_email_popup_action_message').show();
                    $('.send_email_popup_action_message').html(message);

                    $('.send_email_popup_action_message').text();
                    $('#send_email_btn_confirm').text("Send");
                    $('#send_email_btn_confirm').prop('disabled', false);

                } else {
                    $('.send_email_popup_action_message').addClass('success');
                    $('.send_email_popup_action_message').show();
                    $('.send_email_popup_action_message').html('Email successfully sent');
                    $('#send_email_btn_confirm').text("Send");
                    $('#send_email_btn_confirm').prop('disabled', false);
                }

            },
            complete: function (response) {
                // grecaptcha.reset();
            }
        });
    });
});