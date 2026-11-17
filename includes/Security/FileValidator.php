<?php
/**
 * File Upload Validator for Clinical Reports & AI Summaries
 *
 * Validates upload errors, file size, WordPress filetype/extension,
 * and real MIME content via finfo.
 *
 * @package ClicReport\AssessmentEngine\Security
 */

namespace ClicReport\AssessmentEngine\Security;

defined( 'ABSPATH' ) || exit;

final class FileValidator {

    private const MAX_BYTES = 10485760; // 10 MB

    private const ALLOWED_MIMES = [
        'pdf'  => 'application/pdf',
        'txt'  => 'text/plain',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /**
     * Validate a $_FILES entry safely.
     *
     * @param array<string, mixed> $fileEntry Single item from $_FILES.
     * @return array<string, mixed>|\WP_Error Sanitized file descriptor or WP_Error.
     */
    public function validate( array $fileEntry ): array|\WP_Error {
        if ( empty( $fileEntry['tmp_name'] ) || ! is_uploaded_file( (string) $fileEntry['tmp_name'] ) ) {
            return new \WP_Error( 'crae_invalid_upload', 'No valid uploaded file received.', [ 'status' => 400 ] );
        }

        if ( UPLOAD_ERR_OK !== (int) ( $fileEntry['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
            return new \WP_Error( 'crae_upload_error', 'File upload failed during transfer.', [ 'status' => 400 ] );
        }

        $size = (int) ( $fileEntry['size'] ?? 0 );
        if ( $size <= 0 || $size > self::MAX_BYTES ) {
            return new \WP_Error( 'crae_file_too_large', 'Uploaded file exceeds the 10 MB security limit.', [ 'status' => 400 ] );
        }

        $safeName = sanitize_file_name( (string) ( $fileEntry['name'] ?? '' ) );
        $tmpPath  = (string) $fileEntry['tmp_name'];

        $checked = wp_check_filetype_and_ext( $tmpPath, $safeName, self::ALLOWED_MIMES );
        if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
            return new \WP_Error( 'crae_disallowed_file_type', 'Only PDF, DOCX, and TXT files are permitted.', [ 'status' => 415 ] );
        }

        if ( function_exists( 'finfo_open' ) ) {
            $finfo    = finfo_open( FILEINFO_MIME_TYPE );
            $realMime = $finfo ? (string) finfo_file( $finfo, $tmpPath ) : '';
            if ( $finfo ) {
                finfo_close( $finfo );
            }

            $validRealMimes = array_merge( array_values( self::ALLOWED_MIMES ), [ 'application/zip' ] ); // DOCX uses zip container.
            if ( '' !== $realMime && ! in_array( $realMime, $validRealMimes, true ) ) {
                return new \WP_Error( 'crae_mime_mismatch', 'File contents do not match an allowed document type.', [ 'status' => 415 ] );
            }
        }

        return [
            'name'     => wp_unique_filename( sys_get_temp_dir(), $safeName ),
            'tmp_name' => $tmpPath,
            'ext'      => $checked['ext'],
            'mime'     => $checked['type'],
            'size'     => $size,
        ];
    }
}

