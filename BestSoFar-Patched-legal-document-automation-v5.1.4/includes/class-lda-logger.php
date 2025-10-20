<?php
/**
 * A simple file-based logger.
 *
 * @package LegalDocumentAutomation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LDA_Logger {

    const MAX_LOG_SIZE = 10485760; // 10MB limit
    const MAX_LOG_FILES = 5; // Keep 5 rotated files
    
    /**
     * Rotate logs if they get too large
     */
    private static function rotate_logs_if_needed($log_file) {
        if (!file_exists($log_file)) {
            return;
        }
        
        $size = filesize($log_file);
        if ($size > self::MAX_LOG_SIZE) {
            // Archive current log with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $archive_file = str_replace('.log', "_archived_{$timestamp}.log", $log_file);
            
            // Move current log to archive
            rename($log_file, $archive_file);
            
            // Clean up old archives (keep only MAX_LOG_FILES)
            self::cleanup_old_logs($log_file);
            
            // Log rotation message to new file
            self::log("v5.1.3-ENHANCED-SPLIT-TAG-RECONSTRUCTION: Log rotated - Previous log archived as " . basename($archive_file), 'INFO');
        }
    }
    
    /**
     * Clean up old log files
     */
    private static function cleanup_old_logs($log_file) {
        $log_dir = dirname($log_file);
        $log_name = basename($log_file, '.log');
        
        $pattern = $log_dir . '/' . $log_name . '_archived_*.log';
        $archived_files = glob($pattern);
        
        if (count($archived_files) > self::MAX_LOG_FILES) {
            // Sort by modification time (oldest first)
            usort($archived_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($archived_files, 0, count($archived_files) - self::MAX_LOG_FILES);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }

    private static function get_log_file_path() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/lda-logs/';

        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        return $log_dir . 'lda-main.log';
    }
    
    /**
     * Get path for focused debug log (v5.1.3 ENHANCED)
     */
    private static function get_debug_log_file_path() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/lda-logs/';

        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        return $log_dir . 'lda-debug-v5.log';
    }
    
    /**
     * Create a fresh debug session log
     */
    public static function start_debug_session($entry_id) {
        $debug_file = self::get_debug_log_file_path();
        
        // Set Australian Eastern timezone for logging
        $original_timezone = date_default_timezone_get();
        date_default_timezone_set('Australia/Melbourne');
        $timestamp = date('d/m/Y H:i:s'); // Australian format: DD/MM/YYYY
        date_default_timezone_set($original_timezone);
        
        $session_header = "=== NEW DEBUG SESSION STARTED ===\n";
        $session_header .= "Timestamp: {$timestamp} (AEDT)\n";
        $session_header .= "Entry ID: {$entry_id}\n";
        $session_header .= "======================================\n\n";
        
        // Truncate file and start fresh
        @file_put_contents($debug_file, $session_header, LOCK_EX);
    }

    /**
     * Logs a message to a file with automatic rotation.
     *
     * @param string $message The message to log.
     * @param string $level The log level (e.g., INFO, WARNING, ERROR).
     */
    public static function log($message, $level = 'INFO') {
        // Check and rotate main log if needed
        $log_file = self::get_log_file_path();
        self::rotate_logs_if_needed($log_file);
        
        // Set Australian Eastern timezone for logging
        $original_timezone = date_default_timezone_get();
        date_default_timezone_set('Australia/Melbourne');
        $timestamp = date('d/m/Y H:i:s'); // Australian format: DD/MM/YYYY
        date_default_timezone_set($original_timezone);
        
        $level_str = strtoupper($level);
        $formatted_message = "[{$timestamp}] [{$level_str}] " . $message . "\n";

        @file_put_contents($log_file, $formatted_message, FILE_APPEND | LOCK_EX);
        
        // v5.0.11-ADMIN-FIELD-MAPPING-FIX: More selective debug logging to reduce file size
        // Only log critical field mapping, errors, and milestones to debug log
        if (strpos($message, 'FIELD MAPPING') !== false || 
            strpos($message, 'MILESTONE') !== false || 
            strpos($message, 'TROUBLESHOOT') !== false || 
            strpos($message, 'ERROR') !== false ||
            strpos($message, 'USR_') !== false && strpos($message, '=') !== false) {
            
            $debug_file = self::get_debug_log_file_path();
            self::rotate_logs_if_needed($debug_file);
            
            // Use same Australian timestamp for debug log consistency
            $original_timezone = date_default_timezone_get();
            date_default_timezone_set('Australia/Melbourne');
            $debug_timestamp = date('d/m/Y H:i:s');
            date_default_timezone_set($original_timezone);
            
            $debug_message = "[{$debug_timestamp}] [DEBUG] " . $message . "\n";
            @file_put_contents($debug_file, $debug_message, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Logs an error message.
     *
     * @param string $message The error message to log.
     */
    public static function error($message) {
        self::log($message, 'ERROR');
    }
    
    /**
     * Logs an info message.
     *
     * @param string $message The info message to log.
     */
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    /**
     * Log essential field mapping debug info (for troubleshooting)
     */
    public static function debug_field_mapping($merge_data, $context = '') {
        $context_prefix = $context ? "[{$context}] " : "";
        
        // Log critical field mappings that are essential for troubleshooting
        $critical_fields = ['USR_Name', 'USR_ABN', 'USR_Business', 'PT2_Name', 'PT2_ABN'];
        foreach ($critical_fields as $field) {
            if (isset($merge_data[$field])) {
                self::log("v5.0.11-ADMIN-FIELD-MAPPING-FIX FIELD MAPPING: {$context_prefix}{$field} = " . $merge_data[$field]);
            } else {
                self::log("v5.0.11-ADMIN-FIELD-MAPPING-FIX FIELD MAPPING: {$context_prefix}{$field} = NOT SET");
            }
        }
    }
    
    /**
     * Log processing milestone (for tracking workflow)
     */
    public static function milestone($message) {
        self::log("v5.0.11-ADMIN-FIELD-MAPPING-FIX MILESTONE: " . $message);
    }
    
    /**
     * Log troubleshooting info when issues occur
     */
    public static function troubleshoot($issue, $data = null) {
        self::log("v5.0.11-ADMIN-FIELD-MAPPING-FIX TROUBLESHOOT: " . $issue);
        if ($data) {
            self::log("v5.0.11-ADMIN-FIELD-MAPPING-FIX TROUBLESHOOT DATA: " . (is_array($data) ? json_encode($data) : $data));
        }
    }
    
    /**
     * Get debug information for troubleshooting Activity Logs discrepancy
     */
    public static function getLogFileInfo() {
        $main_file = self::get_log_file_path();
        $debug_file = self::get_debug_log_file_path();
        
        $info = array(
            'main_log_path' => $main_file,
            'debug_log_path' => $debug_file,
            'main_log_exists' => file_exists($main_file),
            'debug_log_exists' => file_exists($debug_file),
            'main_log_size' => file_exists($main_file) ? filesize($main_file) : 0,
            'debug_log_size' => file_exists($debug_file) ? filesize($debug_file) : 0,
            'main_log_modified' => file_exists($main_file) ? date('Y-m-d H:i:s', filemtime($main_file)) : 'N/A',
            'debug_log_modified' => file_exists($debug_file) ? date('Y-m-d H:i:s', filemtime($debug_file)) : 'N/A'
        );
        
        return $info;
    }
    
    /**
     * Get the debug log content for troubleshooting
     */
    public static function get_debug_log_content() {
        $debug_file = self::get_debug_log_file_path();
        if (file_exists($debug_file)) {
            return file_get_contents($debug_file);
        }
        return "Debug log not found.";
    }
    
    /**
     * Get the debug log file path for user access
     */
    public static function get_debug_log_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/lda-logs/lda-debug-v5.log';
    }

    /**
     * Logs a warning message.
     *
     * @param string $message The warning message to log.
     */
    public static function warn($message) {
        self::log($message, 'WARN');
    }

    /**
     * Logs a warning message (alias for warn).
     *
     * @param string $message The warning message to log.
     */
    public static function warning($message) {
        self::log($message, 'WARN');
    }

    /**
     * Logs an array with truncation to avoid log file issues.
     *
     * @param string $label The label for the array.
     * @param array $array The array to log.
     * @param int $max_value_length Maximum length for array values.
     */
    public static function logArray($label, $array, $max_value_length = 50) {
        $summary = array();
        foreach ($array as $key => $value) {
            if (is_string($value) && strlen($value) > $max_value_length) {
                $summary[$key] = substr($value, 0, $max_value_length) . '...';
            } else {
                $summary[$key] = $value;
            }
        }
        self::log($label . " (" . count($array) . " items): " . json_encode($summary, JSON_PRETTY_PRINT));
    }

    /**
     * Logs a debug message.
     *
     * @param string $message The debug message to log.
     */
    public static function debug($message) {
        $settings = get_option('lda_settings', array());
        if (!empty($settings['debug_mode'])) {
            self::log($message, 'DEBUG');
        }
    }

    /**
     * Retrieves recent log entries.
     *
     * @return array An array of log entry strings.
     */
    /**
     * Gets recent logs including debug information for Activity Logs tab
     * @return array Combined logs from main and debug files
     */
    public static function getRecentLogs() {
        $main_logs = self::getFilteredLogs('', 7, 50);  // Get 50 from main log
        $debug_logs = self::getFilteredDebugLogs('', 7, 50);  // Get 50 from debug log
        
        // Combine and sort by timestamp
        $all_logs = array_merge($main_logs, $debug_logs);
        
        // Sort by timestamp (newest first)
        usort($all_logs, function($a, $b) {
            preg_match('/\[(.*?)\]/', $a, $matches_a);
            preg_match('/\[(.*?)\]/', $b, $matches_b);
            
            if (isset($matches_a[1]) && isset($matches_b[1])) {
                $timestamp_a = $matches_a[1];
                $timestamp_b = $matches_b[1];
                
                // Handle Australian DD/MM/YYYY HH:MM:SS format
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s(\d{2}):(\d{2}):(\d{2})/', $timestamp_a, $date_matches_a)) {
                    $us_format_a = $date_matches_a[2] . '/' . $date_matches_a[1] . '/' . $date_matches_a[3] . ' ' . $date_matches_a[4] . ':' . $date_matches_a[5] . ':' . $date_matches_a[6];
                    $time_a = strtotime($us_format_a);
                } else {
                    $time_a = strtotime($timestamp_a);
                }
                
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s(\d{2}):(\d{2}):(\d{2})/', $timestamp_b, $date_matches_b)) {
                    $us_format_b = $date_matches_b[2] . '/' . $date_matches_b[1] . '/' . $date_matches_b[3] . ' ' . $date_matches_b[4] . ':' . $date_matches_b[5] . ':' . $date_matches_b[6];
                    $time_b = strtotime($us_format_b);
                } else {
                    $time_b = strtotime($timestamp_b);
                }
                
                return $time_b - $time_a; // Newest first
            }
            return 0;
        });
        
        return array_slice($all_logs, 0, 100); // Return top 100
    }
    
    /**
     * Get filtered logs from debug file 
     */
    public static function getFilteredDebugLogs($level = '', $days = 7, $limit = 50) {
        $debug_file = self::get_debug_log_file_path();

        if (!file_exists($debug_file)) {
            return array();
        }

        $logs = file($debug_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($logs)) {
            return array();
        }

        $filtered_logs = array();
        $cutoff_timestamp = time() - ($days * 24 * 60 * 60);

        foreach (array_reverse($logs) as $log_entry) {
            if (count($filtered_logs) >= $limit) {
                break;
            }

            // Skip session headers and empty lines
            if (strpos($log_entry, '===') !== false || strpos($log_entry, '---') !== false || empty(trim($log_entry))) {
                continue;
            }

            preg_match('/\[(.*?)\]/', $log_entry, $matches);
            
            if (count($matches) >= 2) {
                $log_timestamp = strtotime($matches[1]);

                if ($log_timestamp >= $cutoff_timestamp) {
                    // Format debug entry to look like main log entry
                    $formatted_entry = $log_entry;
                    if (!preg_match('/\[(.*?)\]\s\[(.*?)\]/', $log_entry)) {
                        // Add DEBUG level if not present
                        $formatted_entry = preg_replace('/(\[.*?\])\s/', '$1 [DEBUG] ', $log_entry);
                    }
                    $filtered_logs[] = $formatted_entry;
                }
            }
        }
        
        return $filtered_logs;
    }

    /**
     * Retrieves filtered log entries.
     *
     * @param string $level The log level to filter by (e.g., 'ERROR', 'INFO').
     * @param int $days The number of days of logs to retrieve.
     * @param int $limit The maximum number of log entries to return.
     * @return array An array of log entry strings.
     */
    public static function getFilteredLogs($level = '', $days = 7, $limit = 100) {
        $log_file = self::get_log_file_path();

        if (!file_exists($log_file)) {
            return array('Log file not found.');
        }

        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($logs)) {
            return array();
        }

        $filtered_logs = array();
        $cutoff_timestamp = time() - ($days * 24 * 60 * 60);

        foreach (array_reverse($logs) as $log_entry) {
            if (count($filtered_logs) >= $limit) {
                break;
            }

            preg_match('/\[(.*?)\]\s\[(.*?)\]/', $log_entry, $matches);
            
            if (count($matches) === 3) {
                $timestamp_str = $matches[1];
                $log_level = $matches[2];
                
                // Handle Australian DD/MM/YYYY HH:MM:SS format
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s(\d{2}):(\d{2}):(\d{2})/', $timestamp_str, $date_matches)) {
                    // Convert DD/MM/YYYY to MM/DD/YYYY for strtotime
                    $us_format = $date_matches[2] . '/' . $date_matches[1] . '/' . $date_matches[3] . ' ' . $date_matches[4] . ':' . $date_matches[5] . ':' . $date_matches[6];
                    $log_timestamp = strtotime($us_format);
                } else {
                    // Fallback to original parsing for old format logs
                    $log_timestamp = strtotime($timestamp_str);
                }

                if ($log_timestamp && $log_timestamp >= $cutoff_timestamp) {
                    if (empty($level) || $log_level === $level) {
                        $filtered_logs[] = $log_entry;
                    }
                }
            }
        }
        
        return $filtered_logs;
    }

    /**
     * Retrieves statistics about the logs.
     *
     * @return array An array of log statistics.
     */
    public static function getLogStats() {
        $log_file = self::get_log_file_path();

        if (!file_exists($log_file) || !is_readable($log_file)) {
            return array(
                'total_entries' => 0,
                'error_count' => 0,
                'warning_count' => 0,
                'latest_error' => null, // Changed from string to null for consistency
            );
        }

        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $lines = array();
        }

        $stats = array(
            'total_entries' => count($lines),
            'error_count' => 0,
            'warning_count' => 0,
            'latest_error' => null,
        );

        $latest_error_timestamp = 0;

        foreach ($lines as $line) {
            if (strpos($line, '[ERROR]') !== false) {
                $stats['error_count']++;

                // Parse log format: [timestamp] [LEVEL] message
                preg_match('/^\[([^\]]+)\]\s*\[([^\]]+)\]\s*(.*)$/', $line, $matches);
                if (isset($matches[1])) {
                    $timestamp = strtotime($matches[1]);
                    if ($timestamp > $latest_error_timestamp) {
                        $latest_error_timestamp = $timestamp;
                        // Store error info as array with timestamp and message
                        $stats['latest_error'] = array(
                            'timestamp' => $matches[1],
                            'message' => isset($matches[3]) ? trim($matches[3]) : $line
                        );
                    }
                }
            }
            if (strpos($line, '[WARNING]') !== false) {
                $stats['warning_count']++;
            }
        }

        return $stats;
    }

    /**
     * Cleans up old log entries.
     *
     * @param int $days_to_keep The number of days to keep log entries for.
     */
    public static function cleanOldLogs($days_to_keep = 30) {
        $log_file = self::get_log_file_path();

        if (!file_exists($log_file) || !is_readable($log_file) || !is_writable($log_file)) {
            return;
        }

        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return;
        }

        $cutoff_timestamp = time() - ($days_to_keep * 24 * 60 * 60);
        $fresh_logs = array();

        foreach ($lines as $line) {
            preg_match('/\[(.*?)\]/', $line, $matches);
            if (isset($matches[1])) {
                $log_timestamp = strtotime($matches[1]);
                if ($log_timestamp >= $cutoff_timestamp) {
                    $fresh_logs[] = $line . "\n";
                }
            }
        }

        file_put_contents($log_file, implode('', $fresh_logs));
    }

    /**
     * Clears the entire log file.
     *
     * @return bool True on success, false on failure.
     */
    public static function clearLogs() {
        $log_file = self::get_log_file_path();
        if (file_exists($log_file) && is_writable($log_file)) {
            if (file_put_contents($log_file, '') !== false) {
                // Add a new entry to say the log was cleared.
                self::log('Log file cleared.');
                return true;
            }
        }
        return false;
    }
    
    /**
     * Completely removes ALL log files and directories (for uninstall)
     *
     * @return bool True on success, false on failure.
     */
    public static function deleteAllLogs() {
        $success = true;
        
        // Get all log file paths
        $main_log = self::get_log_file_path();
        $debug_log = self::get_debug_log_file_path();
        
        // Delete main log file
        if (file_exists($main_log)) {
            if (!unlink($main_log)) {
                $success = false;
                error_log('LDA: Failed to delete main log file: ' . $main_log);
            }
        }
        
        // Delete debug log file
        if (file_exists($debug_log)) {
            if (!unlink($debug_log)) {
                $success = false;
                error_log('LDA: Failed to delete debug log file: ' . $debug_log);
            }
        }
        
        // Remove log directory if empty
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/lda-logs/';
        
        if (is_dir($log_dir)) {
            // Check if directory is empty
            $files = scandir($log_dir);
            $files = array_diff($files, array('.', '..'));
            
            if (empty($files)) {
                if (!rmdir($log_dir)) {
                    $success = false;
                    error_log('LDA: Failed to remove log directory: ' . $log_dir);
                }
            }
        }
        
        return $success;
    }
}
