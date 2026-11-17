<?php
/**
 * WordPress Contact & Clinical Notification Mailer Service
 * Replaces legacy unauthenticated mail.php with wp_mail() & header injection guards.
 *
 * @package ClicReport\AssessmentEngine\Services\Mail
 */

namespace ClicReport\AssessmentEngine\Services\Mail;

defined( 'ABSPATH' ) || exit;

final class NotificationService {

    /**
     * Send a sanitized contact/support message using WordPress wp_mail().
     *
     * @throws \InvalidArgumentException On invalid sender email or empty message.
     */
    public function sendContactMessage( string $senderName, string $senderEmail, string $subject, string $message ): bool {
        $cleanName    = $this->stripNewlines( sanitize_text_field( $senderName ) );
        $cleanEmail   = sanitize_email( $senderEmail );
        $cleanSubject = $this->stripNewlines( sanitize_text_field( $subject ) );
        $cleanMessage = sanitize_textarea_field( $message );

        if ( '' === $cleanName || ! is_email( $cleanEmail ) || '' === $cleanMessage ) {
            throw new \InvalidArgumentException( 'Valid name, email address, and message are required.' );
        }

        $settings  = get_option( 'crae_settings', [] );
        $recipient = ! empty( $settings['contact_recipient_email'] ) && is_email( (string) $settings['contact_recipient_email'] )
            ? (string) $settings['contact_recipient_email']
            : (string) get_option( 'admin_email' );

        $mailSubject = '' !== $cleanSubject
            ? sprintf( '[ClicReport] %s', $cleanSubject )
            : sprintf( '[ClicReport] Support Inquiry from %s', $cleanName );

        $body = sprintf(
            "Sender Name: %s\nSender Email: %s\n\nMessage:\n%s\n",
            $cleanName,
            $cleanEmail,
            $cleanMessage
        );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf( 'Reply-To: %s <%s>', $cleanName, $cleanEmail ),
        ];

        return wp_mail( $recipient, $mailSubject, $body, $headers );
    }

    private function stripNewlines( string $value ): string {
        return str_replace( [ "\r", "\n", '%0a', '%0d' ], '', $value );
    }
}

