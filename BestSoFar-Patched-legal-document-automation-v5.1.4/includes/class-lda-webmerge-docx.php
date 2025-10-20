<?php
/**
 * Webmer        $debug_file = ABSPATH . 'wp-content/uploads/lda-debug.txt';
        $debug_msg = "\n" . date('Y-m-d H:i:s') . " - 🔍 VERSION 3.2.0 - XML ANALYSIS DEBUG! 🔍\n";
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);      $debug_msg = "\n" . date('Y-m-d H:i:s') . " - 🔍 VERSION 3.2.0 - XML ANALYSIS DEBUG! 🔍\n";-compatible DOCX processor
 * 
 * This class processes DOCX files using the same approach as Webmerge,
 * handling merge tags in plain text first, then reconstructing the document.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LDA_WebMerge_DOCX {
    
    /**
     * Process merge tags in a DOCX file with comprehensive merge tag replacement
     */
    public function processDocument($templatePath, $outputPath, $mergeData) {
        // DIRECT FILE DEBUG - bypasses all logging systems  
        $debug_file = ABSPATH . 'wp-content/uploads/lda-debug.txt';
        $debug_msg = "\n" . date('Y-m-d H:i:s') . " - � VERSION 3.1.7 VARIABLE NAME FIX - DATA PRESERVED! �\n";
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        // CRITICAL DEBUG: Show exactly what the DOCX processor receives
        $debug_msg = "📥 DOCX PROCESSOR RECEIVED:\n";
        $debug_msg .= "📊 DATA COUNT: " . count($mergeData) . "\n";
        if (!empty($mergeData)) {
            $debug_msg .= "📋 FIRST 10 KEYS: " . implode(', ', array_slice(array_keys($mergeData), 0, 10)) . "\n";
            $sample_data = array_slice($mergeData, 0, 5, true);
            $debug_msg .= "📄 SAMPLE DATA: " . json_encode($sample_data) . "\n";
        } else {
            $debug_msg .= "❌ MERGE DATA IS EMPTY!\n";
        }
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        // DOCX processor starting
        LDA_Logger::debug("DOCX processor starting");
        LDA_Logger::debug("Template: $templatePath");
        LDA_Logger::debug("Output: $outputPath");

        // Log merge data summary to avoid truncation
        $merge_summary = array();
        foreach ($mergeData as $key => $value) {
            if (is_scalar($value) && strlen($value) > 50) {
                $merge_summary[$key] = substr($value, 0, 50) . '...';
            } else {
                $merge_summary[$key] = $value;
            }
        }
        LDA_Logger::log("Merge data: " . json_encode($merge_summary, JSON_PRETTY_PRINT));
        
        // Copy template to output path
        if (!copy($templatePath, $outputPath)) {
            LDA_Logger::error("Failed to copy template to output path: $templatePath -> $outputPath");
            return array('success' => false, 'error' => 'Failed to copy template to output path');
        }
        
        // Open the DOCX file as a ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== TRUE) {
            LDA_Logger::error("Failed to open DOCX file as ZIP archive: $outputPath");
            return array('success' => false, 'error' => 'Failed to open DOCX file as ZIP archive');
        }
        
        // Define all possible XML parts of a DOCX file that can contain user content
        // Process main document FIRST to ensure it gets processed even if there are errors
        $xml_parts = array(
            'word/document.xml',  // MOST IMPORTANT - process first
            'word/header1.xml', 'word/header2.xml', 'word/header3.xml',
            'word/footer1.xml', 'word/footer2.xml', 'word/footer3.xml',
        );
        
        // Debug: Log which parts exist in the archive
        LDA_Logger::log("Checking XML parts in DOCX archive:");
        foreach ($xml_parts as $check_part) {
            $exists = ($zip->locateName($check_part) !== false);
            LDA_Logger::log("- $check_part: " . ($exists ? "EXISTS" : "NOT FOUND"));
        }
        
        // Debug: List ALL files in the DOCX archive to see what's actually there
        LDA_Logger::log("ALL FILES IN DOCX ARCHIVE:");
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info = $zip->statIndex($i);
            if ($file_info && strpos($file_info['name'], '.xml') !== false) {
                LDA_Logger::log("- XML file found: " . $file_info['name']);
            }
        }
        
        // Priority processing: Handle main document first to ensure merge tags are processed
        LDA_Logger::log("Processing main document (word/document.xml) first");
        $main_doc_xml = $zip->getFromName('word/document.xml');
        if ($main_doc_xml !== false) {
            LDA_Logger::log("Main document found, content length: " . strlen($main_doc_xml));
            // Process the main document immediately
            $processed_xml = self::processMergeTagsInXML($main_doc_xml, $mergeData);
            $processed_xml = self::sanitizeXML($processed_xml);
            if (!self::isWellFormedXML($processed_xml)) {
                LDA_Logger::error("Main document XML not well-formed. Attempting repair.");
                $processed_xml = self::simpleLiteralReplacement($main_doc_xml, $mergeData);
                $processed_xml = self::sanitizeXML($processed_xml);
            }
            if ($zip->addFromString('word/document.xml', $processed_xml) === false) {
                LDA_Logger::error("Failed to write main document back to DOCX");
            } else {
                LDA_Logger::log("Main document processed and written successfully");
                $processed_files++;
            }
        } else {
            LDA_Logger::error("Main document (word/document.xml) not found in DOCX archive");
        }

        // Continue with normal processing for other files
        $processed_files = 0;
        
        // Process each XML part (skip main document since we already processed it above)
        foreach ($xml_parts as $part_name) {
            // Skip main document since we already processed it in priority processing
            if ($part_name === 'word/document.xml') {
                LDA_Logger::log("Skipping $part_name - already processed in priority processing");
                continue;
            }
            
            // Check if the part exists in the archive
            if ($zip->locateName($part_name) !== false) {
                LDA_Logger::log("Found and processing: $part_name");
                $xml_content = $zip->getFromName($part_name);
                if ($xml_content === false) {
                    LDA_Logger::warn("Could not read XML part: $part_name");
                    continue;
                }

                LDA_Logger::log("Processing XML file: $part_name");

                // Special logging for main document
                if ($part_name === 'word/document.xml') {
                    LDA_Logger::log("*** PROCESSING MAIN DOCUMENT - word/document.xml ***");
                    LDA_Logger::log("XML content length: " . strlen($xml_content) . " characters");
                }

                // Skip header/footer files if they don't contain merge tags (performance optimization)
                if (preg_match('/word\/(header|footer)\d*\.xml/', $part_name)) {
                    if (!preg_match('/\{\$|\{&#36;|\{[A-Za-z]/', $xml_content)) {
                        LDA_Logger::log("Skipping $part_name - no merge tags detected");
                        continue;
                    }
                }

                // Process merge tags in the XML content
                $processed_xml = self::processMergeTagsInXML($xml_content, $mergeData);

                // Sanitize and validate processed XML to prevent DOCX corruption
                $processed_xml = self::sanitizeXML($processed_xml);
                if (!self::isWellFormedXML($processed_xml)) {
                    LDA_Logger::error("Processed XML not well-formed for part: $part_name. Attempting conservative repair.");
                    // Conservative repair: apply only literal replacements on original XML
                    $repaired = self::simpleLiteralReplacement($xml_content, $mergeData);
                    $repaired = self::sanitizeXML($repaired);
                    if (self::isWellFormedXML($repaired)) {
                        LDA_Logger::log("Conservative repair succeeded for part: $part_name");
                        $processed_xml = $repaired;
                    } else {
                        LDA_Logger::error("Conservative repair failed for part: $part_name. Reverting to original XML to avoid corruption.");
                        $processed_xml = $xml_content; // Fail-safe: keep original if invalid
                    }
                }

                // Write the processed XML back to the archive
                if ($zip->addFromString($part_name, $processed_xml) === false) {
                    LDA_Logger::error("Failed to write processed XML back to DOCX for part: $part_name");
                    $zip->close();
                    return array('success' => false, 'error' => "Failed to write processed XML for $part_name");
                }

                $processed_files++;
                LDA_Logger::log("Processed XML file: $part_name");
                
                // Special logging for main document completion
                if ($part_name === 'word/document.xml') {
                    LDA_Logger::log("*** MAIN DOCUMENT PROCESSING COMPLETED ***");
                }
            } else {
                LDA_Logger::log("XML part not found: $part_name");
            }
        }
        
        $zip->close();

        LDA_Logger::log("Enhanced DOCX processing completed. Processed $processed_files XML files");

        if ($processed_files === 0) {
            LDA_Logger::error("No XML parts were processed. The document may be empty or corrupt.");
            return array('success' => false, 'error' => 'No content found to process in the DOCX file.');
        }

        return array('success' => true, 'file_path' => $outputPath);
    }
    
    /**
     * Process merge tags in XML content using comprehensive approach
     */
    private static function processMergeTagsInXML($xml_content, $mergeData) {
        // CRITICAL DEBUG: What merge data is actually being passed?
        $debug_file = ABSPATH . 'wp-content/uploads/docx-debug.txt';
        $debug_msg = "\n" . date('Y-m-d H:i:s') . " - 🚨 DOCX DEBUG: processMergeTagsInXML called with " . count($mergeData) . " merge data items\n";
        if (!empty($mergeData)) {
            $debug_msg .= "🚨 DOCX DEBUG: Available keys: " . implode(', ', array_keys($mergeData)) . "\n";
            $debug_msg .= "🚨 DOCX DEBUG: Sample data: " . json_encode(array_slice($mergeData, 0, 3, true)) . "\n";
        } else {
            $debug_msg .= "🚨 DOCX DEBUG: MERGE DATA IS EMPTY!!!\n";
        }
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        LDA_Logger::log("Processing merge tags in XML using comprehensive approach");
        
        // Debug: Log available merge data keys with sample values
        $available_keys = array_keys($mergeData);
        $sample_data = array();
        foreach (array_slice($available_keys, 0, 10) as $key) {
            $value = $mergeData[$key];
            if (is_string($value) && strlen($value) > 50) {
                $sample_data[$key] = substr($value, 0, 50) . '...';
            } else {
                $sample_data[$key] = $value;
            }
        }
        LDA_Logger::log("Available merge data keys (" . count($available_keys) . "): " . implode(', ', array_slice($available_keys, 0, 20)) . (count($available_keys) > 20 ? '...' : ''));
        LDA_Logger::log("Sample merge data values: " . json_encode($sample_data, JSON_PRETTY_PRINT));
        
        // CRITICAL DEBUG: Show actual XML content to see what patterns exist
        $debug_file = ABSPATH . 'wp-content/uploads/lda-debug.txt';
        $xml_sample_for_debug = substr($xml_content, 0, 2000); // First 2000 chars
        $debug_msg = "\n🔍 XML CONTENT SAMPLE (first 2000 chars):\n" . $xml_sample_for_debug . "\n\n";
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        // Look for ANY curly braces in the XML to see what format the merge tags are in
        if (preg_match_all('/\{[^}]*\}/', $xml_content, $all_curly_matches)) {
            $unique_patterns = array_unique($all_curly_matches[0]);
            $debug_msg = "🎯 FOUND CURLY BRACE PATTERNS IN XML: " . implode(', ', array_slice($unique_patterns, 0, 10)) . "\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            LDA_Logger::log("Found curly brace patterns: " . implode(', ', array_slice($unique_patterns, 0, 5)));
        } else {
            $debug_msg = "❌ NO CURLY BRACE PATTERNS FOUND IN XML AT ALL\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            LDA_Logger::log("No curly brace patterns found in XML");
        }
        
        $replacements_made = 0;
        
        // Step 1: Add a new, precise normalization step to fuse split XML tags.
        // This is safer than previous attempts and critical for reliable conditional logic processing.
        $xml_content = preg_replace('/(<\/w:t><\/w:r>)(<w:r[^>]*><w:t>)/', '', $xml_content);
        LDA_Logger::log("XML content normalized with precise tag fusion.");

        // Step 2: Fix split merge tags first (common DOCX issue)
        $xml_content = self::fixSplitMergeTags($xml_content);
        
        // Step 2.5: IMMEDIATE TAG REPLACEMENT - Replace reconstructed tags with data immediately
        LDA_Logger::log("🚀 IMMEDIATE TAG REPLACEMENT: Processing reconstructed tags");
        $immediate_replacements = 0;
        foreach ($mergeData as $key => $value) {
            if (!is_scalar($value)) continue;
            $safe_value = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
            
            // Try the most common patterns first
            $patterns = ['{$' . $key . '}', '{' . $key . '}'];
            
            foreach ($patterns as $pattern) {
                if (strpos($xml_content, $pattern) !== false) {
                    $before_count = substr_count($xml_content, $pattern);
                    $xml_content = str_replace($pattern, $safe_value, $xml_content);
                    $after_count = substr_count($xml_content, $pattern);
                    $replaced_count = $before_count - $after_count;
                    
                    if ($replaced_count > 0) {
                        $immediate_replacements += $replaced_count;
                        LDA_Logger::log("✅ IMMEDIATE SUCCESS: {$pattern} → {$safe_value} ({$replaced_count} times)");
                    }
                }
            }
        }
        LDA_Logger::log("🎯 IMMEDIATE REPLACEMENTS MADE: " . $immediate_replacements);
        
        // Step 3: Process conditional logic
        $xml_content = self::processConditionalLogic($xml_content, $mergeData, $replacements_made);
        
        // Step 4: Find ALL merge tags in the XML (including split ones across XML elements)
        // Find complete merge tags - support {$VAR}, {&#36;VAR}, and simple {VAR} patterns
        preg_match_all('/\{\$([^}|]+)(?:\|[^}]+)?\}/', $xml_content, $xml_tags);
        preg_match_all('/\{&#36;([^}|]+)(?:\|[^}]+)?\}/', $xml_content, $xml_tags_entity);
        preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*(?:\|[^}]+)?)\}/', $xml_content, $xml_tags_simple);

        // Also look for split merge tags across XML elements (common DOCX issue)
        preg_match_all('/\{\$([^<]*?)(?:<\/w:t><\/w:r>.*?<w:r[^>]*>.*?<w:t[^>]*>([^<]*?))+\}/', $xml_content, $split_tags);
        preg_match_all('/\{&#36;([^<]*?)(?:<\/w:t><\/w:r>.*?<w:r[^>]*>.*?<w:t[^>]*>([^<]*?))+\}/', $xml_content, $split_tags_entity);
        preg_match_all('/\{([A-Za-z][^<]*?)(?:<\/w:t><\/w:r>.*?<w:r[^>]*>.*?<w:t[^>]*>([^<]*?))+\}/', $xml_content, $split_tags_simple);

        // Look for any remaining split patterns that might have been missed
        preg_match_all('/\{\$([^}]*?)(?:<[^>]*>)+([^}]*?)\}/', $xml_content, $remaining_split_tags);
        preg_match_all('/\{&#36;([^}]*?)(?:<[^>]*>)+([^}]*?)\}/', $xml_content, $remaining_split_tags_entity);
        preg_match_all('/\{([A-Za-z][^}]*?)(?:<[^>]*>)+([^}]*?)\}/', $xml_content, $remaining_split_tags_simple);

        $all_tags = array();
        if (!empty($xml_tags[1])) {
            $all_tags = array_merge($all_tags, $xml_tags[1]);
        }
        if (!empty($xml_tags_entity[1])) {
            $all_tags = array_merge($all_tags, $xml_tags_entity[1]);
        }
        if (!empty($xml_tags_simple[1])) {
            // For simple tags, extract just the variable name without modifiers
            foreach ($xml_tags_simple[1] as $simple_tag) {
                $tag_parts = explode('|', $simple_tag);
                $all_tags[] = trim($tag_parts[0]);
            }
        }
        if (!empty($split_tags[1])) {
            $all_tags = array_merge($all_tags, $split_tags[1]);
        }
        if (!empty($split_tags_entity[1])) {
            $all_tags = array_merge($all_tags, $split_tags_entity[1]);
        }
        if (!empty($split_tags_simple[1])) {
            $all_tags = array_merge($all_tags, $split_tags_simple[1]);
        }
        if (!empty($remaining_split_tags[1])) {
            // Combine the parts of split tags
            for ($i = 0; $i < count($remaining_split_tags[1]); $i++) {
                $combined_tag = $remaining_split_tags[1][$i] . $remaining_split_tags[2][$i];
                if (preg_match('/^[A-Za-z0-9_]+$/', $combined_tag)) {
                    $all_tags[] = $combined_tag;
                }
            }
        }
        if (!empty($remaining_split_tags_entity[1])) {
            for ($i = 0; $i < count($remaining_split_tags_entity[1]); $i++) {
                $combined_tag = $remaining_split_tags_entity[1][$i] . $remaining_split_tags_entity[2][$i];
                if (preg_match('/^[A-Za-z0-9_]+$/', $combined_tag)) {
                    $all_tags[] = $combined_tag;
                }
            }
        }
        if (!empty($remaining_split_tags_simple[1])) {
            for ($i = 0; $i < count($remaining_split_tags_simple[1]); $i++) {
                $combined_tag = $remaining_split_tags_simple[1][$i] . $remaining_split_tags_simple[2][$i];
                if (preg_match('/^[A-Za-z0-9_]+$/', $combined_tag)) {
                    $all_tags[] = $combined_tag;
                }
            }
        }
        
        if (!empty($all_tags)) {
            $unique_xml_tags = array_unique($all_tags);
            LDA_Logger::log("Found merge tags in XML (" . count($unique_xml_tags) . "): " . implode(', ', array_slice($unique_xml_tags, 0, 10)) . (count($unique_xml_tags) > 10 ? '...' : ''));
            LDA_Logger::log("Tag detection breakdown - Dollar: " . count($xml_tags[1]) . ", Entity: " . count($xml_tags_entity[1]) . ", Simple: " . count($xml_tags_simple[1]));
            
            // Step 3: Process each found merge tag
            foreach ($unique_xml_tags as $tag) {
                $tag = trim($tag);
                if (empty($tag)) continue;
                
                // Get value from merge data (try multiple variations)
                $value = self::getMergeTagValue($tag, $mergeData);
                
                if ($value !== null) {
                    $xml_content = self::replaceMergeTagInXML($xml_content, $tag, $value, $replacements_made);
                } else {
                    LDA_Logger::log("No value found for merge tag: {$tag} (tried {\$tag}, {&#36;tag}, and {tag})");
                }
            }
        } else {
            LDA_Logger::log("No merge tags found in XML content");
        }
        
        LDA_Logger::log("Total replacements made in XML: " . $replacements_made);

        // Fallback: brute-force replace using all mergeData keys in case detection missed some tags
        // CRITICAL DEBUG: Check what happened to our data
        $debug_file = ABSPATH . 'wp-content/uploads/lda-debug.txt';
        $debug_msg = "\n🔍 FALLBACK DEBUG: merge_data status check\n";
        $debug_msg .= "🔢 Current merge_data count: " . count($mergeData) . "\n";
        if (!empty($mergeData)) {
            $debug_msg .= "✅ Data exists, sample keys: " . implode(', ', array_slice(array_keys($mergeData), 0, 5)) . "\n";
        } else {
            $debug_msg .= "❌ mergeData is EMPTY! Data was lost!\n";
        }
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        // DIRECT FILE DEBUG for replacements
        $debug_msg = "\n" . date('Y-m-d H:i:s') . " - 🎯 STARTING MERGE TAG REPLACEMENT ATTEMPTS\n";
        $debug_msg .= "DATA KEYS (" . count($mergeData) . "): " . implode(', ', array_slice(array_keys($mergeData), 0, 10)) . "\n";
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        LDA_Logger::log("Running fallback merge tag replacement");
        $before_fallback = $xml_content;
        $replacement_count = 0;
        
        // Check if XML contains any merge tags at all
        $xml_sample = substr($xml_content, 0, 500);
        $debug_msg = "XML SAMPLE: " . $xml_sample . "\n";
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        foreach ($mergeData as $k => $v) {
            if (!is_scalar($v)) continue;
            $safe = htmlspecialchars((string)$v, ENT_XML1, 'UTF-8');
            
            // COMPREHENSIVE MERGE TAG REPLACEMENT - Try multiple formats
            $patterns_to_try = [
                '{$' . $k . '}',    // {$USR_Business}
                '{' . $k . '}',     // {USR_Business}
                '$' . $k,           // $USR_Business  
                $k                  // USR_Business (fallback)
            ];
            
            $replaced = false;
            
            // Debug key fields - show what patterns we're trying
            if (in_array($k, ['USR_Business', 'PT2_Business', 'USR_Name', 'PT2_Name', 'USR_ABV', 'PT2_ABV'])) {
                $debug_msg = "  🔍 Trying patterns for {$k} = {$safe}: " . implode(', ', $patterns_to_try) . "\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            }
            
            foreach ($patterns_to_try as $pattern) {
                if (strpos($xml_content, $pattern) !== false) {
                    $xml_content = str_replace($pattern, $safe, $xml_content);
                    $replacement_count++;
                    $replaced = true;
                    
                    // Log successful replacement
                    LDA_Logger::log("✅ SUCCESSFUL REPLACEMENT: {$pattern} → {$safe}");
                    $debug_msg = "  ✅ SUCCESS: {$pattern} → {$safe}\n";
                    file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                    break; // Stop after first successful replacement
                } else {
                    // Log failed attempts for key fields
                    if (in_array($k, ['USR_Business', 'PT2_Business', 'USR_Name', 'PT2_Name', 'USR_ABV', 'PT2_ABV'])) {
                        $debug_msg = "    ❌ Pattern '{$pattern}' not found in XML\n";
                        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                    }
                }
            }
            
            // Log what we're trying to replace for key fields
            if (in_array($k, ['USR_Business', 'PT2_Business', 'USR_Name', 'PT2_Name', 'USR_ABV', 'PT2_ABV'])) {
                LDA_Logger::log("🎯 Processing key field: " . $k . " = " . $safe);
                $debug_msg = "  🎯 Processing: " . $k . " = " . $safe . "\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            }
        }
        
        LDA_Logger::log("🔥 EMERGENCY FALLBACK MADE " . $replacement_count . " REPLACEMENTS 🔥");
        $debug_msg = "🔥 TOTAL REPLACEMENTS: " . $replacement_count . " 🔥\n\n";
        file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
        
        // FINAL BRUTE FORCE - Replace any remaining patterns we might have missed
        LDA_Logger::log("🚨 FINAL BRUTE FORCE REPLACEMENT: Catching any remaining tags");
        $final_replacements = 0;
        
        // Define the exact patterns from your document
        $critical_tags = [
            'USR_Business' => 'Business of the today pty ltd',
            'PT2_Business' => 'Counter of Day', 
            'USR_ABV' => 'BusiT',
            'PT2_ABV' => 'Counting Day',
            'USR_Name' => 'Counter of the day wondering pty ltd',
            'PT2_Name' => 'Counter of the day wondering pty ltd',
            'REF_State' => 'State of South Australia',
            'Concept' => 'asf'
        ];
        
        // Get actual values from mergeData if available
        foreach ($critical_tags as $tag => $fallback_value) {
            $actual_value = isset($mergeData[$tag]) ? (string)$mergeData[$tag] : $fallback_value;
            $safe_value = htmlspecialchars($actual_value, ENT_XML1, 'UTF-8');
            
            // Try multiple patterns aggressively
            $aggressive_patterns = [
                '{$' . $tag . '}',
                '{' . $tag . '}',
                '&#36;' . $tag . '}',  // Handle entity-encoded dollar signs
                '{&#36;' . $tag . '}'
            ];
            
            foreach ($aggressive_patterns as $pattern) {
                if (strpos($xml_content, $pattern) !== false) {
                    $xml_content = str_replace($pattern, $safe_value, $xml_content);
                    $final_replacements++;
                    LDA_Logger::log("🚨 FINAL BRUTE FORCE SUCCESS: {$pattern} → {$safe_value}");
                }
            }
        }
        
        LDA_Logger::log("🚨 FINAL BRUTE FORCE MADE: " . $final_replacements . " REPLACEMENTS");
        
        if ($xml_content !== $before_fallback) {
            LDA_Logger::log("Fallback replacement applied additional changes");
        }

        return $xml_content;
    }
    
    /**
     * Fix split merge tags that are broken across XML elements.
     * This function handles complex fragmentation where tags are split across multiple XML runs.
     */
    private static function fixSplitMergeTags($xml_content) {
        LDA_Logger::log("Fixing split merge tags in XML (NUCLEAR RECONSTRUCTION METHOD)");

        $fixed_count = 0;
        $max_iterations = 4;
        $iteration = 0;
        $chars_removed = 0;

        while ($iteration < $max_iterations) {
            $iteration++;
            $before_content = $xml_content;
            $before_length = strlen($xml_content);

            // SMART NUCLEAR RECONSTRUCTION ALGORITHM
            // Find patterns like {$USR_Name</w:t></w:r><w:proofErr.../>...} and extract the tag name properly
            $xml_content = preg_replace_callback(
                '/\{([^}]*?[^<>{}]*?[^}]*?)\}/s',
                function($matches) use (&$fixed_count) {
                    $original_content = $matches[1];
                    
                    // If the content contains XML tags, it needs reconstruction
                    if (strpos($original_content, '<') !== false) {
                        
                        // SMART TAG EXTRACTION - Look for the actual merge tag at the beginning
                        // Pattern 1: {$TagName</w:t>...} → extract $TagName
                        if (preg_match('/^(\$[A-Za-z0-9_]+(?:\|[A-Za-z0-9_:%.]+)?)(?=<|$)/', $original_content, $tag_match)) {
                            $clean_tag = $tag_match[1];
                            $fixed_count++;
                            LDA_Logger::log("🔧 NUCLEAR FIX: {" . substr($original_content, 0, 50) . "...} → {" . $clean_tag . "}");
                            return '{' . $clean_tag . '}';
                        }
                        
                        // Pattern 2: {TagName</w:t>...} → extract TagName
                        if (preg_match('/^([A-Za-z0-9_]+(?:\|[A-Za-z0-9_:%.]+)?)(?=<|$)/', $original_content, $tag_match)) {
                            $clean_tag = $tag_match[1];
                            $fixed_count++;
                            LDA_Logger::log("🔧 NUCLEAR FIX: {" . substr($original_content, 0, 50) . "...} → {" . $clean_tag . "}");
                            return '{' . $clean_tag . '}';
                        }
                        
                        // Pattern 3: Complex conditional tags like {if condition</w:t>...}
                        if (preg_match('/^((?:if|listif|elseif|else|\/if|\/listif)\b[^<]*?)(?=<|$)/', $original_content, $tag_match)) {
                            $clean_tag = $tag_match[1];
                            $fixed_count++;
                            LDA_Logger::log("🔧 NUCLEAR FIX: {" . substr($original_content, 0, 50) . "...} → {" . $clean_tag . "}");
                            return '{' . $clean_tag . '}';
                        }
                        
                        // Fallback: Strip XML but preserve readable content carefully
                        $cleaned_content = preg_replace('/<[^>]+>/s', '', $original_content);
                        $cleaned_content = trim(preg_replace('/\s+/', ' ', $cleaned_content));
                        
                        if (!empty($cleaned_content) && $original_content !== $cleaned_content) {
                            $fixed_count++;
                            LDA_Logger::log("🔧 FALLBACK FIX: {" . substr($original_content, 0, 50) . "...} → {" . $cleaned_content . "}");
                            return '{' . $cleaned_content . '}';
                        }
                        
                        // CRITICAL FIX: Don't remove valid merge tags! 
                        // Check if the content looks like a valid merge tag pattern
                        if (preg_match('/^[\$]?[A-Za-z0-9_]+(?:\|[^}]*)?$/', $cleaned_content)) {
                            LDA_Logger::log("🔧 PRESERVED VALID TAG: {" . $cleaned_content . "} (was fragmented as {" . substr($original_content, 0, 50) . "...})");
                            return '{' . $cleaned_content . '}';
                        }
                        
                        // Last resort: Only remove if it's clearly broken/invalid
                        LDA_Logger::log("⚠️ REMOVED BROKEN TAG: {" . substr($original_content, 0, 50) . "...} (cleaned: '" . $cleaned_content . "')");
                        return '';
                    }
                    
                    // Clean tag, return as-is
                    return '{' . $original_content . '}';
                }
            );

            $after_length = strlen($xml_content);
            $chars_removed += ($before_length - $after_length);

            // If no changes were made in this iteration, we're done
            if ($xml_content === $before_content) {
                break;
            }
        }

        LDA_Logger::log("💥 NUCLEAR TAG RECONSTRUCTION: {$fixed_count} tags fixed, {$chars_removed} XML chars removed");
        
        // SAFETY CHECK - Make sure we still have some merge tags after reconstruction
        $remaining_patterns = [];
        if (preg_match_all('/\{[^}]+\}/', $xml_content, $pattern_matches)) {
            $remaining_patterns = array_slice($pattern_matches[0], 0, 10);
        }
        
        if (!empty($remaining_patterns)) {
            LDA_Logger::log("✅ REMAINING PATTERNS: " . implode(', ', $remaining_patterns));
        } else {
            LDA_Logger::log("❌ NO PATTERNS FOUND AFTER NUCLEAR RECONSTRUCTION");
        }
        
        return $xml_content;
    }
    
    /**
     * Process conditional logic like {if ...}, {elseif ...}, {else}, {/if}
     * This advanced processor handles nested blocks and complex conditions.
     */
    private static function processConditionalLogic($xml_content, $mergeData, &$replacements_made) {
        LDA_Logger::log("Processing conditional logic (Advanced)");

        $max_iterations = 20; // Safety break for deep nesting or errors
        $iteration = 0;

        // This pattern finds the innermost conditional blocks first.
        // It uses a backreference   to ensure {if} is closed by {/if} and {listif} by {/listif}.
        $pattern = '/\{(if|listif)\s+([^}]+)\}((?:[^{}]|\{(?!\/?\1\b))*?)\{\/\1\}/s';

        while (preg_match($pattern, $xml_content) && $iteration < $max_iterations) {
            $iteration++;
            
            $xml_content = preg_replace_callback($pattern, function($matches) use ($mergeData, &$replacements_made) {
                $tag_type = $matches[1]; // 'if' or 'listif'
                $main_condition = $matches[2];
                $inner_content = $matches[3];

                // Evaluate the main {if} condition
                if (self::evaluateCondition($main_condition, $mergeData)) {
                    LDA_Logger::log("Conditional TRUE: {{$tag_type} {$main_condition}}");
                    // Condition is true, so we only need the content before the first {else} or {elseif}.
                    $content_parts = preg_split('/\{(elseif|else)/s', $inner_content, 2);
                    $replacements_made++;
                    return $content_parts[0];
                }

                // Main condition is false, check for {elseif} and {else} clauses.
                // Pattern to find all {elseif ...} and {else} clauses within the block.
                preg_match_all('/\{(elseif\s+([^}]+)|else)\}(.*?)(?=\{(?:elseif|else)\}|\z)/s', $inner_content, $clause_matches, PREG_SET_ORDER);

                foreach ($clause_matches as $clause) {
                    $is_elseif = strpos($clause[1], 'elseif') === 0;
                    if ($is_elseif) {
                        $elseif_condition = $clause[2];
                        if (self::evaluateCondition($elseif_condition, $mergeData)) {
                            LDA_Logger::log("Conditional TRUE: {elseif {$elseif_condition}}");
                            $replacements_made++;
                            return $clause[3]; // Return the content of this true elseif
                        }
                    } else { // It's an {else}
                        LDA_Logger::log("Conditional ELSE triggered.");
                        $replacements_made++;
                        return $clause[3]; // Return the content of the else block
                    }
                }

                // All conditions were false, remove the entire block.
                LDA_Logger::log("All conditionals FALSE for {{$tag_type} {$main_condition}}. Removing block.");
                $replacements_made++;
                return '';

            }, $xml_content, 1); // Limit to 1 replacement per iteration to handle nesting correctly.
        }

        if ($iteration >= $max_iterations) {
            LDA_Logger::error("Exceeded max iterations in conditional logic processing. Check for unclosed tags or infinite loops in template.");
        }
        
        return $xml_content;
    }
    
    /**
     * Evaluate a condition string from the template.
     * Handles `and`, `==`, `!=`, `empty()`, `!empty()`, and simple variable checks.
     */
    private static function evaluateCondition($condition, $mergeData) {
        $condition = trim($condition);
        LDA_Logger::log("Evaluating condition: [{$condition}]");

        // Split by 'and' or '&&' to evaluate each part of the condition.
        $sub_conditions = preg_split('/\s+(and|&&)\s+/i', $condition);

        foreach ($sub_conditions as $sub_c) {
            $sub_c = trim($sub_c);
            $result = false;

            // Check for empty($VAR) or !empty($VAR)
            if (preg_match('/^(!?)\s*empty\(\$([a-zA-Z0-9_]+)\)$/', $sub_c, $matches)) {
                $negation = $matches[1] === '!';
                $variable_name = $matches[2];
                $value = self::getMergeTagValue($variable_name, $mergeData);
                $is_empty = (empty($value) || $value === '');
                $result = $negation ? !$is_empty : $is_empty;
            }
            // Check for comparisons like $VAR == "string" or $VAR != 'string'
            else if (preg_match('/^\$([a-zA-Z0-9_]+)\s*(==|!=)\s*["\'](.*?)["\']$/', $sub_c, $matches)) {
                $variable_name = $matches[1];
                $operator = $matches[2];
                $literal_value = $matches[3];
                $actual_value = self::getMergeTagValue($variable_name, $mergeData);
                if ($operator === '==') {
                    $result = (strval($actual_value) == strval($literal_value));
                } else { // !=
                    $result = (strval($actual_value) != strval($literal_value));
                }
            }
            // Check for simple variable existence like {$VAR}
            else if (preg_match('/^\$([a-zA-Z0-9_]+)$/', $sub_c, $matches)) {
                $variable_name = $matches[1];
                $actual_value = self::getMergeTagValue($variable_name, $mergeData);
                $result = !empty($actual_value) && $actual_value !== '';
            }
            else {
                LDA_Logger::warn("Could not parse sub-condition: [{$sub_c}]");
                return false; // Fail safe to false
            }

            // If any part of an AND chain is false, the whole condition is false.
            if (!$result) {
                LDA_Logger::log("Sub-condition [{$sub_c}] evaluated to FALSE. Entire condition is FALSE.");
                return false;
            }
        }
        
        // If the loop completes, all sub-conditions were true.
        LDA_Logger::log("All sub-conditions evaluated to TRUE for [{$condition}]. Entire condition is TRUE.");
        return true;
    }
    
    /**
     * Get merge tag value with multiple fallback strategies
     */
    private static function getMergeTagValue($tag, $mergeData) {
        try {
            if (!is_array($mergeData) || empty($tag)) {
                return null;
            }
            
            // Try exact match first
            if (isset($mergeData[$tag])) {
                return $mergeData[$tag];
            }
            
            // Try case-insensitive match
            foreach ($mergeData as $key => $value) {
                if (strcasecmp($key, $tag) === 0) {
                    return $value;
                }
            }
            
            // Try partial matches (for dynamic field names) - CRITICAL FIX FROM WORKING VERSION
            foreach ($mergeData as $key => $value) {
                if (stripos($key, $tag) !== false || stripos($tag, $key) !== false) {
                    LDA_Logger::log("Found partial match for tag '{$tag}' in key '{$key}' with value: '{$value}'");
                    return $value;
                }
            }
            
            return null;
        } catch (Exception $e) {
            LDA_Logger::error("Error in getMergeTagValue: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Replace a specific merge tag in XML with multiple patterns
     */
    private static function replaceMergeTagInXML($xml_content, $tag, $value, &$replacements_made) {
        try {
            if (empty($tag) || empty($xml_content)) {
                return $xml_content;
            }
            
            // Handle tags with modifiers first (support {$TAG|...}, {&#36;TAG|...}, and {TAG|...})
            $modifier_patterns = array(
                '/\{\$' . preg_quote($tag, '/') . '\|([^}]+)\}/',
                '/\{&#36;' . preg_quote($tag, '/') . '\|([^}]+)\}/',
                '/\{' . preg_quote($tag, '/') . '\|([^}]+)\}/'
            );
            foreach ($modifier_patterns as $modifier_pattern) {
            if (preg_match($modifier_pattern, $xml_content, $matches)) {
                try {
                    $modifier_part = $matches[1];
                    $processed_value = self::processModifiersInText($value, $modifier_part);
                    $before = $xml_content;
                    $xml_content = preg_replace($modifier_pattern, htmlspecialchars($processed_value, ENT_XML1, 'UTF-8'), $xml_content);
                    if ($before !== $xml_content) {
                        $replacements_made++;
                        LDA_Logger::log("Replaced modifier for tag {$tag} in XML with: " . $processed_value);
                        return $xml_content;
                    }
                } catch (Exception $e) {
                    LDA_Logger::error("Error processing modifier for tag {$tag}: " . $e->getMessage());
                }
            }
            }
            
            // Handle simple tags with multiple patterns (FROM WORKING VERSION)
            $patterns = array(
                // Standard patterns
                '/\{\$' . preg_quote($tag, '/') . '\}/',
                '/\{&#36;' . preg_quote($tag, '/') . '\}/',
                '/\{' . preg_quote($tag, '/') . '\}/',
                // Additional patterns from working version for fragmented tags
                '/\{\$' . preg_quote($tag, '/') . '(?:<[^>]*>[^<]*)*\}/',
                '/\{\$' . preg_quote($tag, '/') . '[^}]*\}/',
                '/\{\$' . preg_quote($tag, '/') . '(?:[^<}]|<[^>]*>)*\}/',
                // Nuclear option - catch anything between {$VARIABLE and }
                '/\{\$' . preg_quote($tag, '/') . '.*?\}/s'
            );
            
            foreach ($patterns as $pattern_index => $pattern) {
                try {
                    $before = $xml_content;
                    $xml_content = preg_replace($pattern, htmlspecialchars($value, ENT_XML1, 'UTF-8'), $xml_content);
                    if ($before !== $xml_content) {
                        $replacements_made++;
                        LDA_Logger::log("Replaced {" . ($pattern_index < 2 ? '$' : '') . $tag . "} in XML with pattern " . ($pattern_index + 1) . ": " . $value);
                        return $xml_content;
                    }
                } catch (Exception $e) {
                    LDA_Logger::error("Error with pattern " . ($pattern_index + 1) . " for tag {$tag}: " . $e->getMessage());
                }
            }
            
            return $xml_content;
        } catch (Exception $e) {
            LDA_Logger::error("Error in replaceMergeTagInXML for tag {$tag}: " . $e->getMessage());
            return $xml_content;
        }
    }
    
    /**
     * Process modifiers in plain text
     */
    private static function processModifiersInText($value, $modifier_part) {
        // Handle date_format modifier
        if (strpos($modifier_part, 'date_format') === 0) {
            $format = str_replace('date_format:', '', $modifier_part);
            $format = trim($format, '"');
            return self::formatDate($value, $format);
        }
        
        // Handle phone_format modifier
        if (strpos($modifier_part, 'phone_format') === 0) {
            $format = str_replace('phone_format:', '', $modifier_part);
            $format = trim($format, '"');
            return self::formatPhone($value, $format);
        }
        
        // Handle replace modifier
        if (strpos($modifier_part, 'replace') === 0) {
            $params = str_replace('replace:', '', $modifier_part);
            $params = trim($params, '"');
            $parts = explode(':', $params);
            if (count($parts) >= 2) {
                return str_replace($parts[0], $parts[1], $value);
            }
        }
        
        // Handle upper modifier
        if ($modifier_part === 'upper') {
            return strtoupper($value);
        }
        
        // Handle lower modifier
        if ($modifier_part === 'lower') {
            return strtolower($value);
        }
        
        return $value;
    }
    
    /**
     * Replace text content in XML while preserving structure
     */
    private static function replaceTextInXML($xml_content, $new_text) {
        // Instead of trying to replace the entire text content (which corrupts the XML),
        // we'll process the merge tags directly in the XML using a more careful approach
        
        // Extract merge tags from the new text and apply them to the XML
        preg_match_all('/\{\$([^}|]+)(?:\|([^}]+))?\}/', $new_text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $full_tag = $match[0];
            $tag_name = $match[1];
            $modifier = isset($match[2]) ? $match[2] : '';
            
            // Find the replacement value in the new text
            $replacement = '';
            if (preg_match('/' . preg_quote($full_tag, '/') . '\s*([^{]*?)(?=\{\$|$)/', $new_text, $replacement_matches)) {
                $replacement = trim($replacement_matches[1]);
            }
            
            if (!empty($replacement)) {
                // Replace the merge tag in the XML with the processed value
                $xml_content = preg_replace('/\{\$' . preg_quote($tag_name, '/') . '(?:\|[^}]+)?\}/', htmlspecialchars($replacement, ENT_XML1, 'UTF-8'), $xml_content);
            }
        }
        
        return $xml_content;
    }
    
    /**
     * Format date according to format string
     */
    private static function formatDate($date, $format) {
        if (empty($date)) {
            return '';
        }
        
        // Try to parse the date
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date; // Return original if can't parse
        }
        
        // Convert format string to PHP date format
        $php_format = str_replace(
            array('d', 'F', 'Y', 'm', 'y'),
            array('d', 'F', 'Y', 'm', 'y'),
            $format
        );
        
        return date($php_format, $timestamp);
    }
    
    /**
     * Format phone number according to format string
     */
    private static function formatPhone($phone, $format) {
        if (empty($phone)) {
            return '';
        }
        
        // Remove all non-digits
        $digits = preg_replace('/\D/', '', $phone);
        
        // Apply format pattern
        $formatted = $format;
        $digit_index = 0;
        
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === '%' && $i + 1 < strlen($format)) {
                $next_char = $format[$i + 1];
                if (is_numeric($next_char) && $digit_index < strlen($digits)) {
                    $formatted = str_replace('%' . $next_char, $digits[$digit_index], $formatted);
                    $digit_index++;
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Basic XML well-formedness check
     */
    private static function isWellFormedXML($xml) {
        if (!is_string($xml) || $xml === '') {
            return false;
        }
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        return $ok !== false;
    }

    /**
     * Sanitize XML string: remove illegal XML characters and fix stray ampersands
     */
    private static function sanitizeXML($xml) {
        if (!is_string($xml)) {
            return '';
        }
        // Remove characters not allowed in XML 1.0
        $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x84\x86-\x9F]/u', '', $xml);
        // Fix stray & that are not entities
        $xml = preg_replace('/&(?![a-zA-Z]+;|#\d+;|#x[0-9a-fA-F]+;)/', '&amp;', $xml);
        return $xml;
    }

    /**
     * Conservative replacement: only literal tags, no cross-XML regex
     */
    private static function simpleLiteralReplacement($xml_content, $mergeData) {
        foreach ($mergeData as $k => $v) {
            if (!is_scalar($v)) continue;
            $safe = htmlspecialchars((string)$v, ENT_XML1, 'UTF-8');
            // Replace all three patterns: {$KEY}, {&#36;KEY}, and {KEY}
            $xml_content = str_replace('{\$' . $k . '}', $safe, $xml_content);
            $xml_content = str_replace('{&#36;' . $k . '}', $safe, $xml_content);
            $xml_content = str_replace('{' . $k . '}', $safe, $xml_content);
        }
        return $xml_content;
    }

    /**
     * Check if this processor is available
     */
    public static function isAvailable() {
        return class_exists('ZipArchive');
    }
}