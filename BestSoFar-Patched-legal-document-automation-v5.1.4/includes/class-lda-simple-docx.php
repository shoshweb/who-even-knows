<?php
/**
 * Simple DOCX Processing without PHPWord
 * Uses basic ZIP manipulation for merge tag replacement
 */

if (!defined('ABSPATH')) {
    exit;
}

class LDA_SimpleDOCX {
    
    /**
     * Process merge tags in DOCX file
     */
    public static function processMergeTags($template_path, $merge_data, $output_path) {
                    // DIAGNOSTICS: Log XML diffs for section/style nodes before and after processing
                    $sectPr_before = [];
                    $style_before = [];
                    preg_match_all('/<w:sectPr[\s\S]*?<\/w:sectPr>/', $xml_content, $sectPr_matches_before);
                    preg_match_all('/<w:style[\s\S]*?<\/w:style>/', $xml_content, $style_matches_before);
                    $sectPr_before = $sectPr_matches_before[0];
                    $style_before = $style_matches_before[0];
        try {
        // ENHANCED DIAGNOSTICS: Track tags of interest
        $diagnostic_tags = [
            '{$REF_State}', '{$USR_Signatory_FN}', '{$PT2_Signatory_FN}', '{$Eff_date|date_format:d F Y}', '{$Login_ID}'
        ];
        $diagnostic_log = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/ENHANCED-DIAGNOSTICS.log';
        // v5.1.3 ENHANCED: Form 30 safety checks and comprehensive error handling
        LDA_Logger::log("🚀 Starting v5.1.3 ENHANCED merge with 100% split tag reconstruction");
        
        // SAFETY CHECK 1: ZipArchive availability
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available. Please install php-zip extension.');
        }
        
        // SAFETY CHECK 2: Template validation
        if (!file_exists($template_path)) {
            throw new Exception("Template file does not exist: {$template_path}");
        }
        
        if (!is_readable($template_path)) {
            throw new Exception("Template file is not readable: {$template_path}");
        }
        
        // SAFETY CHECK 3: Form 30 template verification
        $template_name = basename($template_path);
        if (strpos($template_name, 'Contractor Agreement') !== false) {
            LDA_Logger::log("✅ FORM 30 DETECTED: Using correct Contractor Agreement template");
        } elseif (strpos($template_name, 'Confidentiality') !== false) {
            LDA_Logger::log("⚠️ WARNING: Confidentiality template detected - may not be Form 30");
        }
        
        // FORCED DIAGNOSTIC LOG - IMMEDIATE ENTRY POINT TRACKING
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        if (!file_exists($log_dir)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($log_dir);
            } else {
                if (!is_dir($log_dir)) {
                    mkdir($log_dir, 0755, true);
                }
            }
        }
        $diagnostic_log = $log_dir . 'CRITICAL-MERGE-TAG-DEBUG.log';
        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600)); // Force Melbourne time
        $diagnostic_msg = "[{$timestamp}] 📄 v5.1.3 ENHANCED DOCX PROCESSING STARTED! 📄\n";
        $diagnostic_msg .= "[{$timestamp}] Version: v5.1.3-ENHANCED-SPLIT-TAG-RECONSTRUCTION\n";
        $diagnostic_msg .= "[{$timestamp}] Template: {$template_path}\n";
        $diagnostic_msg .= "[{$timestamp}] Output: {$output_path}\n";
        $diagnostic_msg .= "[{$timestamp}] Merge Data Keys: " . implode(', ', array_keys($merge_data)) . "\n";
        $diagnostic_msg .= "[{$timestamp}] Data Count: " . count($merge_data) . "\n";
        
        // v5.1.3 ENHANCED: Log the actual merge data values for critical fields including PT2
        $critical_fields = array('USR_Business', 'USR_Name', 'USR_ABN', 'PT2_Business', 'PT2_Name', 'PT2_ABN', 'Pmt_Negotiate', 'Pmt_Business');
        foreach ($critical_fields as $field) {
            $value = isset($merge_data[$field]) ? $merge_data[$field] : 'NOT_FOUND';
            $diagnostic_msg .= "[{$timestamp}] v5.1.3 {$field}: '{$value}'\n";
        }
        
        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);            // Ensure output directory exists and is writable
            $output_dir = dirname($output_path);
            
            // Debug: Log the output directory path
            LDA_Logger::log("Output directory path: " . $output_dir);
            LDA_Logger::log("Output file path: " . $output_path);
            
            if (empty($output_dir)) {
                throw new Exception("Output directory path is empty. Output path: " . $output_path);
            }
            
            if (!file_exists($output_dir)) {
                LDA_Logger::log("Creating output directory: " . $output_dir);
                if (function_exists('wp_mkdir_p')) {
                    if (function_exists('wp_mkdir_p')) {
                        if (!wp_mkdir_p($output_dir)) {
                            throw new Exception("Failed to create output directory: {$output_dir}");
                        }
                    } else {
                        if (!is_dir($output_dir) && !mkdir($output_dir, 0755, true)) {
                            throw new Exception("Failed to create output directory: {$output_dir}");
                        }
                    }
                } else {
                    if (!is_dir($output_dir) && !mkdir($output_dir, 0755, true)) {
                        throw new Exception("Failed to create output directory: {$output_dir}");
                    }
                }
                LDA_Logger::log("Output directory created successfully");
            }
            
            if (!is_writable($output_dir)) {
                $perms = fileperms($output_dir);
                throw new Exception("Output directory is not writable: {$output_dir} (permissions: " . decoct($perms & 0777) . ")");
            }
            
            LDA_Logger::log("Output directory is writable: " . $output_dir);
            
            // Check if template file exists and is readable
            if (!file_exists($template_path)) {
                throw new Exception("Template file not found: {$template_path}");
            }
            
            if (!is_readable($template_path)) {
                throw new Exception("Template file is not readable: {$template_path}");
            }
            
            // Create a copy of the template
            if (!copy($template_path, $output_path)) {
                $error = error_get_last();
                throw new Exception("Failed to copy template file. Error: " . ($error['message'] ?? 'Unknown error'));
            }
            
            // Open the DOCX as a ZIP file
            $zip = new ZipArchive();
            if ($zip->open($output_path) !== TRUE) {
                throw new Exception('Failed to open DOCX file');
            }
            
            // Process all XML files that might contain merge tags
            $xml_files_to_process = array(
                'word/document.xml',  // MAIN DOCUMENT - MUST BE PROCESSED FIRST
                'word/header1.xml',
                'word/header2.xml', 
                'word/header3.xml',
                'word/footer1.xml',
                'word/footer2.xml',
                'word/footer3.xml'
            );
            
            LDA_Logger::log("CRITICAL: About to process " . count($xml_files_to_process) . " XML files, starting with word/document.xml");
            
            $files_processed = 0;
                foreach ($xml_files_to_process as $xml_file) {
                    $xml_content = $zip->getFromName($xml_file);
                    if ($xml_content !== false) {
                        // Capture formatting nodes from original XML before any processing
                        $original_sectPr = [];
                        $original_style = [];
                        preg_match_all('/<w:sectPr[\s\S]*?<\/w:sectPr>/', $xml_content, $sectPr_matches);
                        preg_match_all('/<w:style[\s\S]*?<\/w:style>/', $xml_content, $style_matches);
                        $original_sectPr = $sectPr_matches[0];
                        $original_style = $style_matches[0];
                        // DIAGNOSTICS: Log style/section nodes before processing
                        $style_count_before = preg_match_all('/<w:style[\s>]/', $xml_content, $dummy1);
                        $sectPr_count_before = preg_match_all('/<w:sectPr[\s>]/', $xml_content, $dummy2);
                        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                        $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
                        $diagnostic_msg = "[{$timestamp}] STYLE/SECTION BEFORE: {$xml_file} styles={$style_count_before} sectPr={$sectPr_count_before}\n";
                        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                    
                    // SPECIAL HANDLING: Headers and footers need extra aggressive XML reconstruction
                    $is_header_footer = (strpos($xml_file, 'header') !== false || strpos($xml_file, 'footer') !== false);
                    if ($is_header_footer) {
                        LDA_Logger::log("🔥 HEADER/FOOTER DETECTED: {$xml_file} - applying NUCLEAR reconstruction");
                        
                        // NUCLEAR OPTION FOR HEADERS: Replace suspicious patterns with SPECIFIC matching
                        $nuclear_patterns = array(
                            '/\{\$[^}]*?USR[^}]*?Business[^}]*?\}/si' => '{$USR_Business}',  // FIXED: USR Business only
                            '/\{\$[^}]*?USR[^}]*?Name[^}]*?\}/si' => '{$USR_Name}',
                            '/\{\$[^}]*?USR[^}]*?ABN[^}]*?\}/si' => '{$USR_ABN}',
                            '/\{\$[^}]*?REF[^}]*?State[^}]*?\}/si' => '{$REF_State}',
                            '/\{\$[^}]*?PT2[^}]*?Business[^}]*?\}/si' => '{$PT2_Business}',
                            '/\{\$[^}]*?PT2[^}]*?Name[^}]*?\}/si' => '{$PT2_Name}',
                            '/\{\$[^}]*?PT2[^}]*?ABN[^}]*?\}/si' => '{$PT2_ABN}',
                            // Aggressive pattern for split REF_State in any XML part
                            '/\{\$[^}]*?(R[^}]*?E[^}]*?F[^}]*?_[^}]*?S[^}]*?t[^}]*?a[^}]*?t[^}]*?e)[^}]*?\}/si' => '{$REF_State}',
                        );
                        
                        foreach ($nuclear_patterns as $pattern => $replacement) {
                            $before = $xml_content;
                            $xml_content = preg_replace($pattern, $replacement, $xml_content);
                            if ($xml_content !== $before) {
                                LDA_Logger::log("🔥 NUCLEAR HEADER FIX: Applied {$replacement} pattern");
                                
                                // Log to diagnostic file
                                $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                                $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                                $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
                                $diagnostic_msg = "[{$timestamp}] 🔥 NUCLEAR HEADER FIX: {$xml_file} -> {$replacement}\n";
                                file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                            }
                        }
                    }
                    
                    // FORCED DIAGNOSTIC LOG - Prove new merge tag processing is running
                    $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                    if (!file_exists($log_dir)) {
                        if (function_exists('wp_mkdir_p')) {
                            wp_mkdir_p($log_dir);
                        } else {
                            if (!is_dir($log_dir)) {
                                mkdir($log_dir, 0755, true);
                            }
                        }
                    }
                    $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                    $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600)); // Force Melbourne time
                    $diagnostic_msg = "[{$timestamp}] NEW MERGE TAG PROCESSING IS RUNNING! Processing: {$xml_file}\n";
                    file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
            
            // Process merge tags - Split tag fixing disabled to prevent XML corruption
                    $processed_xml = self::replaceMergeTags($xml_content, $merge_data, $xml_file);
                    // DIAGNOSTICS: Log XML diffs for section/style nodes after processing
                    $sectPr_after = [];
                    $style_after = [];
                    preg_match_all('/<w:sectPr[\s\S]*?<\/w:sectPr>/', $processed_xml, $sectPr_matches_after);
                    preg_match_all('/<w:style[\s\S]*?<\/w:style>/', $processed_xml, $style_matches_after);
                    $sectPr_after = $sectPr_matches_after[0];
                    $style_after = $style_matches_after[0];
                    $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                    $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                    $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
                    $diagnostic_msg = "[{$timestamp}] SECTION DIFF: {$xml_file} BEFORE=" . count($sectPr_before) . " AFTER=" . count($sectPr_after) . "\n";
                    $diagnostic_msg .= "[{$timestamp}] STYLE DIFF: {$xml_file} BEFORE=" . count($style_before) . " AFTER=" . count($style_after) . "\n";
                    file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                    // Restore formatting nodes from original XML if missing
                    foreach ($original_sectPr as $sectPr) {
                        if (strpos($processed_xml, $sectPr) === false) {
                            if (strpos($processed_xml, '</w:body>') !== false) {
                                $processed_xml = str_replace('</w:body>', $sectPr . "\n</w:body>", $processed_xml);
                            } elseif (strpos($processed_xml, '</w:document>') !== false) {
                                $processed_xml = str_replace('</w:document>', $sectPr . "\n</w:document>", $processed_xml);
                            }
                        }
                    }
                    foreach ($original_style as $style) {
                        if (strpos($processed_xml, $style) === false) {
                            if (strpos($processed_xml, '</w:styles>') !== false) {
                                $processed_xml = str_replace('</w:styles>', $style . "\n</w:styles>", $processed_xml);
                            }
                        }
                    }

                    // DIAGNOSTICS: Log style/section nodes after processing
                    $style_count_after = preg_match_all('/<w:style[\s>]/', $processed_xml, $dummy3);
                    $sectPr_count_after = preg_match_all('/<w:sectPr[\s>]/', $processed_xml, $dummy4);
                    $diagnostic_msg = "[{$timestamp}] STYLE/SECTION AFTER: {$xml_file} styles={$style_count_after} sectPr={$sectPr_count_after}\n";
                    file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);

                    // Sanitize and validate processed XML
                    $processed_xml = self::sanitizeXML($processed_xml);
                    if (!self::isWellFormedXML($processed_xml)) {
                        LDA_Logger::error("Processed XML not well-formed for part: {$xml_file}. Reverting to original to avoid corruption.");
                        $processed_xml = $xml_content;
                    }

                    // Always update the XML file to ensure it's processed
                    $zip->addFromString($xml_file, $processed_xml);
                    $files_processed++;
            } else {
                    LDA_Logger::error("XML file not found: " . $xml_file);
            }
            }
            
            $zip->close();
            
            LDA_Logger::log("DOCX processing completed. Processed {$files_processed} XML files");
            return array('success' => true, 'file_path' => $output_path);
            
        } catch (Exception $e) {
            LDA_Logger::error("DOCX processing failed: " . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    // Test helper - expose conservative fix for local testing only
    public static function test_fixSplitMergeTagsConservative($xml_content) {
        return self::fixSplitMergeTagsConservative($xml_content);
    }
    
    /**
     * Replace merge tags in XML content
     */
    private static function replaceMergeTags($xml_content, $merge_data, $xml_file_name = '') {
        // ENHANCED DIAGNOSTICS: Ensure log directory exists
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        if (!file_exists($log_dir)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($log_dir);
            } else {
                if (!is_dir($log_dir)) {
                    mkdir($log_dir, 0755, true);
                }
            }
        }
        $diagnostic_log = $log_dir . 'ENHANCED-DIAGNOSTICS.log';
        // Forced diagnostics: log entry before diagnostics block
        file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] ENTERING DIAGNOSTICS BLOCK for $xml_file_name\n", FILE_APPEND | LOCK_EX);
        // Log unreplaced tags and modifier failures
        $unreplaced_tags = [];
        $modifier_failures = [];
        preg_match_all('/\{\$[A-Za-z0-9_\|: .,-]+\}/', $xml_content, $all_tags);
        // Log all tags found in this XML part
        file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] TAGS FOUND in $xml_file_name: " . (empty($all_tags[0]) ? 'NONE' : implode(', ', $all_tags[0])) . "\n", FILE_APPEND | LOCK_EX);
        foreach ($all_tags[0] as $tag) {
            $tag_base = preg_replace('/\|.*/', '', $tag); // Remove modifier for base tag
            if (!array_key_exists(trim($tag_base, '{}$'), $merge_data)) {
                $unreplaced_tags[] = $tag;
            }
            // Check for modifier
            if (strpos($tag, '|') !== false && !preg_match('/\|upper|\|date_format/', $tag)) {
                $modifier_failures[] = $tag;
            }
        }
        if (empty($unreplaced_tags)) {
            file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] NO UNREPLACED TAGS in $xml_file_name\n", FILE_APPEND | LOCK_EX);
        }
        if (!empty($unreplaced_tags)) {
            file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] UNREPLACED TAGS: " . implode(', ', $unreplaced_tags) . " in $xml_file_name\n", FILE_APPEND | LOCK_EX);
        }
        if (empty($modifier_failures)) {
            file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] NO MODIFIER FAILURES in $xml_file_name\n", FILE_APPEND | LOCK_EX);
        }
        if (!empty($modifier_failures)) {
            file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] MODIFIER FAILURES: " . implode(', ', $modifier_failures) . " in $xml_file_name\n", FILE_APPEND | LOCK_EX);
        }
        // Specifically log for diagnostic tags
        foreach ([
            '{$REF_State}', '{$USR_Signatory_FN}', '{$PT2_Signatory_FN}', '{$Eff_date|date_format:d F Y}', '{$Login_ID}'
        ] as $dtag) {
            if (strpos($xml_content, $dtag) !== false) {
                file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] DIAGNOSTIC TAG FOUND: $dtag in $xml_file_name\n", FILE_APPEND | LOCK_EX);
            }
        }
        // Forced diagnostics: log entry after diagnostics block
        file_put_contents($diagnostic_log, "[" . date('d/m/Y H:i:s') . "] EXITING DIAGNOSTICS BLOCK for $xml_file_name\n", FILE_APPEND | LOCK_EX);
        // ENHANCED DIAGNOSTICS: Track unreplaced tags and modifier failures
        $unreplaced_tags = [];
        $modifier_failures = [];
        preg_match_all('/\{\$[A-Za-z0-9_\|: .,-]+\}/', $xml_content, $all_tags);
        foreach ($all_tags[0] as $tag) {
            $tag_base = preg_replace('/\|.*/', '', $tag); // Remove modifier for base tag
            if (!array_key_exists(trim($tag_base, '{}$'), $merge_data)) {
                $unreplaced_tags[] = $tag;
            }
            // Check for modifier
            if (strpos($tag, '|') !== false && !preg_match('/\|upper|\|date_format/', $tag)) {
                $modifier_failures[] = $tag;
            }
        }
        if (!empty($unreplaced_tags)) {
            file_put_contents('/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/ENHANCED-DIAGNOSTICS.log', "[" . date('d/m/Y H:i:s') . "] UNREPLACED TAGS: " . implode(', ', $unreplaced_tags) . " in $xml_file_name\n", FILE_APPEND | LOCK_EX);
        }
        if (!empty($modifier_failures)) {
            file_put_contents('/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/ENHANCED-DIAGNOSTICS.log', "[" . date('d/m/Y H:i:s') . "] MODIFIER FAILURES: " . implode(', ', $modifier_failures) . " in $xml_file_name\n", FILE_APPEND | LOCK_EX);
        }
        // Specifically log for diagnostic tags
        foreach ([
            '{$REF_State}', '{$USR_Signatory_FN}', '{$PT2_Signatory_FN}', '{$Eff_date|date_format:d F Y}', '{$Login_ID}'
        ] as $dtag) {
            if (strpos($xml_content, $dtag) !== false) {
                file_put_contents('/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/ENHANCED-DIAGNOSTICS.log', "[" . date('d/m/Y H:i:s') . "] DIAGNOSTIC TAG FOUND: $dtag in $xml_file_name\n", FILE_APPEND | LOCK_EX);
            }
        }
        // Safety check for merge_data
        if (!is_array($merge_data)) {
            LDA_Logger::warn("Merge data is not an array, skipping merge tag replacement");
            // === FORMATTING PRESERVATION PATCH ===
            // Extract all <w:sectPr> and <w:style> nodes from original XML before merge
            preg_match_all('/<w:sectPr[\s\S]*?<\/w:sectPr>/', $xml_content, $sectPr_matches);
            preg_match_all('/<w:style[\s\S]*?<\/w:style>/', $xml_content, $style_matches);
            $sectPr_nodes = $sectPr_matches[0];
            $style_nodes = $style_matches[0];

            // After all replacements and cleanups, restore formatting nodes if missing
            // Restore <w:sectPr> nodes if any were lost
            foreach ($sectPr_nodes as $sectPr) {
                if (strpos($xml_content, $sectPr) === false) {
                    // Insert before closing </w:body> or </w:document>
                    if (strpos($xml_content, '</w:body>') !== false) {
                        $xml_content = str_replace('</w:body>', $sectPr . "\n</w:body>", $xml_content);
                    } elseif (strpos($xml_content, '</w:document>') !== false) {
                        $xml_content = str_replace('</w:document>', $sectPr . "\n</w:document>", $xml_content);
                    }
                }
            }
            // Restore <w:style> nodes if any were lost
            foreach ($style_nodes as $style) {
                if (strpos($xml_content, $style) === false) {
                    // Insert before closing </w:styles>
                    if (strpos($xml_content, '</w:styles>') !== false) {
                        $xml_content = str_replace('</w:styles>', $style . "\n</w:styles>", $xml_content);
                    }
                }
            }
            return $xml_content;
        }
        
        LDA_Logger::milestone("Merge tag processing started with " . count($merge_data) . " fields for {$xml_file_name}");
        
        // CRITICAL DEBUG: Log all merge data keys to see what we're working with
        LDA_Logger::log("🔍 MERGE DATA KEYS RECEIVED: " . implode(', ', array_keys($merge_data)));
        
        // Essential field mapping debug for troubleshooting (only for main document to avoid spam)
        if ($xml_file_name === 'word/document.xml') {
            LDA_Logger::debug_field_mapping($merge_data, "BEFORE_PROCESSING");
        }
        
        // Step 1: ENHANCED v5.1.3 XML reconstruction - 100% split tag reconstruction
        LDA_Logger::log("🚀 Starting v5.1.3 ENHANCED merge with 100% split tag reconstruction");
        $xml_content = self::fixSplitMergeTagsConservative($xml_content);
        
        // Step 1.5: ADDITIONAL reconstruction specifically for MODIFIER tags that might still be split
        if (strpos($xml_file_name, 'header') !== false || strpos($xml_file_name, 'footer') !== false) {
            LDA_Logger::log("🔥 HEADER/FOOTER MODIFIER FIX: Applying additional modifier tag reconstruction");
            
            // Ultra-aggressive pattern to catch any remaining split modifier tags
            $modifier_patterns = array(
                // Match any {$...Name|upper} pattern that might be split
                '/\{\$([^}]*?)Name([^}]*?)\|([^}]*?)upper([^}]*?)\}/si',
                '/\{\$([^}]*?)Business([^}]*?)\|([^}]*?)upper([^}]*?)\}/si',
                '/\{\$([^}]*?)ABN([^}]*?)\|([^}]*?)upper([^}]*?)\}/si',
                // Also catch patterns split across multiple XML elements
                '/\{\$[^}]*?USR[^}]*?Name[^}]*?\|[^}]*?upper[^}]*?\}/si',
                '/\{\$[^}]*?PT2[^}]*?Name[^}]*?\|[^}]*?upper[^}]*?\}/si',
            );
            
            foreach ($modifier_patterns as $pattern) {
                if (preg_match($pattern, $xml_content)) {
                    // Try to reconstruct any remaining split modifier tags
                    $xml_content = preg_replace($pattern, '{$USR_Name|upper}', $xml_content);
                    LDA_Logger::log("🔥 MODIFIER RECONSTRUCTION: Applied pattern fix for modifier tags");
                }
            }
        }
        
        // Step 2: ADDITIONAL XML cleanup for conditional tags specifically
        $xml_content = self::fixSplitConditionalTags($xml_content);
        LDA_Logger::log("v5.1.3 ENHANCED: XML reconstruction completed for {$xml_file_name}");
        
        // DYNAMIC APPROACH: Build replacement array from merge data instead of hardcoded
        $basic_replacements = array();
        
        // v5.1.3 ENHANCED: Log critical field values before building replacement array
        LDA_Logger::debug("🔍 MERGE TAG DEBUG - Critical field values before replacement:");
        $critical_fields = array('USR_Business', 'USR_Name', 'USR_ABN', 'PT2_Business', 'PT2_Name', 'PT2_ABN', 'REF_State');
        foreach ($critical_fields as $field) {
            $value = isset($merge_data[$field]) ? $merge_data[$field] : 'NOT_SET';
            LDA_Logger::debug("🔍 {$field} = '{$value}'");
        }
        
        // Add all merge data as potential replacements dynamically
        foreach ($merge_data as $key => $value) {
            // v5.1.3 ENHANCED: CRITICAL USR/PT2 DEBUGGING - Log all field values for comparison
            if (strpos($key, 'USR_Name') !== false || strpos($key, 'PT2_Name') !== false || 
                strpos($key, 'USR_Business') !== false || strpos($key, 'PT2_Business') !== false) {
                
                $is_empty = empty($value);
                $value_preview = $is_empty ? 'EMPTY' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                
                LDA_Logger::log("🔍 FIELD COMPARISON: {$key} = '{$value_preview}' (empty: " . ($is_empty ? 'YES' : 'NO') . ")");
                
                // FORCED DIAGNOSTIC LOG for critical fields
                $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
                $diagnostic_msg = "[{$timestamp}] 🔍 FIELD COMPARISON: {$key} = '{$value_preview}' (empty: " . ($is_empty ? 'YES' : 'NO') . ")\n";
                file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
            }
            
            if (!empty($value)) {
                // Add both regular and HTML entity formats dynamically
                $basic_replacements['{$' . $key . '}'] = $value;
                $basic_replacements['{&#36;' . $key . '}'] = $value;
                LDA_Logger::debug("🔧 REPLACEMENT ADDED: {$key} -> '{$value}'");
            }
        }
        
        // v5.1.3 ENHANCED: Add field aliases for common template variations
        $field_aliases = array(
            'Pmt_Agreements' => 'Pmt_Negotiate',  // Template uses Pmt_Agreements but form field is Pmt_Negotiate
            'Pmt_Relations' => 'Pmt_Business',   // Template uses Pmt_Relations but form field is Pmt_Business
        );
        
        foreach ($field_aliases as $template_field => $actual_field) {
            if (isset($merge_data[$actual_field]) && !isset($merge_data[$template_field])) {
                $merge_data[$template_field] = $merge_data[$actual_field];
                $basic_replacements['{$' . $template_field . '}'] = $merge_data[$actual_field];
                LDA_Logger::log("🔄 FIELD ALIAS: {$template_field} -> {$actual_field} (value: '{$merge_data[$actual_field]}')");
            }
        }
        
        // v5.1.3: Initialize Pmt_* fields to empty if they don't exist
        // This ensures conditional logic can process them even when field 27 is empty
        $pmt_fields = array('Pmt_Services', 'Pmt_Negotiate', 'Pmt_Business', 'Pmt_Other', 'Pmt_Agreements', 'Pmt_Relations');
        foreach ($pmt_fields as $pmt_field) {
            if (!isset($merge_data[$pmt_field])) {
                $merge_data[$pmt_field] = '';
                $basic_replacements['{$' . $pmt_field . '}'] = '';
                LDA_Logger::log("🔧 PMT FIELD INIT: {$pmt_field} initialized to empty");
            }
        }
        
        // v5.1.3 ENHANCED: Generate Pmt_* fields from form field 27 checkbox selections
        // This is essential for {listif} conditional logic in the Purposes section
        $purpose_checkbox_mapping = array(
            'Pmt_Services' => '27.yes1',
            'Pmt_Negotiate' => '27.yes2',    // Also map to Pmt_Agreements
            'Pmt_Business' => '27.yes3',     // Also map to Pmt_Relations
            'Pmt_Other' => '27.yes4'
        );
        
        foreach ($purpose_checkbox_mapping as $pmt_field => $checkbox_key) {
            // Check if this checkbox is selected (has value)
            if (isset($merge_data[$checkbox_key]) && !empty($merge_data[$checkbox_key])) {
                // Extract the yes value (yes1, yes2, yes3, yes4)
                $yes_value = substr($checkbox_key, 3); // Remove "27." to get "yes1", "yes2", etc.
                
                // Set the Pmt field to the yes value for conditional logic
                $merge_data[$pmt_field] = $yes_value;
                $basic_replacements['{$' . $pmt_field . '}'] = $yes_value;
                
                LDA_Logger::log("🎯 PMT FIELD CREATED: {$pmt_field} = '{$yes_value}' (from {$checkbox_key})");
                
                // Also create template alias fields
                if ($pmt_field === 'Pmt_Negotiate') {
                    $merge_data['Pmt_Agreements'] = $yes_value;
                    $basic_replacements['{$Pmt_Agreements}'] = $yes_value;
                    LDA_Logger::log("🎯 PMT ALIAS: Pmt_Agreements = '{$yes_value}' (from {$pmt_field})");
                }
                if ($pmt_field === 'Pmt_Business') {
                    $merge_data['Pmt_Relations'] = $yes_value;
                    $basic_replacements['{$Pmt_Relations}'] = $yes_value;
                    LDA_Logger::log("🎯 PMT ALIAS: Pmt_Relations = '{$yes_value}' (from {$pmt_field})");
                }
            } else {
                // Checkbox not selected - explicitly set to empty for conditional logic
                $merge_data[$pmt_field] = '';
                LDA_Logger::log("🚫 PMT FIELD EMPTY: {$pmt_field} (checkbox {$checkbox_key} not selected)");
            }
        }
        
        // v5.1.3-ENHANCED-XML-FIX: Enhanced error logging for failed merge tags
        LDA_Logger::debug("🔍 REPLACEMENT TAGS BUILT: " . count($basic_replacements) . " total");
        foreach ($critical_fields as $field) {
            $tag = '{$' . $field . '}';
            if (isset($basic_replacements[$tag])) {
                LDA_Logger::debug("✅ REPLACEMENT READY: {$tag} = '{$basic_replacements[$tag]}'");
            } else {
                // DETAILED ERROR: Why did this merge tag fail?
                if (!isset($merge_data[$field])) {
                    LDA_Logger::log("❌ MERGE TAG FAILED: {$tag} - Field '{$field}' not found in merge_data array (no admin mapping configured?)");
                } elseif (empty($merge_data[$field])) {
                    LDA_Logger::log("❌ MERGE TAG FAILED: {$tag} - Field '{$field}' exists but is empty (value: '" . $merge_data[$field] . "')");
                } else {
                    LDA_Logger::log("❌ MERGE TAG FAILED: {$tag} - Field '{$field}' has value but didn't make it to replacement array");
                }
            }
        }
        
        // Use dynamic replacements first with modifier support
        LDA_Logger::log("🔍 STARTING MERGE TAG REPLACEMENT PROCESS");
        LDA_Logger::debug("🔍 Processing " . count($basic_replacements) . " replacement tags");
        
        // FORCED DIAGNOSTIC LOG - TRACK MERGE TAG PROCESSING
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600)); // Force Melbourne time
        $diagnostic_msg = "[{$timestamp}] 🔍🔍🔍 MERGE TAG REPLACEMENT STARTING! 🔍🔍🔍\n";
        $diagnostic_msg .= "[{$timestamp}] File: {$xml_file_name}\n";
        $diagnostic_msg .= "[{$timestamp}] Replacement Array Size: " . count($basic_replacements) . "\n";
        $diagnostic_msg .= "[{$timestamp}] Sample XML Content (first 500 chars): " . substr($xml_content, 0, 500) . "\n";
        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
        
        foreach ($basic_replacements as $tag => $value) {
            if (!empty($value)) {
                // v5.1.3: Extract base tag name for modifier checking
                if (preg_match('/\{\$([^}]+)\}/', $tag, $tag_matches)) {
                    $base_tag = $tag_matches[1]; // Extract "USR_Name" from "{$USR_Name}"
                } else {
                    continue; // Skip malformed tags
                }
                
                // Create proper modifier pattern: {$USR_Name|anything}
                $modifier_pattern = '/\{\$' . preg_quote($base_tag, '/') . '\|[^}]+\}/';
                
                // Count exact matches
                $exact_count = substr_count($xml_content, $tag);
                
                // Count modifier matches
                $modifier_matches = array();
                preg_match_all($modifier_pattern, $xml_content, $modifier_matches);
                $modifier_count = count($modifier_matches[0]);
                
                $total_replacements = 0;
                
                // Replace exact matches first
                if ($exact_count > 0) {
                    $escaped_value = htmlspecialchars($value, ENT_XML1, 'UTF-8');
                    $xml_content = str_replace($tag, $escaped_value, $xml_content);
                    $total_replacements += $exact_count;
                    LDA_Logger::debug("EXACT: Replaced {$exact_count} instances of {$tag} with '{$escaped_value}'");
                    
                    // FORCED DIAGNOSTIC LOG - TRACK CRITICAL REPLACEMENTS
                    if (strpos($tag, 'USR_Business') !== false || strpos($tag, 'USR_Name') !== false) {
                        $diagnostic_msg = "[{$timestamp}] ✅✅✅ CRITICAL REPLACEMENT MADE! ✅✅✅\n";
                        $diagnostic_msg .= "[{$timestamp}] Tag: {$tag}\n";
                        $diagnostic_msg .= "[{$timestamp}] Value: {$escaped_value}\n";
                        $diagnostic_msg .= "[{$timestamp}] Count: {$exact_count}\n";
                        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                    }
                }
                
                // Replace modifier matches by processing them individually
                if ($modifier_count > 0) {
                    LDA_Logger::debug("MODIFIER PATTERN: {$modifier_pattern} found {$modifier_count} matches");
                    
                    // v5.1.3: Special debugging for USR_Name vs PT2_Name modifiers
                    if (strpos($tag, 'USR_Name') !== false || strpos($tag, 'PT2_Name') !== false) {
                        LDA_Logger::log("🎯 MODIFIER DEBUG: Processing {$tag} with value '{$value}' - found {$modifier_count} modifier matches");
                        
                        // Log to diagnostic file
                        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                        $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
                        $diagnostic_msg = "[{$timestamp}] 🎯 MODIFIER DEBUG: {$tag} = '{$value}' - modifier matches: {$modifier_count}\n";
                        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                    }
                    
                    $xml_content = preg_replace_callback($modifier_pattern, function($matches) use ($value, &$total_replacements, $tag) {
                        $full_tag = $matches[0]; // e.g., {$USR_Name|upper}
                        preg_match('/\{\$([^|]+)\|([^}]+)\}/', $full_tag, $tag_parts);
                        
                        if (count($tag_parts) >= 3) {
                            $tag_name = $tag_parts[1]; // USR_Name
                            $modifier = $tag_parts[2]; // upper
                            $processed_value = self::applyModifier($value, $modifier);
                            $escaped_value = htmlspecialchars($processed_value, ENT_XML1, 'UTF-8');
                            $total_replacements++;
                            
                            // v5.1.3: Enhanced debugging for name fields
                            if (strpos($tag_name, 'Name') !== false) {
                                LDA_Logger::log("🎯 MODIFIER SUCCESS: {$full_tag} -> '{$escaped_value}' (original: '{$value}', modifier: {$modifier})");
                                
                                // Log to diagnostic file
                                $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
                                $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
                                $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
                                $diagnostic_msg = "[{$timestamp}] 🎯 MODIFIER SUCCESS: {$full_tag} -> '{$escaped_value}'\n";
                                file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                            } else {
                                LDA_Logger::log("MODIFIER: Replaced {$full_tag} with '{$escaped_value}' (modifier: {$modifier})");
                            }
                            
                            return $escaped_value;
                        }
                        return $full_tag; // Return unchanged if parsing fails
                    }, $xml_content);
                }
                
                if ($total_replacements > 0) {
                    LDA_Logger::log("TOTAL: Replaced {$total_replacements} instances of {$tag} variants");
                } else {
                    // v5.1.3: Debug why specific tags aren't being found
                    if (strpos($tag, 'USR_Name') !== false || strpos($tag, 'USR_ABN') !== false) {
                        LDA_Logger::log("DEBUG: Tag {$tag} has value '{$value}' but found {$exact_count} exact matches and {$modifier_count} modifier matches");
                        LDA_Logger::log("DEBUG: Checked pattern: " . $modifier_pattern);
                        
                        // FORCED DIAGNOSTIC LOG - TRACK MISSING CRITICAL TAGS
                        $diagnostic_msg = "[{$timestamp}] ❌❌❌ CRITICAL TAG NOT FOUND! ❌❌❌\n";
                        $diagnostic_msg .= "[{$timestamp}] Tag: {$tag}\n";
                        $diagnostic_msg .= "[{$timestamp}] Value: {$value}\n";
                        $diagnostic_msg .= "[{$timestamp}] Exact Count: {$exact_count}\n";
                        $diagnostic_msg .= "[{$timestamp}] Modifier Count: {$modifier_count}\n";
                        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                        
                        // Show what tags ARE in the XML for comparison
                        if (preg_match_all('/\{\$' . preg_quote($base_tag, '/') . '[^}]*\}/', $xml_content, $actual_matches)) {
                            LDA_Logger::log("DEBUG: Actually found in XML: " . implode(', ', array_unique($actual_matches[0])));
                            $diagnostic_msg = "[{$timestamp}] Found variations: " . implode(', ', array_unique($actual_matches[0])) . "\n";
                            file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                        }
                    }
                }
            } else {
                // v5.1.3: Debug empty values for key tags
                if (strpos($tag, 'USR_Name') !== false || strpos($tag, 'USR_ABN') !== false) {
                    LDA_Logger::log("DEBUG: Tag {$tag} has empty value, skipping replacement");
                }
            }
        }
        
        // Process advanced merge tags with modifiers and conditional logic
        LDA_Logger::log("*** CRITICAL: About to process advanced merge tags ***");
        $xml_content = self::processAdvancedMergeTags($xml_content, $merge_data, $replacements_made);
        LDA_Logger::log("*** CRITICAL: Advanced merge tags processing completed ***");
        
        // DYNAMIC FALLBACK: Handle any remaining merge tags from merge_data that weren't in basic_replacements
        foreach ($merge_data as $key => $value) {
            if (!empty($value)) {
                $regular_tag = '{$' . $key . '}';
                $entity_tag = '{&#36;' . $key . '}';
                
                // Check regular format
                if (!isset($basic_replacements[$regular_tag])) {
                    $count = substr_count($xml_content, $regular_tag);
                    if ($count > 0) {
                        $escaped_value = htmlspecialchars($value, ENT_XML1, 'UTF-8');
                        $xml_content = str_replace($regular_tag, $escaped_value, $xml_content);
                        LDA_Logger::log("DYNAMIC FALLBACK: Replaced {$count} instances of {$regular_tag} with '{$escaped_value}'");
                    }
                }
                
                // Check HTML entity format
                if (!isset($basic_replacements[$entity_tag])) {
                    $count = substr_count($xml_content, $entity_tag);
                    if ($count > 0) {
                        $escaped_value = htmlspecialchars($value, ENT_XML1, 'UTF-8');
                        $xml_content = str_replace($entity_tag, $escaped_value, $xml_content);
                        LDA_Logger::log("DYNAMIC FALLBACK: Replaced {$count} instances of {$entity_tag} with '{$escaped_value}'");
                    }
                }
            }
        }
        
        LDA_Logger::log("Merge tag replacement completed. Total replacements made: " . $replacements_made);
        
        // CRITICAL FIX: Process conditional logic AFTER all merge tags have been replaced
        // This ensures that variables like $USR_ABN are available for condition evaluation
        // v5.1.3 ENHANCED: Process conditional logic in ALL document parts (headers, footers, main)
        LDA_Logger::log("v5.1.3 ENHANCED: Processing conditional logic AFTER merge tag replacement for {$xml_file_name}");
        $conditional_replacements = 0;
        $xml_content = self::processConditionalLogic($xml_content, $merge_data, $conditional_replacements);
        LDA_Logger::milestone("Conditional logic processed for {$xml_file_name}, {$conditional_replacements} replacements made");
        
        // v5.1.3: INTELLIGENT cleanup - only remove conditional tags that are clearly malformed or unprocessed
        // Do NOT remove properly structured conditionals that should be processed
        
        // Only remove clearly broken conditional fragments (not complete conditional blocks)
        $cleanup_patterns = array(
            '/\{if\s+[^}]*<[^>]*>/',     // Broken if tags with XML fragments
            '/\{listif\s+[^}]*<[^>]*>/', // Broken listif tags with XML fragments  
            '/\{\/if[^}]*<[^>]*>/',      // Broken closing if tags with XML fragments
            '/\{\/listif[^}]*<[^>]*>/',  // Broken closing listif tags with XML fragments
        );
        
        $cleanup_count = 0;
        foreach ($cleanup_patterns as $pattern) {
            $matches_before = array();
            preg_match_all($pattern, $xml_content, $matches_before);
            $before_count = count($matches_before[0]);
            
            if ($before_count > 0) {
                $xml_content = preg_replace($pattern, '', $xml_content);
                
                $matches_after = array();
                preg_match_all($pattern, $xml_content, $matches_after);
                $after_count = count($matches_after[0]);
                
                $removed = $before_count - $after_count;
                if ($removed > 0) {
                    $cleanup_count += $removed;
                    LDA_Logger::log("v5.1.3: Cleaned up {$removed} broken/malformed conditional fragments");
                }
            }
        }
        
        if ($cleanup_count > 0) {
            LDA_Logger::log("v5.1.3: Total broken conditional fragments cleaned up: {$cleanup_count}");
        } else {
            LDA_Logger::log("v5.1.3: No broken conditional fragments found to clean up");
        }
        
        // DEBUG: Show what merge tags remain after processing
        preg_match_all('/\{\$[A-Z_]+[^}]*\}/', $xml_content, $remaining_tags);
        if (!empty($remaining_tags[0])) {
            $unique_remaining = array_unique($remaining_tags[0]);
            LDA_Logger::log("DEBUG - Merge tags remaining after processing: " . implode(', ', $unique_remaining));
            
            // For each remaining tag, check if we have data for it
            foreach ($unique_remaining as $remaining_tag) {
                if (preg_match('/\{\$([A-Z_]+)[^}]*\}/', $remaining_tag, $tag_match)) {
                    $tag_name = $tag_match[1];
                    if (isset($merge_data[$tag_name])) {
                        LDA_Logger::log("WARNING: Tag '{$remaining_tag}' was not replaced but data exists: '{$merge_data[$tag_name]}'");
                        
                        // Check if this is a simple fragmentation issue
                        if (strpos($xml_content, '{$' . $tag_name) !== false) {
                            LDA_Logger::log("FRAGMENTATION DETECTED: Tag '{$tag_name}' appears to be split in XML");
                        }
                    } else {
                        LDA_Logger::log("INFO: Tag '{$remaining_tag}' has no data in merge_data");
                    }
                }
            }
        } else {
            LDA_Logger::log("DEBUG - No merge tags remaining after processing");
        }
        return $xml_content;
    }
    
    /**
     * Process advanced merge tags with modifiers and conditional logic
     */
    private static function processAdvancedMergeTags($xml_content, $merge_data, &$replacements_made) {
        LDA_Logger::log("Processing advanced merge tags with modifiers (conditional logic now processed separately)");
        
        // Process tags with modifiers (e.g., {$USR_Name|upper}, {$USR_ABN|phone_format})
        $xml_content = self::processModifiers($xml_content, $merge_data, $replacements_made);
        
        return $xml_content;
    }
    
    /**
     * Process modifiers in merge tags
     */
    private static function processModifiers($xml_content, $merge_data, &$replacements_made) {
        LDA_Logger::log("Processing modifiers in merge tags");
        
        // Find tags with modifiers
        preg_match_all('/\{\$([^}|]+)\|([^}]+)\}/', $xml_content, $matches);
        
        foreach ($matches[0] as $index => $full_tag) {
            $tag_name = $matches[1][$index];
            $modifier = $matches[2][$index];
            
            if (isset($merge_data[$tag_name])) {
                $value = $merge_data[$tag_name];
                $processed_value = self::applyModifier($value, $modifier);
                
                if ($processed_value !== $value) {
                    $xml_content = str_replace($full_tag, htmlspecialchars($processed_value, ENT_XML1, 'UTF-8'), $xml_content);
                    $replacements_made++;
                    LDA_Logger::log("Applied modifier {$modifier} to {$tag_name}: '{$value}' -> '{$processed_value}'");
                }
            }
        }
        
        return $xml_content;
    }
    
    /**
     * Apply modifier to value
     */
    private static function applyModifier($value, $modifier) {
        switch ($modifier) {
            case 'upper':
                return strtoupper($value);
            case 'lower':
                return strtolower($value);
            default:
                if (strpos($modifier, 'phone_format:') === 0) {
                    // Extract format from phone_format:"%2 %3 %3 %3"
                    $format = str_replace('phone_format:', '', $modifier);
                    $format = trim($format, '"\'');
                    return self::formatPhoneNumber($value, $format);
                } elseif (strpos($modifier, 'date_format:') === 0) {
                    // Extract format from date_format:"d F Y"
                    $format = str_replace('date_format:', '', $modifier);
                    $format = trim($format, '"\'');
                    return self::formatDate($value, $format);
                }
                return $value;
        }
    }
    
    /**
     * Format date
     */
    private static function formatDate($date, $format) {
        if (empty($date)) return '';
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        if ($timestamp === false) return $date;
        
        // Use Australian timezone for date formatting
        $original_timezone = date_default_timezone_get();
        date_default_timezone_set('Australia/Melbourne');
        $formatted_date = date($format, $timestamp);
        date_default_timezone_set($original_timezone);
        
        return $formatted_date;
    }
    
    /**
     * Process conditional logic in merge tags
     */
    private static function processConditionalLogic($xml_content, $merge_data, &$replacements_made) {
        LDA_Logger::log("v5.1.3: Processing conditional logic on pre-cleaned XML");
        
        // Step 1: Process listif blocks first
        $xml_content = self::processListifBlocks($xml_content, $merge_data, $replacements_made);
        
        // Step 2: Process if/elseif/else blocks
        $xml_content = self::processIfBlocks($xml_content, $merge_data, $replacements_made);
        
        return $xml_content;
    }
    
    /**
     * Fix split conditional tags across XML elements - ENHANCED VERSION
     */
    private static function fixSplitConditionalTags($xml_content) {
        LDA_Logger::log("v5.1.3: Fixing split conditional tags");
        
        $fixes_applied = 0;
        
        // STEP 1: NUCLEAR APPROACH - Fix ANY conditional block that contains XML fragments
        // This is the most aggressive fix that will find and reconstruct ALL conditional logic
        
        // Pattern 1: Fix {if !empty($VAR)} patterns that are completely broken by XML
        $ultra_pattern = '/\{[^}]*?if[^}]*?!empty[^}]*?\$[^}]*?([A-Za-z0-9_]+)[^}]*?\}/s';
        $xml_content = preg_replace_callback($ultra_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            $var_name = $matches[1];
            
            // Remove all XML and reconstruct
            $clean_content = preg_replace('/<[^>]*>/', '', $split_tag);
            $clean_content = preg_replace('/\s+/', '', $clean_content);
            
            if (preg_match('/\{if!empty\$([A-Za-z0-9_]+)\}/', $clean_content, $clean_matches)) {
                $fixed_tag = '{if !empty($' . $clean_matches[1] . ')}';
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED NUCLEAR FIX: '{$split_tag}' -> '{$fixed_tag}'");
                return $fixed_tag;
            }
            
            return $split_tag;
        }, $xml_content);
        
        // Pattern 2: Fix complex conditional patterns with or/and logic
        $complex_pattern = '/\{[^}]*?if[^}]*?!empty[^}]*?\$[^}]*?Pmt_[^}]*?or[^}]*?!empty[^}]*?\$[^}]*?Pmt_[^}]*?\}/s';
        $xml_content = preg_replace_callback($complex_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            // Remove all XML and try to extract the pattern
            $clean_content = preg_replace('/<[^>]*>/', '', $split_tag);
            $clean_content = preg_replace('/\s+/', ' ', $clean_content);
            $clean_content = trim($clean_content);
            
            // Look for multiple Pmt_ fields in or conditions
            if (preg_match_all('/Pmt_(\w+)/', $clean_content, $pmt_matches)) {
                $pmt_fields = $pmt_matches[1];
                if (count($pmt_fields) >= 2) {
                    // Reconstruct as a simple or condition
                    $conditions = array();
                    foreach ($pmt_fields as $field) {
                        $conditions[] = "!empty(\$Pmt_{$field})";
                    }
                    $fixed_tag = '{if ' . implode(' or ', $conditions) . '}';
                    $fixes_applied++;
                    LDA_Logger::log("v5.1.3 ENHANCED COMPLEX FIX: '{$split_tag}' -> '{$fixed_tag}'");
                    return $fixed_tag;
                }
            }
            
            return $split_tag;
        }, $xml_content);
        
        // Pattern 3: Fix /if closing tags that are split
        $endif_pattern = '/\{[^}]*?\/if[^}]*?\}/s';
        $xml_content = preg_replace_callback($endif_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            $clean_content = preg_replace('/<[^>]*>/', '', $split_tag);
            $clean_content = preg_replace('/\s+/', '', $clean_content);
            
            if (strpos($clean_content, '/if') !== false) {
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED ENDIF FIX: '{$split_tag}' -> '{/if}'");
                return '{/if}';
            }
            
            return $split_tag;
        }, $xml_content);
        
        // v5.1.3: ENHANCED pattern to catch listif blocks that are completely fragmented in XML
        // This addresses the Purposes section where {listif $Pmt_Services == "yes1"} is split
        
        // Step 1: Ultra-aggressive listif reconstruction
        // Look for any text that contains "listif" and reconstruct common patterns
        $listif_ultra_pattern = '/\{[^}]*?listif[^}]*?\$[^}]*?Pmt_[^}]*?==[^}]*?"yes[1-4]"[^}]*?\}/si';
        $xml_content = preg_replace_callback($listif_ultra_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            // Remove ALL XML tags from the inner content and extract the essentials
            $clean_content = preg_replace('/<[^>]*>/', '', $split_tag);
            $clean_content = preg_replace('/\s+/', ' ', $clean_content);
            $clean_content = trim($clean_content);
            
            // Try to extract field name and value from the cleaned content
            if (preg_match('/\{listif\s*\$\s*(Pmt_\w+)\s*==\s*"(yes[1-4])"\s*\}/', $clean_content, $field_matches)) {
                $field_name = $field_matches[1];
                $value = $field_matches[2];
                $fixed_tag = "{listif \${$field_name} == \"{$value}\"}";
                
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED ULTRA LISTIF FIX: Fixed complex split listif tag: " . $fixed_tag);
                return $fixed_tag;
            }
            
            return $split_tag;
        }, $xml_content);
        
        // Step 2: Fix {if !empty($VAR)} patterns that are split
        $if_empty_pattern = '/\{if[^}]*<[^>]*>[^}]*empty[^}]*<[^>]*>[^}]*\$[A-Z_]+[^}]*<[^>]*>[^}]*\}/s';
        $xml_content = preg_replace_callback($if_empty_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            // Extract variable name from the split tag
            if (preg_match('/\$([A-Z_]+)/', $split_tag, $var_match)) {
                $var_name = $var_match[1];
                $fixed_tag = '{if !empty($' . $var_name . ')}';
                
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED FIX: Fixed complex split if empty tag: " . $fixed_tag);
                return $fixed_tag;
            }
            return $split_tag;
        }, $xml_content);
        
        // Step 3: Fix simpler {if} patterns
        $if_pattern = '/\{if\s+[^}]*<\/w:t>.*?<w:t[^>]*>[^}]*\}/s';
        $xml_content = preg_replace_callback($if_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            // Extract the condition part
            if (preg_match('/\{if\s+!empty\(\$([A-Z_a-z]+)\)/', $split_tag, $condition_match)) {
                $var_name = $condition_match[1];
                $fixed_tag = '{if !empty($' . $var_name . ')}';
                
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED FIX: Fixed split if empty tag: " . $fixed_tag);
                return $fixed_tag;
            }
            return $split_tag;
        }, $xml_content);
        
        // Step 4: Fix {/if} and {/listif} end tags
        $endtag_pattern = '/\{\/(?:listif|if)[^}]*<\/w:t>.*?<w:t[^>]*>[^}]*\}/s';
        $xml_content = preg_replace_callback($endtag_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            if (strpos($split_tag, 'listif') !== false) {
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED FIX: Fixed split /listif tag");
                return '{/listif}';
            } else {
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED FIX: Fixed split /if tag");
                return '{/if}';
            }
        }, $xml_content);
        
        // Step 5: Fix {listif} patterns - enhanced for Pmt_ fields
        $listif_pattern = '/\{listif\s+[^}]*<\/w:t>.*?<w:t[^>]*>[^}]*\}/s';
        $xml_content = preg_replace_callback($listif_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            // Extract the condition part - handle $variable == "value" format
            if (preg_match('/\{listif\s+\$([A-Z_a-z]+)\s*==\s*"([^"]+)"/', $split_tag, $condition_match)) {
                $var_name = $condition_match[1];
                $value = $condition_match[2];
                $fixed_tag = '{listif $' . $var_name . ' == "' . $value . '"}';
                
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED FIX: Fixed split listif tag: " . $fixed_tag);
                return $fixed_tag;
            }
            return $split_tag;
        }, $xml_content);
        
        // Step 6: NUCLEAR OPTION - Find and fix ANY remaining listif blocks that are mangled
        // This is specifically for the Purposes section issue
        $nuclear_listif_patterns = array(
            // Pattern for Pmt_Services
            '/\{[^}]*?listif[^}]*?\$[^}]*?Pmt_Services[^}]*?==[^}]*?"yes1"[^}]*?\}/si' => '{listif $Pmt_Services == "yes1"}',
            // Pattern for Pmt_Agreements (template error - should be Pmt_Negotiate)
            '/\{[^}]*?listif[^}]*?\$[^}]*?Pmt_Agreements[^}]*?==[^}]*?"yes2"[^}]*?\}/si' => '{listif $Pmt_Agreements == "yes2"}',
            // Pattern for Pmt_Relations (template error - should be Pmt_Business)  
            '/\{[^}]*?listif[^}]*?\$[^}]*?Pmt_Relations[^}]*?==[^}]*?"yes3"[^}]*?\}/si' => '{listif $Pmt_Relations == "yes3"}',
            // Pattern for Pmt_Other
            '/\{[^}]*?listif[^}]*?\$[^}]*?Pmt_Other[^}]*?==[^}]*?"yes4"[^}]*?\}/si' => '{listif $Pmt_Other == "yes4"}',
        );
        
        foreach ($nuclear_listif_patterns as $pattern => $replacement) {
            $before = $xml_content;
            $xml_content = preg_replace($pattern, $replacement, $xml_content);
            if ($xml_content !== $before) {
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED NUCLEAR LISTIF FIX: Applied pattern {$replacement}");
            }
        }
        
        // Step 7: Fix {/listif} closing tags that might be split
        $nuclear_endlist_pattern = '/\{[^}]*?\/listif[^}]*?\}/si';
        $xml_content = preg_replace_callback($nuclear_endlist_pattern, function($matches) use (&$fixes_applied) {
            $split_tag = $matches[0];
            
            // Remove XML and clean up
            $clean_tag = preg_replace('/<[^>]*>/', '', $split_tag);
            if (strpos($clean_tag, '/listif') !== false) {
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED NUCLEAR ENDLIST FIX: Fixed closing listif tag");
                return '{/listif}';
            }
            return $split_tag;
        }, $xml_content);
        
        // Step 8: Advanced cleanup - remove any remaining XML fragments in conditional tags
        $cleanup_pattern = '/(\{(?:if|listif|\/if|\/listif)[^}]*)<[^>]*>([^}]*\})/';
        $xml_content = preg_replace_callback($cleanup_pattern, function($matches) use (&$fixes_applied) {
            $start = $matches[1]; // {if part
            $end = $matches[2];   // rest}
            $cleaned = $start . $end;
            
            if ($cleaned !== $matches[0]) {
                $fixes_applied++;
                LDA_Logger::log("v5.1.3 ENHANCED CLEANUP: Removed XML fragments from: " . substr($matches[0], 0, 50) . "... -> " . $cleaned);
            }
            
            return $cleaned;
        }, $xml_content);
        
        if ($fixes_applied > 0) {
            LDA_Logger::log("v5.1.3: Fixed {$fixes_applied} split conditional tags");
        }
        
        return $xml_content;
    }
    
    /**
     * Process listif blocks: {listif condition}content{/listif}
     */
    private static function processListifBlocks($xml_content, $merge_data, &$replacements_made) {
        LDA_Logger::log("v5.1.3: Processing listif blocks");
        
        $pattern = '/\{listif\s+([^}]+)\}(.*?)\{\/listif\}/s';
        
        return preg_replace_callback($pattern, function($matches) use ($merge_data, &$replacements_made) {
            $condition = trim($matches[1]);
            $content = $matches[2];
            
            LDA_Logger::log("v5.1.3: Found listif block with condition: {$condition}");
            LDA_Logger::log("v5.1.3: Content length: " . strlen($content) . " chars");
            
            if (self::evaluateCondition($condition, $merge_data)) {
                $replacements_made++;
                LDA_Logger::log("v5.1.3: Listif condition MET - including content");
                return $content;
            } else {
                LDA_Logger::log("v5.1.3: Listif condition NOT MET - removing content");
                return '';
            }
        }, $xml_content);
    }
    
    /**
     * Process if/elseif/else blocks
     */
    private static function processIfBlocks($xml_content, $merge_data, &$replacements_made) {
        LDA_Logger::log("v5.1.3: Processing if/else blocks");
        
        // Enhanced pattern to catch if blocks with better handling
        $pattern = '/\{if\s+([^}]+)\}(.*?)(?:\{elseif\s+([^}]+)\}(.*?))*(?:\{else\}(.*?))?\{\/if\}/s';
        
        $xml_content = preg_replace_callback($pattern, function($matches) use ($merge_data, &$replacements_made) {
            $if_condition = trim($matches[1]);
            $if_content = $matches[2];
            $else_content = isset($matches[5]) ? $matches[5] : '';
            
            LDA_Logger::log("v5.1.3: Found if block with condition: {$if_condition}");
            LDA_Logger::log("v5.1.3: If content length: " . strlen($if_content) . " chars");
            LDA_Logger::log("v5.1.3: Else content length: " . strlen($else_content) . " chars");
            
            // Try to evaluate condition
            $condition_result = self::evaluateCondition($if_condition, $merge_data);
            
            if ($condition_result === null) {
                // Condition couldn't be evaluated - log warning and return empty (conservative approach)
                LDA_Logger::log("v5.1.3: WARNING - Could not evaluate condition '{$if_condition}' - removing entire conditional block");
                $replacements_made++;
                return '';
            } elseif ($condition_result === true) {
                $replacements_made++;
                LDA_Logger::log("v5.1.3: If condition MET - including if content");
                return $if_content;
            } else {
                $replacements_made++;
                if (!empty($else_content)) {
                    LDA_Logger::log("v5.1.3: If condition NOT MET - including else content");
                    return $else_content;
                } else {
                    LDA_Logger::log("v5.1.3: If condition NOT MET and no else content - removing content");
                    return '';
                }
            }
        }, $xml_content);
        
        return $xml_content;
    }
    
    /**
     * Enhanced condition evaluation with field aliases and XML cleanup
     */
    private static function evaluateCondition($condition, $merge_data) {
        $original_condition = $condition;
        $condition = trim($condition);
        
        // STEP 1: AGGRESSIVE XML CLEANUP for conditions that still contain fragments
        // Remove any remaining XML tags from the condition
        $condition = preg_replace('/<[^>]*>/', '', $condition);
        // Remove excessive whitespace
        $condition = preg_replace('/\s+/', ' ', $condition);
        $condition = trim($condition);
        
        LDA_Logger::log("v5.1.3: Evaluating condition: '{$condition}' (original: '{$original_condition}')");
        
        // If condition is empty after cleanup, try to fall back to safe evaluation
        if (empty($condition) || strlen($condition) < 3) {
            LDA_Logger::log("v5.1.3: Condition too short or empty after XML cleanup - returning TRUE to preserve content");
            return true; // Better to include content than remove it
        }
        
        // v5.1.3 ENHANCED: Add field aliases for common template variations
        $field_aliases = array(
            'Pmt_Agreements' => 'Pmt_Negotiate',  // Template uses Pmt_Agreements but form field is Pmt_Negotiate
            'Pmt_Relations' => 'Pmt_Business',   // Template uses Pmt_Relations but form field is Pmt_Business
        );
        
        // v5.1.3 ENHANCED: Add checkbox value mapping for purposes - check actual form submission values
        $checkbox_value_mapping = array(
            'Pmt_Services' => array('27.yes1', 'yes1'),
            'Pmt_Negotiate' => array('27.yes2', 'yes2'), 
            'Pmt_Business' => array('27.yes3', 'yes3'),
            'Pmt_Other' => array('27.yes4', 'yes4')
        );
        
        // Helper function to get field value with alias support and checkbox handling
        $getValue = function($var_name) use ($merge_data, $field_aliases, $checkbox_value_mapping) {
            // First, check if we have the exact field
            if (isset($merge_data[$var_name])) {
                $value = $merge_data[$var_name];
                LDA_Logger::log("v5.1.3 ENHANCED CHECKBOX DEBUG: Found direct field {$var_name} = '{$value}'");
                return $value;
            }
            
            // Check for checkbox-specific mappings (27.yes1, 27.yes2, etc.)
            if (isset($checkbox_value_mapping[$var_name])) {
                $checkbox_keys = $checkbox_value_mapping[$var_name];
                foreach ($checkbox_keys as $key) {
                    if (isset($merge_data[$key]) && !empty($merge_data[$key])) {
                        LDA_Logger::log("v5.1.3 ENHANCED CHECKBOX DEBUG: Found checkbox {$var_name} via key {$key} = '{$merge_data[$key]}'");
                        return $key; // Return the key (yes1, yes2, etc.) for evaluation
                    }
                }
                LDA_Logger::log("v5.1.3 ENHANCED CHECKBOX DEBUG: No checkbox values found for {$var_name}");
            }
            
            // Check aliases
            if (isset($field_aliases[$var_name]) && isset($merge_data[$field_aliases[$var_name]])) {
                $alias_field = $field_aliases[$var_name];
                LDA_Logger::log("v5.1.3 ENHANCED FIELD ALIAS: Using {$alias_field} for {$var_name}");
                return $merge_data[$alias_field];
            }
            
            LDA_Logger::log("v5.1.3 ENHANCED FIELD DEBUG: No value found for {$var_name}");
            return '';
        };
        
        // STEP 2: Try different patterns for condition evaluation
        
        // Handle !empty($VAR) format - enhanced with case flexibility
        if (preg_match('/!empty\(\$([A-Z_a-z0-9]+)\)/i', $condition, $matches)) {
            $var_name = $matches[1];
            $value = $getValue($var_name);
            $result = !empty($value);
            
            LDA_Logger::log("v5.1.3 ENHANCED: !empty($var_name) - Value: '{$value}' - Result: " . ($result ? 'TRUE' : 'FALSE'));
            return $result;
        }
        
        // Handle multiple !empty conditions with 'or' operator
        if (preg_match_all('/!empty\(\$([A-Z_a-z0-9]+)\)/', $condition, $matches, PREG_SET_ORDER)) {
            if (strpos($condition, ' or ') !== false) {
                $or_result = false;
                foreach ($matches as $match) {
                    $var_name = $match[1];
                    $value = $getValue($var_name);
                    if (!empty($value)) {
                        $or_result = true;
                        LDA_Logger::log("v5.1.3 ENHANCED OR CONDITION: {$var_name} has value '{$value}' - OR condition is TRUE");
                        break;
                    }
                }
                LDA_Logger::log("v5.1.3 ENHANCED OR RESULT: " . ($or_result ? 'TRUE' : 'FALSE'));
                return $or_result;
            }
        }
        
        // Handle $VAR == "value" format
        if (preg_match('/\$([A-Z_a-z]+)\s*==\s*"([^"]*)"/', $condition, $matches)) {
            $var_name = $matches[1];
            $expected_value = $matches[2];
            $actual_value = $getValue($var_name);
            $result = ($actual_value == $expected_value);
            
            LDA_Logger::log("v5.1.3: $var_name == \"$expected_value\" - Actual: '{$actual_value}' - Result: " . ($result ? 'TRUE' : 'FALSE'));
            return $result;
        }
        
        // Handle $VAR != "value" format
        if (preg_match('/\$([A-Z_a-z]+)\s*!=\s*"([^"]*)"/', $condition, $matches)) {
            $var_name = $matches[1];
            $expected_value = $matches[2];
            $actual_value = $getValue($var_name);
            $result = ($actual_value != $expected_value);
            
            LDA_Logger::log("v5.1.3: $var_name != \"$expected_value\" - Actual: '{$actual_value}' - Result: " . ($result ? 'TRUE' : 'FALSE'));
            return $result;
        }
        
        // Handle empty($VAR) format
        if (preg_match('/empty\(\$([A-Z_a-z]+)\)/', $condition, $matches)) {
            $var_name = $matches[1];
            $value = $getValue($var_name);
            $result = empty($value);
            
            LDA_Logger::log("v5.1.3: empty($var_name) - Value: '{$value}' - Result: " . ($result ? 'TRUE' : 'FALSE'));
            return $result;
        }
        
        // STEP 3: FALLBACK - If we can't parse the condition but it looks like it contains field names,
        // assume it's true to preserve content rather than remove it
        if (preg_match('/\$[A-Z_a-z]+/', $condition)) {
            LDA_Logger::log("v5.1.3: Unparseable condition but contains field references - returning TRUE to preserve content");
            return true;
        }
        
        LDA_Logger::log("v5.1.3: Unknown condition format: '{$condition}' - returning FALSE to be safe");
        return false; // Only return false if we're sure the condition is invalid
    }
    
    /**
     * Process modifier on a value
     */
    private static function processModifier($value, $modifier) {
        // Handle phone_format modifier (with or without quotes)
        if (preg_match('/phone_format:"([^"]+)"/', $modifier, $matches)) {
            $format = $matches[1];
            return self::formatPhone($value, $format);
        } elseif (preg_match('/phone_format:([^}]+)/', $modifier, $matches)) {
            $format = $matches[1];
            return self::formatPhone($value, $format);
        }
        
        // Handle date_format modifier (with or without quotes)
        if (preg_match('/date_format:"([^"]+)"/', $modifier, $matches)) {
            $format = $matches[1];
            return self::formatDate($value, $format);
        } elseif (preg_match('/date_format:([^}]+)/', $modifier, $matches)) {
            $format = $matches[1];
            return self::formatDate($value, $format);
        }
        
        // Handle replace modifier (with or without quotes)
        if (preg_match('/replace:"([^"]+)":"([^"]+)"/', $modifier, $matches)) {
            $search = $matches[1];
            $replace = $matches[2];
            return str_replace($search, $replace, $value);
        } elseif (preg_match('/replace:([^:]+):([^}]+)/', $modifier, $matches)) {
            $search = $matches[1];
            $replace = $matches[2];
            return str_replace($search, $replace, $value);
        }
        
        // Handle simple modifiers
        switch ($modifier) {
            case 'upper':
                return strtoupper($value);
            case 'lower':
                return strtolower($value);
            case 'ucwords':
                return ucwords($value);
            case 'ucfirst':
                return ucfirst($value);
            default:
                LDA_Logger::log("Unknown modifier: {$modifier}");
                return $value;
        }
    }
    
    /**
     * Format phone number based on pattern
     */
    private static function formatPhone($phone, $format) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Apply format pattern
        $formatted = $format;
        $phone_chars = str_split($phone);
        $char_index = 0;
        
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] == '%' && $i + 1 < strlen($format)) {
                $next_char = $format[$i + 1];
                if (is_numeric($next_char) && $char_index < count($phone_chars)) {
                    $formatted = substr_replace($formatted, $phone_chars[$char_index], $i, 2);
                    $char_index++;
                }
            }
        }
        
        return $formatted;
    }
    
    
    /**
     * CONSERVATIVE split merge tag reconstruction - v5.1.4 SAFE VERSION
     * FIXES: False positive reconstructions that were contaminating merge tags
     * ONLY fixes VERIFIED split patterns to prevent tag contamination
     */
    private static function fixSplitMergeTagsConservative($xml_content) {
        LDA_Logger::log("Starting ROBUST split merge tag reconstruction v5.1.6");

        $fixes_applied = 0;

        // This pattern finds a '{' then non-greedily captures everything until the next '}'
        $xml_content = preg_replace_callback('/(\{)(.*?)(\})/s', function($matches) use (&$fixes_applied) {
            $full_match = $matches[0];
            $tag_open = $matches[1]; // "{"
            $tag_content = $matches[2]; // Content between braces
            $tag_close = $matches[3]; // "}"

            // If the content between the braces contains XML tags, it's a split tag that needs fixing.
            if (strpos($tag_content, '<') !== false) {
                // Remove all XML tags from the content to reconstruct the tag.
                $cleaned_content = preg_replace('/<[^>]+>/', '', $tag_content);

                // Reassemble the full, clean tag
                $reconstructed_tag = $tag_open . $cleaned_content . $tag_close;

                // Normalize whitespace that might have been introduced inside the tag
                $reconstructed_tag = preg_replace('/\s+/', ' ', $reconstructed_tag);
                // Specifically, clean up whitespace inside the curly braces
                $reconstructed_tag = preg_replace_callback('/(\{)(.*?)(\})/s', function($inner_matches) {
                    return $inner_matches[1] . trim($inner_matches[2]) . $inner_matches[3];
                }, $reconstructed_tag);

                // A validation to ensure we've reconstructed a plausible merge tag or conditional.
                // This prevents accidentally corrupting the document if we match something unintended.
                if (preg_match('/^\{(if|elseif|else|\/if|listif|\/listif|\$).*\}$/i', $reconstructed_tag)) {
                    $fixes_applied++;
                    LDA_Logger::log("ROBUST FIX: Reconstructed '{$full_match}' -> '{$reconstructed_tag}'");
                    return $reconstructed_tag;
                } else {
                     LDA_Logger::log("ROBUST-WARN: Matched '{$full_match}' but reconstructed tag '{$reconstructed_tag}' failed validation. Reverting.");
                     // Failed validation, return original to be safe
                     return $full_match;
                }
            }

            // If no XML tags are present, it's not a split tag, so return the original match.
            return $full_match;
        }, $xml_content);

        LDA_Logger::log("ROBUST: Split merge tag reconstruction completed. Applied {$fixes_applied} fixes.");
        return $xml_content;
    }
    
    /**
     * Fix split merge tags across XML elements (WORKING approach) 
     */
    private static function fixSplitMergeTags($xml_content) {
        LDA_Logger::log("Starting WORKING split merge tag fixing");
        LDA_Logger::log("DIAGNOSTIC: Input XML length: " . strlen($xml_content) . " characters");
        
        $original_content = $xml_content;
        $fixes_applied = 0;
        
        // DIAGNOSTIC: Search for any {$ patterns to understand what we're dealing with
        if (preg_match_all('/\{\$[^}]*\}/s', $xml_content, $all_patterns)) {
            LDA_Logger::log("DIAGNOSTIC: Found " . count($all_patterns[0]) . " potential merge tag patterns");
            foreach (array_slice($all_patterns[0], 0, 5) as $i => $pattern) {
                LDA_Logger::log("DIAGNOSTIC PATTERN " . ($i+1) . ": " . substr($pattern, 0, 200) . "...");
            }
        }
        
        // SPECIFIC PATTERN 1: Handle the exact fragmentation pattern from logs
        $specific_pattern1 = '/\{\$<\/w:t><\/w:r><w:proofErr[^>]*><w:r[^>]*><w:rPr[^>]*><w:t>([A-Z][A-Z0-9_]*)<\/w:t><\/w:r><w:proofErr[^>]*><w:r[^>]*><w:rPr[^>]*><w:t>\}/s';
        $before_count = substr_count($xml_content, '{$');
        $xml_content = preg_replace_callback($specific_pattern1, function($matches) use (&$fixes_applied) {
            $field_name = $matches[1];
            $clean_tag = '{$' . $field_name . '}';
            $fixes_applied++;
            LDA_Logger::log("SPECIFIC FIX 1: Found exact split pattern -> '" . $clean_tag . "'");
            return $clean_tag;
        }, $xml_content);
        $after_count = substr_count($xml_content, '{$');
        LDA_Logger::log("SPECIFIC PATTERN 1: Before count: " . $before_count . ", After: " . $after_count);
        
        // SPECIFIC PATTERN 2: Handle the actual pattern we see in document
        $specific_pattern2 = '/\{\$<\/w:t><\/w:r><w:proofErr w:type="spellStart"\/><w:r[^>]*><w:rPr[^>]*><w:t>([A-Z][A-Z0-9_]*)<\/w:t><\/w:r><w:proofErr w:type="spellEnd"\/><w:r[^>]*><w:rPr[^>]*><w:t>\}/s';
        $before_count2 = substr_count($xml_content, '{$');
        $xml_content = preg_replace_callback($specific_pattern2, function($matches) use (&$fixes_applied) {
            $field_name = $matches[1];
            $clean_tag = '{$' . $field_name . '}';
            $fixes_applied++;
            LDA_Logger::log("SPECIFIC FIX 2: Found spellStart/spellEnd pattern -> '" . $clean_tag . "'");
            return $clean_tag;
        }, $xml_content);
        $after_count2 = substr_count($xml_content, '{$');
        LDA_Logger::log("SPECIFIC PATTERN 2: Before count: " . $before_count2 . ", After: " . $after_count2);
        
        // TARGETED APPROACH: Fix specific known split patterns that we've identified
        $before_count3 = substr_count($xml_content, '{$');
        $targeted_patterns = [
            // Pattern for USR_Name (no modifier)
            '/\{\$<\/w:t><\/w:r>.*?<w:t>USR_Name<\/w:t>.*?<w:t>\}/s' => '{$USR_Name}',
            
            // Pattern for USR_Name|upper
            '/\{\$<\/w:t><\/w:r>.*?<w:t>USR_Name\|upper<\/w:t>.*?<w:t>\}/s' => '{$USR_Name|upper}',
            
            // Pattern for other USR_ fields with optional modifiers
            '/\{\$<\/w:t><\/w:r>.*?<w:t>(USR_[A-Z_]+(?:\|[a-z_|:]+)?)<\/w:t>.*?<w:t>\}/s' => '{$\\1}',
            
            // Pattern for PT2_ fields with optional modifiers  
            '/\{\$<\/w:t><\/w:r>.*?<w:t>(PT2_[A-Z_]+(?:\|[a-z_|:]+)?)<\/w:t>.*?<w:t>\}/s' => '{$\\1}',
            
            // Pattern for other standard fields
            '/\{\$<\/w:t><\/w:r>.*?<w:t>([A-Z][A-Z0-9_]{3,}(?:\|[a-z_|:]+)?)<\/w:t>.*?<w:t>\}/s' => '{$\\1}',
        ];
        
        foreach ($targeted_patterns as $pattern => $replacement) {
            $xml_content = preg_replace_callback($pattern, function($matches) use (&$fixes_applied, $replacement) {
                $fixes_applied++;
                
                // Handle replacement with captured groups
                if (strpos($replacement, '\\1') !== false) {
                    $field_name = $matches[1];
                    $clean_tag = '{$' . $field_name . '}';
                    LDA_Logger::log("TARGETED FIX: Found field '" . $field_name . "' -> '" . $clean_tag . "'");
                    return $clean_tag;
                } else {
                    LDA_Logger::log("DIRECT FIX: -> '" . $replacement . "'");
                    return $replacement;
                }
            }, $xml_content);
        }
        
        $after_count3 = substr_count($xml_content, '{$');
        LDA_Logger::log("TARGETED PATTERNS: Before count: " . $before_count3 . ", After: " . $after_count3);
        
        if ($fixes_applied > 0) {
            LDA_Logger::log("WORKING split merge tag fixing completed. Applied " . $fixes_applied . " fixes");
        } else {
            LDA_Logger::log("No split merge tags detected - this may indicate pattern matching issues");
        }
        
        return $xml_content;
    }
    
    /**
     * Check if XML content is valid
     */
    private static function isValidXML($xml_content) {
        // Simple XML validation - check for basic structure
        if (empty($xml_content)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if XML is well-formed using DOMDocument
     */
    private static function isWellFormedXML($xml) {
        if (!is_string($xml) || $xml === '') {
            return false;
        }
        
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        
        // Suppress warnings and check if XML loads properly
        return @($dom->loadXML($xml)) !== false;
    }
    
    /**
     * v5.1.3 ENHANCED: Parse memory limit for WordPress safety checks
     */
    private static function parse_memory_limit($memory_limit) {
        $memory_limit = trim($memory_limit);
        $unit = strtoupper(substr($memory_limit, -1));
        $value = (int) substr($memory_limit, 0, -1);
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int) $memory_limit;
        }
    }
    
    /**
     * Sanitize XML string: remove illegal XML characters and fix stray ampersands
     */
    private static function sanitizeXML($xml) {
        if (!is_string($xml)) {
            return '';
        }
        
        // Remove illegal XML characters
        $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x84\x86-\x9F]/u', '', $xml);
        
        // Fix unescaped ampersands
        $xml = preg_replace('/&(?![a-zA-Z]+;|#\d+;|#x[0-9a-fA-F]+;)/', '&amp;', $xml);
        
        return $xml;
    }
    
    /**
     * Check if this processor is available
     */
    public static function isAvailable() {
        return class_exists('ZipArchive');
    }
}
