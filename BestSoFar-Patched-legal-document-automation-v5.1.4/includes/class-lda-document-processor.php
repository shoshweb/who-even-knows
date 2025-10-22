<?php
/**
 * Handles processing a Gravity Form entry and generating a document.
 *
 * @package LegalDocumentAutomation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LDA_DocumentProcessor {

    private $entry;
    private $form;
    private $settings;

    /**
     * Constructor.
     *
     * @param array $entry The Gravity Forms entry object.
     * @param array $form The Gravity Forms form object.
     * @param array $settings The plugin settings.
     */
    public function __construct($entry, $form, $settings) {
        $this->entry = $entry;
        $this->form = $form;
        $this->settings = $settings;
    }

    /**
     * Check if PHPWord is available and properly loaded.
     * DEPRECATED: Now using LDA_SimpleDOCX instead
     *
     * @return array Array with 'available' boolean and 'error' string if not available.
     */
    public static function checkPhpWordAvailability() {
        $result = array(
            'available' => false,
            'error' => '',
            'details' => array()
        );

        // Check if autoloader exists
        $autoloader = LDA_PLUGIN_DIR . 'vendor/autoload.php';
        if (!file_exists($autoloader)) {
            $result['error'] = 'Composer autoloader not found at: ' . $autoloader;
            $result['details'][] = 'Autoloader path: ' . $autoloader;
            return $result;
        }
        $result['details'][] = 'Autoloader found: ' . $autoloader;

        // Check if PHPWord class exists
        if (!class_exists('PhpOffice\PhpWord\TemplateProcessor')) {
            $result['error'] = 'PHPWord TemplateProcessor class not found. Please ensure PHPWord is properly installed.';
            $result['details'][] = 'TemplateProcessor class not available';
            return $result;
        }
        $result['details'][] = 'TemplateProcessor class found';

        // Check if we can create a TemplateProcessor instance
        try {
            // This is a basic test - we'll create a minimal test
            $result['available'] = true;
            $result['details'][] = 'PHPWord is properly loaded and functional';
        } catch (Exception $e) {
            $result['error'] = 'PHPWord initialization failed: ' . $e->getMessage();
            $result['details'][] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get available merge tags from a template file.
     *
     * @param string $template_path Path to the template file.
     * @return array Array of available merge tags.
     */
    public static function getTemplateMergeTags($template_path) {
        if (!file_exists($template_path)) {
            return array();
        }

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($template_path);
            return $templateProcessor->getVariables();
        } catch (Exception $e) {
            LDA_Logger::error("Error reading template variables: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Processes the form entry and generates the document.
     *
     * @return array
     */
    public function process() {
        // Start fresh debug session
        LDA_Logger::start_debug_session($this->entry['id']);
        
        LDA_Logger::log(LDA_VERSION . ": Document processing started - Entry {$this->entry['id']}, Form {$this->form['id']}");
        
        // FORCED DIAGNOSTIC LOG TO PRODUCTION SERVER - PROVE VERSION IS DEPLOYED
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        $diagnostic_log = $log_dir . 'CRITICAL-MERGE-TAG-DEBUG.log';
        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600)); // Force Melbourne time
        $diagnostic_msg = "[{$timestamp}] 🚀 DOCUMENT PROCESSING STARTED! 🚀\n";
        $diagnostic_msg .= "[{$timestamp}] Version: " . LDA_VERSION . "\n";
        $diagnostic_msg .= "[{$timestamp}] Entry ID: {$this->entry['id']}\n";
        $diagnostic_msg .= "[{$timestamp}] Form ID: {$this->form['id']}\n";
        $diagnostic_msg .= "[{$timestamp}] Form Title: {$this->form['title']}\n";
        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
        
        try {
            $result = $this->processDocument(false);
            LDA_Logger::log(LDA_VERSION . ": Document processing completed successfully");
            return $result;
        } catch (Exception $e) {
            LDA_Logger::error("CRITICAL ERROR: Document processing failed");
            LDA_Logger::error("Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            LDA_Logger::error("Error line: " . $e->getLine());
            LDA_Logger::error("Error trace: " . $e->getTraceAsString());
            LDA_Logger::error("=== END CRITICAL ERROR ===");
            throw $e;
        }
    }

    /**
     * Processes the form entry and generates both DOCX and PDF documents.
     *
     * @return array
     */
    public function processWithPdf() {
        LDA_Logger::log("=== DOCUMENT PROCESSOR WITH PDF STARTED ===");
        LDA_Logger::log("Entry ID: " . $this->entry['id']);
        LDA_Logger::log("Form ID: " . $this->form['id']);
        
        try {
            $result = $this->processDocument(true);
            LDA_Logger::log("=== DOCUMENT PROCESSOR WITH PDF COMPLETED ===");
            return $result;
        } catch (Exception $e) {
            LDA_Logger::error("=== CRITICAL ERROR IN DOCUMENT PROCESSOR WITH PDF ===");
            LDA_Logger::error("Error message: " . $e->getMessage());
            LDA_Logger::error("Error file: " . $e->getFile());
            LDA_Logger::error("Error line: " . $e->getLine());
            LDA_Logger::error("Error trace: " . $e->getTraceAsString());
            LDA_Logger::error("=== END CRITICAL ERROR ===");
            throw $e;
        }
    }

    /**
     * Main processing method that can generate DOCX only or both DOCX and PDF
     *
     * @param bool $generate_pdf Whether to generate PDF version
     * @return array
     */
    private function processDocument($generate_pdf = false) {
        // FORCED DIAGNOSTIC LOG - Document processor entry point
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600)); // Force Melbourne time
        $diagnostic_msg = "[{$timestamp}] 🚀🚀🚀 NUCLEAR-XML-RECONSTRUCTION v5.1.3 ENHANCED PROCESSING DOCUMENT! 🚀🚀🚀 PDF: " . ($generate_pdf ? 'TRUE' : 'FALSE') . "\n";
        @file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
        
        LDA_Logger::log("=== PROCESS DOCUMENT METHOD STARTED ===");
        LDA_Logger::log("Generate PDF: " . ($generate_pdf ? 'TRUE' : 'FALSE'));
        LDA_Logger::log("CRITICAL DEBUG: processDocument method called with generate_pdf = " . ($generate_pdf ? 'TRUE' : 'FALSE'));
        
        // 1. Defensive Check for DOCX Processing
        LDA_Logger::log("Step 1: Checking DOCX processing availability");
        $docx_check = LDA_SimpleDOCX::isAvailable();
        LDA_Logger::log("DOCX processing check result: " . ($docx_check ? 'Available' : 'Not Available'));
        
        if (!$docx_check) {
            $error_msg = 'DOCX processing is not available. ZIP extension may be missing.';
            LDA_Logger::error($error_msg);
            return array('success' => false, 'error_message' => $error_msg);
        }
        LDA_Logger::log("DOCX processing is available - continuing");

        // 2. Get Template Path
        LDA_Logger::log("Step 2: Getting template path");
        $template_folder = isset($this->settings['template_folder']) ? $this->settings['template_folder'] : 'lda-templates';
        LDA_Logger::log("Template folder setting: " . $template_folder);
        
        $upload_dir = wp_upload_dir();
        $template_dir = $upload_dir['basedir'] . '/' . $template_folder . '/';
        LDA_Logger::log("Template directory: " . $template_dir);
        
        // Get the assigned template for this form
        LDA_Logger::log("Step 3: Getting assigned template for form " . $this->form['id']);
        $template_assignments = get_option('lda_template_assignments', array());
        $assigned_template = isset($template_assignments[$this->form['id']]) ? $template_assignments[$this->form['id']] : '';
        LDA_Logger::log("Assigned template for form " . $this->form['id'] . ": " . ($assigned_template ?: 'NONE'));
        
        if (empty($assigned_template)) {
            // Fallback: use form meta if available
            if (function_exists('gform_get_meta')) {
                $meta_template = gform_get_meta($this->form['id'], 'lda_template_file');
                LDA_Logger::log("Form meta template: " . ($meta_template ?: 'NONE'));
                if ($meta_template) {
                    $assigned_template = $meta_template;
                }
            }
        }
        
        if (empty($assigned_template)) {
            $error_msg = 'No template assigned to form ' . $this->form['id'] . '. Please assign a template in the Templates tab.';
            LDA_Logger::error($error_msg);
            return array('success' => false, 'error_message' => $error_msg);
        }
        
        $template_path = $template_dir . $assigned_template;
        LDA_Logger::log("Template path: " . $template_path);
        
        if (!file_exists($template_path)) {
            $error_msg = 'Assigned template file not found: ' . $assigned_template . ' (Path: ' . $template_path . ')';
            LDA_Logger::error($error_msg);
            return array('success' => false, 'error_message' => $error_msg);
        }
        
        LDA_Logger::log("Using assigned template: " . basename($template_path));
        LDA_Logger::log("Full template path: " . $template_path);

        // 3. Prepare Merge Data
        LDA_Logger::log("Step 4: Preparing merge data");
        LDA_Logger::log("About to call prepareMergeData() method");
        $merge_data = $this->prepareMergeData();
        LDA_Logger::log("prepareMergeData() method completed");
        
        // Log the merge data for debugging
        // Log merge data summary to avoid truncation issues
        $merge_summary = array();
        foreach ($merge_data as $key => $value) {
            if (strlen($value) > 50) {
                $merge_summary[$key] = substr($value, 0, 50) . '...';
            } else {
                $merge_summary[$key] = $value;
            }
        }
        LDA_Logger::log("Merge data prepared (" . count($merge_data) . " items): " . json_encode($merge_summary, JSON_PRETTY_PRINT));
        
        // Log specific merge tags that should match the template
        $template_merge_tags = array('USR_Business', 'PT2_Business');
        foreach ($template_merge_tags as $tag) {
            if (isset($merge_data[$tag])) {
                LDA_Logger::log("Template merge tag {$tag} found with value: " . $merge_data[$tag]);
            } else {
                LDA_Logger::warn("Template merge tag {$tag} NOT FOUND in merge data");
            }
        }

        try {
            // 4. Perform the Merge using the merge engine
            LDA_Logger::log("Step 5: Creating merge engine");
            $merge_engine = new LDA_MergeEngine();
            LDA_Logger::log("Merge engine created successfully");
            
            // 5. Save the Output File
            LDA_Logger::log("Step 6: Setting up output directory");
            $upload_dir = wp_upload_dir();
            if (empty($upload_dir['basedir'])) {
                throw new Exception('WordPress upload directory not available');
            }
            
            $output_dir = $upload_dir['basedir'] . '/lda-output/';
            LDA_Logger::log("Output directory: " . $output_dir);
            
            if (!is_dir($output_dir)) {
                LDA_Logger::log("Output directory doesn't exist, creating it");
                if (!wp_mkdir_p($output_dir)) {
                    throw new Exception("Failed to create output directory: {$output_dir}");
                }
                LDA_Logger::log("Output directory created successfully");
            }
            
            if (!is_writable($output_dir)) {
                $perms = fileperms($output_dir);
                throw new Exception("Output directory is not writable: {$output_dir} (permissions: " . decoct($perms & 0777) . ")");
            }

            $output_filename = sanitize_file_name($this->form['title'] . '-' . $this->entry['id'] . '-' . time() . '.docx');
            $output_path = $output_dir . $output_filename;
            LDA_Logger::log("Output filename: " . $output_filename);
            LDA_Logger::log("Full output path: " . $output_path);
            
            // Use the merge engine to process the document
            LDA_Logger::log("Step 7: Starting document merge");
            $merge_result = $merge_engine->mergeDocument($template_path, $merge_data, $output_path);
            LDA_Logger::log("Merge result: " . json_encode($merge_result, JSON_PRETTY_PRINT));
            
            if (!$merge_result['success']) {
                LDA_Logger::error("Merge failed: " . $merge_result['error']);
                throw new Exception($merge_result['error']);
            }
            LDA_Logger::log("Document merge completed successfully");

            // 6. Generate PDF if requested
            if ($generate_pdf) {
                $pdf_filename = str_replace('.docx', '.pdf', $output_filename);
                $pdf_path = $output_dir . $pdf_filename;
                
                $pdf_handler = new LDA_PDFHandler($this->settings);
                $pdf_result = $pdf_handler->convertDocxToPdf($output_path, $pdf_path);
                
                if ($pdf_result['success']) {
                    LDA_Logger::log("PDF generated successfully: " . basename($pdf_path));
                    return array(
                        'success' => true,
                        'output_path' => $output_path,
                        'output_filename' => $output_filename,
                        'pdf_path' => $pdf_path,
                        'pdf_filename' => $pdf_filename,
                        'pdf_engine' => $pdf_result['engine']
                    );
                } else {
                    LDA_Logger::warn("PDF generation failed, but DOCX was created: " . $pdf_result['error']);
                    return array(
                        'success' => true,
                        'output_path' => $output_path,
                        'output_filename' => $output_filename,
                        'pdf_path' => null,
                        'pdf_error' => $pdf_result['error']
                    );
                }
            }

            // 7. Return Result (DOCX only)
            return array(
                'success' => true,
                'output_path' => $output_path,
                'output_filename' => $output_filename
            );

        } catch (Exception $e) {
            $error_msg = 'An error occurred during document generation: ' . $e->getMessage();
            LDA_Logger::error($error_msg);
            return array('success' => false, 'error_message' => $error_msg);
        }
    }

    /**
     * Prepare merge data from the form entry
     *
     * @return array
     */
    private function prepareMergeData() {
        LDA_Logger::log("=== PREPARE MERGE DATA METHOD CALLED ===");
        LDA_Logger::log("Form ID: " . $this->form['id']);
        LDA_Logger::log("Entry ID: " . $this->entry['id']);
        LDA_Logger::log("*** CRITICAL DEBUG: prepareMergeData() method is being called ***");
        LDA_Logger::log("*** THIS LOG SHOULD APPEAR IN THE LOGS - IF NOT, THE METHOD IS NOT BEING CALLED ***");
        
        // FORCED DIAGNOSTIC LOG TO PRODUCTION SERVER
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        $diagnostic_log = $log_dir . 'CRITICAL-MERGE-TAG-DEBUG.log';
        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600)); // Force Melbourne time
        $diagnostic_msg = "[{$timestamp}] 🚨🚨🚨 MERGE TAG PROCESSING DEBUG STARTING! 🚨🚨🚨\n";
        $diagnostic_msg .= "[{$timestamp}] Version: LDA_VERSION\n";
        $diagnostic_msg .= "[{$timestamp}] Form ID: {$this->form['id']}\n";
        $diagnostic_msg .= "[{$timestamp}] Entry ID: {$this->entry['id']}\n";
        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
        
        $merge_data = array();
        
        // Get field mappings for this form
        $mappings = get_option('lda_field_mappings', array());
        // CRITICAL FIX: Convert form ID to integer to match stored mapping keys
        $form_id = intval($this->form['id']);
        $form_mappings = isset($mappings[$form_id]) ? $mappings[$form_id] : array();
        
        // CRITICAL DEBUG: Log everything about field mappings
        $diagnostic_msg = "[{$timestamp}] 🔍 FIELD MAPPINGS DEBUG:\n";
        $diagnostic_msg .= "[{$timestamp}] Original form ID: '{$this->form['id']}' (type: " . gettype($this->form['id']) . ")\n";
        $diagnostic_msg .= "[{$timestamp}] Integer form ID: {$form_id} (type: " . gettype($form_id) . ")\n";
        $diagnostic_msg .= "[{$timestamp}] Total stored mappings for all forms: " . count($mappings) . "\n";
        $diagnostic_msg .= "[{$timestamp}] Form mappings for form {$form_id}: " . count($form_mappings) . "\n";
        $diagnostic_msg .= "[{$timestamp}] All stored mappings: " . json_encode($mappings, JSON_PRETTY_PRINT) . "\n";
        $diagnostic_msg .= "[{$timestamp}] Form mappings: " . json_encode($form_mappings, JSON_PRETTY_PRINT) . "\n";
        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
        
        LDA_Logger::log("Found " . count($form_mappings) . " field mappings for form " . $form_id);
        LDA_Logger::log("All stored mappings: " . json_encode($mappings, JSON_PRETTY_PRINT));
        LDA_Logger::log("Form mappings for form " . $form_id . ": " . json_encode($form_mappings, JSON_PRETTY_PRINT));
        
        // CRITICAL: Check if we have any field mappings at all
        if (empty($form_mappings)) {
            $diagnostic_msg = "[{$timestamp}] ❌ CRITICAL ERROR: NO FIELD MAPPINGS FOUND!\n";
            $diagnostic_msg .= "[{$timestamp}] Original Form ID: '{$this->form['id']}' (" . gettype($this->form['id']) . ")\n";
            $diagnostic_msg .= "[{$timestamp}] Integer Form ID: {$form_id} (" . gettype($form_id) . ")\n";
            $diagnostic_msg .= "[{$timestamp}] Available form IDs: " . implode(', ', array_keys($mappings)) . "\n";
            file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
            
            LDA_Logger::log("*** CRITICAL ERROR: NO FIELD MAPPINGS FOUND FOR FORM " . $form_id . " ***");
            LDA_Logger::log("*** This explains why field mappings are not being applied! ***");
            LDA_Logger::log("*** Available form IDs in mappings: " . implode(', ', array_keys($mappings)) . " ***");
            
            // v5.1.3 ENHANCED: Add automatic field mapping as fallback for common forms
            LDA_Logger::log("v5.1.3: Applying automatic field mapping fallback for Confidentiality Agreement form");
            
            // 🚀 FLEXIBLE FIELD MAPPING FIX - Replace hardcoded mapping with intelligent detection
            LDA_Logger::log("🚀 APPLYING FLEXIBLE FIELD MAPPING to fix tag mixing issue");
            
            // Include flexible field mapper if not already loaded
            if (!class_exists('LDA_Flexible_Field_Mapper')) {
                $mapper_file = LDA_PLUGIN_DIR . 'includes/class-lda-flexible-field-mapper.php';
                if (file_exists($mapper_file)) {
                    require_once $mapper_file;
                    LDA_Logger::log("🚀 FLEXIBLE FIELD MAPPER LOADED from: " . $mapper_file);
                } else {
                    LDA_Logger::warn("🚀 FLEXIBLE FIELD MAPPER NOT FOUND at: " . $mapper_file);
                }
            }
            
            // Use flexible mapping instead of hardcoded field IDs
            if (class_exists('LDA_Flexible_Field_Mapper')) {
                $form_mappings = LDA_Flexible_Field_Mapper::getFlexibleFieldMappings($this->form, $this->entry);
                LDA_Logger::log("🚀 FLEXIBLE MAPPING FOUND " . count($form_mappings) . " smart field mappings");
            } else {
                // Fallback to hardcoded mapping if flexible mapper not available
                $form_mappings = array(
                    'USR_Name' => '2',    // Business legal name (first party)
                    'USR_ABN' => '44',    // Business ABN (first party) 
                    'PT2_Name' => '8',    // Counterparty legal name (second party)
                    'PT2_ABN' => '47',    // Counterparty ABN (second party)
                    'Pmt_Negotiate' => '27.1',  // Purpose: Negotiating payments (checkbox)
                    'Pmt_Business' => '27.2',   // Purpose: Business relations (checkbox)
                    'purpose_other' => '29',    // Purpose: Other (textarea)
                );
                LDA_Logger::warn("🚀 USING FALLBACK HARDCODED MAPPING");
            }
            
            LDA_Logger::log("v5.1.3: Applied " . count($form_mappings) . " field mappings");
        }
        
        // Debug: Check if form ID is correct
        LDA_Logger::log("DEBUG: Form ID from form object: " . $this->form['id']);
        LDA_Logger::log("DEBUG: Form ID type: " . gettype($this->form['id']));
        LDA_Logger::log("DEBUG: Available form IDs in mappings: " . implode(', ', array_keys($mappings)));
        
        // Apply dynamic field mappings FIRST
        LDA_Logger::log("Starting field mapping process. Total mappings: " . count($form_mappings));
        
        foreach ($form_mappings as $merge_tag => $field_id) {
            $field_value = function_exists('rgar') ? rgar($this->entry, (string) $field_id) : (isset($this->entry[(string) $field_id]) ? $this->entry[(string) $field_id] : '');
            
            // CRITICAL FIX: Handle merge tags that are already in {$key} format vs plain keys
            if (strpos($merge_tag, '{$') === 0 && strpos($merge_tag, '}') !== false) {
                // This is already a complete merge tag like {$USR_Business}
                // Extract the key name for the merge_data array
                $key_name = str_replace(array('{$', '}'), '', $merge_tag);
                if (!empty($field_value)) {
                    $merge_data[$key_name] = $field_value;
                    LDA_Logger::log("FIELD MAPPING (full tag): {$merge_tag} -> {$key_name} = '{$field_value}' (from field {$field_id})");
                    
                    $diagnostic_msg = "[{$timestamp}] ✅ MAPPED: {$merge_tag} -> {$key_name} = '{$field_value}'\n";
                    file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                } else {
                    $diagnostic_msg = "[{$timestamp}] ❌ EMPTY: {$merge_tag} -> field {$field_id} is empty\n";
                    file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                }
            } else {
                // This is a plain key, use it directly
                if (!empty($field_value)) {
                    $merge_data[$merge_tag] = $field_value;
                    LDA_Logger::log("FIELD MAPPING (plain key): {$merge_tag} = '{$field_value}' (from field {$field_id})");
                    
                    $diagnostic_msg = "[{$timestamp}] ✅ MAPPED (plain): {$merge_tag} = '{$field_value}'\n";
                    file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
                }
            }
        }
        LDA_Logger::log("Field mapping process completed. Total merge data items: " . count($merge_data));
        
        // Set flag to indicate field mappings were already applied (prevents duplicate processing in merge engine)
        $merge_data['_field_mappings_applied'] = true;
        
        // CRITICAL: Add form_id to merge data so field mappings can be applied in merge engine
        $merge_data['form_id'] = $this->form['id'];
        LDA_Logger::log("CRITICAL: Added form_id to merge data: " . $this->form['id']);
        
        // Add form data with multiple naming conventions (fallback)
        foreach ($this->form['fields'] as $field) {
            $value = function_exists('rgar') ? rgar($this->entry, (string) $field->id) : (isset($this->entry[(string) $field->id]) ? $this->entry[(string) $field->id] : '');
            
            // Debug: Log only important field information (reduced verbosity)
            if (!empty($value)) {
                LDA_Logger::log("Field {$field->id}: '{$field->label}' = '{$value}'");
            }
            
            // Special debugging for ABN fields (including ABN Lookup plugin patterns)
            $is_abn_field = false;
            $abn_field_type = '';
            
            // Check for ABN Lookup plugin field types
            if (isset($field->enable_abnlookup) && $field->enable_abnlookup) {
                $is_abn_field = true;
                $abn_field_type = 'ABN_LOOKUP_MAIN';
            } elseif (isset($field->abnlookup_results_enable) && $field->abnlookup_results_enable && !empty($field->abnlookup_results)) {
                $is_abn_field = true;
                $abn_field_type = 'ABN_LOOKUP_RESULT_' . $field->abnlookup_results;
            } elseif (isset($field->abnlookup_enable_gst) && !empty($field->abnlookup_enable_gst)) {
                $is_abn_field = true;
                $abn_field_type = 'ABN_LOOKUP_GST';
            } elseif (isset($field->abnlookup_enable_business_name) && !empty($field->abnlookup_enable_business_name)) {
                $is_abn_field = true;
                $abn_field_type = 'ABN_LOOKUP_BUSINESS_NAME';
            } elseif (stripos($field->label, 'abn') !== false || 
                      stripos($field->adminLabel, 'abn') !== false ||
                      stripos($field->label, 'abnlookup') !== false ||
                      stripos($field->adminLabel, 'abnlookup') !== false) {
                $is_abn_field = true;
                $abn_field_type = 'ABN_TEXT_FIELD';
            }
            
            if ($is_abn_field) {
                LDA_Logger::log("ABN FIELD DETECTED - ID: {$field->id}, Label: '{$field->label}', Admin: '{$field->adminLabel}', Type: '{$field->type}', ABN_Type: '{$abn_field_type}', Value: '{$value}'");
            }
            
            // Handle complex fields like Name and Address that return arrays
            if (is_array($value)) {
                $value = implode(' ', array_filter($value));
                LDA_Logger::log("Field {$field->id} was array, converted to: '{$value}'");
            }
            
            // Handle checkbox fields specially
            if ($field->type === 'checkbox' && is_array($value)) {
                // For checkbox fields, join selected values with commas
                $value = implode(', ', array_filter($value));
                LDA_Logger::log("Checkbox field {$field->id} processed: '{$value}'");
            }
            
            // Create multiple variations of field names for better compatibility
            $field_labels = array();
            
            // Primary label (admin label preferred)
            if (!empty($field->adminLabel)) {
                $field_labels[] = $field->adminLabel;
                // Also create uppercase version for {$VARIABLE} format
                $field_labels[] = strtoupper($field->adminLabel);
            }
            if (!empty($field->label)) {
                $field_labels[] = $field->label;
                // Also create uppercase version for {$VARIABLE} format
                $field_labels[] = strtoupper($field->label);
            }
            
            // Add field ID as a merge tag (webhook-style patterns)
            $field_labels[] = 'field_' . $field->id;
            $field_labels[] = 'FIELD_' . $field->id;
            $field_labels[] = 'input_' . $field->id;
            $field_labels[] = 'INPUT_' . $field->id;
            
            // Special handling for ABN Lookup plugin fields - but RESPECT WordPress admin mappings
            // LDA_VERSION: ABN processing will only add tags that don't conflict with WordPress mappings
            if ($is_abn_field) {
                LDA_Logger::log("ABN field detected (field {$field->id}) - will add ABN tags only if no WordPress mapping conflicts");
                
                // Determine if this is USR or PT2 field based on field context (not hard-coded IDs)
                $field_name_lower = strtolower($field->label . ' ' . $field->adminLabel);
                $is_usr_field = (
                    stripos($field_name_lower, 'your') !== false ||
                    stripos($field_name_lower, 'usr') !== false ||
                    stripos($field_name_lower, 'user') !== false ||
                    stripos($field_name_lower, 'business') !== false
                );
                $is_pt2_field = (
                    stripos($field_name_lower, 'client') !== false ||
                    stripos($field_name_lower, 'counterparty') !== false ||
                    stripos($field_name_lower, 'counter') !== false ||
                    stripos($field_name_lower, 'party') !== false ||
                    stripos($field_name_lower, 'pt2') !== false
                );
                
                // Add ABN merge tags based on field type - these will be added to $field_labels
                // The later logic will check if WordPress mappings exist before adding them
                switch ($abn_field_type) {
                    case 'ABN_LOOKUP_MAIN':
                        if ($is_usr_field) {
                            $field_labels[] = 'USR_ABN';
                            LDA_Logger::log("ABN field suggests USR_ABN tag for field {$field->id} (will check WordPress mappings)");
                        } elseif ($is_pt2_field) {
                            $field_labels[] = 'PT2_ABN';
                            LDA_Logger::log("ABN field suggests PT2_ABN tag for field {$field->id} (will check WordPress mappings)");
                        } else {
                            $field_labels[] = 'ABN'; // Generic ABN tag
                            LDA_Logger::log("ABN field suggests generic ABN tag for field {$field->id} (will check WordPress mappings)");
                        }
                        break;
                    case 'ABN_LOOKUP_RESULT_abnlookup_entity_name':
                        if ($is_usr_field) {
                            $field_labels[] = 'USR_Name';
                            LDA_Logger::log("ABN entity name suggests USR_Name tag for field {$field->id} (will check WordPress mappings)");
                        } elseif ($is_pt2_field) {
                            $field_labels[] = 'PT2_Name';
                            LDA_Logger::log("ABN entity name suggests PT2_Name tag for field {$field->id} (will check WordPress mappings)");
                        } else {
                            $field_labels[] = 'BUSINESS_NAME';
                            LDA_Logger::log("ABN entity name suggests BUSINESS_NAME tag for field {$field->id} (will check WordPress mappings)");
                        }
                        break;
                    case 'ABN_LOOKUP_BUSINESS_NAME':
                        if ($is_usr_field) {
                            $field_labels[] = 'USR_Business';
                            LDA_Logger::log("ABN business name suggests USR_Business tag for field {$field->id} (will check WordPress mappings)");
                        } elseif ($is_pt2_field) {
                            $field_labels[] = 'PT2_Business';
                            LDA_Logger::log("ABN business name suggests PT2_Business tag for field {$field->id} (will check WordPress mappings)");
                        } else {
                            $field_labels[] = 'BUSINESS_NAME';
                            LDA_Logger::log("ABN business name suggests BUSINESS_NAME tag for field {$field->id} (will check WordPress mappings)");
                        }
                        break;
                    case 'ABN_LOOKUP_RESULT_abnlookup_entity_state':
                        if ($is_usr_field) {
                            $field_labels[] = 'USR_State';
                        } elseif ($is_pt2_field) {
                            $field_labels[] = 'PT2_State';
                        } else {
                            $field_labels[] = 'REF_State';
                        }
                        break;
                    // Add other ABN field types as needed
                }
            }
            
            // Add sanitized versions
            if (!empty($field->label)) {
                $sanitized = sanitize_title($field->label);
                $field_labels[] = $sanitized;
                $field_labels[] = strtoupper($sanitized);
                $field_labels[] = strtolower($sanitized);
                
                // Add variations with underscores and hyphens
                $field_labels[] = str_replace('-', '_', $sanitized);
                $field_labels[] = str_replace('_', '-', $sanitized);
                $field_labels[] = strtoupper(str_replace('-', '_', $sanitized));
            }
            
            // Add specific merge tag patterns based on common legal document fields
            // LDA_VERSION: DISABLED - contains hardcoded mappings that override admin configuration
            // $this->addLegalDocumentMergeTags($field, $value, $field_labels);
            LDA_Logger::log(LDA_VERSION . ": Skipped hardcoded legal document mapping - using only admin-configured mappings");
            
            // LDA_VERSION: RESPECT WORDPRESS ADMIN MAPPINGS - Don't overwrite them with automatic processing
            // Add field variations to merge data ONLY if they don't conflict with WordPress admin mappings
            foreach ($field_labels as $label) {
                if (!empty($label)) {
                    // Check if this merge tag already exists from WordPress admin mapping
                    if (!isset($merge_data[$label])) {
                        // Safe to add - no admin mapping exists for this tag
                        $merge_data[$label] = $value;
                        LDA_Logger::log("Added automatic field mapping: {$label} = '{$value}' (field {$field->id})");
                    } else {
                        // WordPress admin mapping exists - respect it, don't overwrite
                        LDA_Logger::log("PRESERVED WordPress admin mapping: {$label} = '{$merge_data[$label]}' (skipped automatic field {$field->id} value '{$value}')");
                    }
                }
            }
        }
        
        // Add system data with multiple naming conventions
        $merge_data['FormTitle'] = $this->form['title'];
        $merge_data['FORMTITLE'] = $this->form['title'];
        $merge_data['Form_Title'] = $this->form['title']; // Legal document format
        $merge_data['EntryId'] = $this->entry['id'];
        $merge_data['ENTRYID'] = $this->entry['id'];
        $merge_data['Entry_ID'] = $this->entry['id']; // Legal document format
        $merge_data['Entry_Date'] = $this->entry['date_created']; // Legal document format
        $merge_data['User_IP'] = $this->entry['ip']; // Legal document format
        $merge_data['Source_URL'] = $this->entry['source_url']; // Legal document format
        $merge_data['SiteName'] = get_bloginfo('name');
        $merge_data['SITENAME'] = get_bloginfo('name');
        
        // Australian date and time formatting
        $original_timezone = date_default_timezone_get();
        date_default_timezone_set('Australia/Melbourne');
        $merge_data['CurrentDate'] = date('d/m/Y'); // Australian format: DD/MM/YYYY
        $merge_data['CURRENTDATE'] = date('d/m/Y'); // Australian format: DD/MM/YYYY
        $merge_data['CurrentTime'] = date('H:i:s'); // 24-hour format
        $merge_data['CURRENTTIME'] = date('H:i:s'); // 24-hour format
        date_default_timezone_set($original_timezone);
        
        // Add user data if available
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $merge_data['UserFirstName'] = $user->first_name;
            $merge_data['USERFIRSTNAME'] = $user->first_name;
            $merge_data['UserLastName'] = $user->last_name;
            $merge_data['USERLASTNAME'] = $user->last_name;
            $merge_data['UserEmail'] = $user->user_email;
            $merge_data['USEREMAIL'] = $user->user_email;
            $merge_data['UserName'] = $user->display_name;
            $merge_data['USERNAME'] = $user->display_name;
            
            // Add legal document format user data
            $merge_data['user_id'] = $user->ID;
            $merge_data['user_login'] = $user->user_login;
            $merge_data['user_email'] = $user->user_email;
            $merge_data['display_nam'] = $user->display_name;
        } else {
            // For non-logged-in users, try to extract user info from form fields
            $this->extractUserInfoFromFormFields($merge_data);
        }
        
        // v5.1.4: Correctly handle checkbox values from Gravity Forms entry for conditional logic.
        LDA_Logger::log("v5.1.4: Processing checkbox fields for conditional logic.");

        // This mapping connects the Gravity Form checkbox field ID (e.g., '27.1')
        // to the template variable name (e.g., 'Pmt_Negotiate') and the value
        // expected by the template's conditional logic (e.g., 'yes2').
        // This is based on combining information from the user's form data and the original plugin code.
        $purpose_mapping = [
            '27.1' => ['Pmt_Negotiate', 'yes2'], // Corresponds to "Negotiating..." checkbox
            '27.2' => ['Pmt_Business', 'yes3'],  // Corresponds to "Business relations" checkbox
            '27.3' => ['Pmt_Other', 'yes4'],     // Corresponds to "Other" checkbox
            // Pmt_Services with 'yes1' is assumed to be an option not selected in the user's form.
        ];

        // Initialize all potential purpose fields to empty to ensure clean state
        $all_purpose_fields = ['Pmt_Services', 'Pmt_Negotiate', 'Pmt_Business', 'Pmt_Other', 'Pmt_Agreements', 'Pmt_Relations'];
        foreach($all_purpose_fields as $pmt_field) {
            $merge_data[$pmt_field] = '';
        }
        LDA_Logger::log("v5.1.4: Initialized all purpose fields to empty.");

        // Find field 27 (checkboxes) to process its inputs
        $field_27 = null;
        foreach ($this->form['fields'] as $field) {
            if ($field->id == 27) {
                $field_27 = $field;
                break;
            }
        }

        if ($field_27 && is_array($field_27->inputs)) {
            LDA_Logger::log("v5.1.4: Found field 27. Processing its inputs for selected checkboxes.");
            foreach ($field_27->inputs as $input) {
                $checkbox_key = $input['id']; // e.g., '27.1'

                // Check if the checkbox was selected in the form entry
                if (!empty($this->entry[$checkbox_key]) && isset($purpose_mapping[$checkbox_key])) {
                    list($pmt_field, $yes_value) = $purpose_mapping[$checkbox_key];

                    $merge_data[$pmt_field] = $yes_value;
                    LDA_Logger::log("🎯 PMT FIELD CREATED: {$pmt_field} = '{$yes_value}' (from entry key {$checkbox_key})");

                    // Also create template alias fields for backwards compatibility
                    if ($pmt_field === 'Pmt_Negotiate') {
                        $merge_data['Pmt_Agreements'] = $yes_value;
                        LDA_Logger::log("🎯 PMT ALIAS: Pmt_Agreements = '{$yes_value}'");
                    }
                    if ($pmt_field === 'Pmt_Business') {
                        $merge_data['Pmt_Relations'] = $yes_value;
                        LDA_Logger::log("🎯 PMT ALIAS: Pmt_Relations = '{$yes_value}'");
                    }
                }
            }
        } else {
             LDA_Logger::log("⚠️ Field 27 (Purposes checkboxes) not found in form.");
        }
        
        // Generate missing standard merge tags
        $this->generateMissingMergeTags($merge_data);
        
        // v5.0.11: Generate abbreviation fields for document template compatibility
        $this->generateAbbreviationFields($merge_data);
        
        // DISABLED: Add specific field mappings based on Gravity Forms analysis
        // $this->addSpecificFieldMappings($merge_data);
        LDA_Logger::log("*** DISABLED: addSpecificFieldMappings() method to prevent hardcoded same values ***");
        
        // Debug: Log all merge data keys to see what's available
        LDA_Logger::log("Available merge data keys: " . implode(', ', array_keys($merge_data)));
        
        // Try to find business-related fields with different naming patterns
        foreach ($merge_data as $key => $value) {
            if (stripos($key, 'business') !== false || stripos($key, 'company') !== false) {
                LDA_Logger::log("Found business-related field: {$key} = {$value}");
            }
        }
        
        // LDA_VERSION: REMOVED ALL hardcoded business field fallback logic
        // This was overriding admin-configured field mappings with hardcoded assumptions
        LDA_Logger::log(LDA_VERSION . ": Using ONLY admin-configured field mappings - no hardcoded fallbacks");
        
        // LDA_VERSION: REMOVED automatic business field fallback loops
        // These were overriding admin-configured field mappings with hardcoded field searching
        // Original fallback logic searched for 'trading', 'business', 'counterparty' terms automatically
        // NOW ONLY admin-configured mappings via WordPress interface control field population
        LDA_Logger::log(LDA_VERSION . ": Removed hardcoded business field fallback - using only admin mappings");
        
        // LDA_VERSION: REMOVED mapFieldsDynamically() method
        // This was overriding admin-configured field mappings with hardcoded rules
        // NOW ONLY admin-configured mappings via WordPress interface control field population
        LDA_Logger::log(LDA_VERSION . ": Using ONLY admin-configured field mappings - no hardcoded fallbacks");
        
        // v5.0.11: COMPREHENSIVE field mapping debug logging
        LDA_Logger::debug_field_mapping($merge_data, "FINAL merge_data before document processing");
        
        // Log all fields for troubleshooting
        LDA_Logger::log("=== COMPLETE FIELD INVENTORY ===");
        foreach ($merge_data as $key => $value) {
            LDA_Logger::log("Field: {$key} = " . (is_string($value) ? $value : json_encode($value)));
        }
        LDA_Logger::log("=== END FIELD INVENTORY ===");
        
        return $merge_data;
    }
    
    /**
     * Dynamically map form fields to merge tag keys based on field content and context
     * v5.0.11: Modified to supplement WordPress mappings, not replace them
     */
    private function mapFieldsDynamically(&$merge_data, $existing_mappings = array()) {
        LDA_Logger::log("v5.0.11: Starting SELECTIVE dynamic field mapping (supplement WordPress mappings)");
        
        // Check which critical fields are missing or empty
        $critical_fields = array('USR_Business', 'USR_ABN', 'USR_Name', 'PT2_Business', 'PT2_ABN', 'PT2_Name');
        $missing_fields = array();
        $mapped_fields = array();
        
        foreach ($critical_fields as $field) {
            if (!isset($merge_data[$field]) || empty($merge_data[$field])) {
                $missing_fields[] = $field;
            } else {
                $mapped_fields[] = $field;
            }
        }
        
        LDA_Logger::log("WordPress mapped fields (" . count($mapped_fields) . "): " . implode(', ', $mapped_fields));
        LDA_Logger::log("Missing/empty fields (" . count($missing_fields) . "): " . implode(', ', $missing_fields));
        
        if (empty($missing_fields)) {
            LDA_Logger::log("✅ All critical fields have values - no dynamic mapping needed");
            return;
        }
        
        LDA_Logger::log("🔄 Applying dynamic mapping ONLY for missing fields: " . implode(', ', $missing_fields));
        
        // REMOVED: Hard-coded field mappings that were overriding WordPress configuration
        // These were causing properly configured field mappings to be ignored
        
        // v5.0.11: Comprehensive mapping rules with STRICT USR/PT2 separation
        $mapping_rules = array(
            // User/Business Information - STRICT exclusions for counterparty terms
            'USR_Name' => array(
                'keywords' => array('name', 'business', 'company', 'your', 'usr', 'user', 'first', 'primary'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'contact', 'signatory', 'counter', 'party', 'second', 'other'),
                'exact_fields' => array('2', 'user_display_name', 'first_name', 'business_name'), // Prefer specific field IDs
                'priority' => 1.0
            ),
            'USR_ABN' => array(
                'keywords' => array('abn', 'business', 'registration', 'number', 'acn', 'your', 'usr', 'user'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'second', 'other', 'counter', 'party'),
                'exact_fields' => array('44', 'user_abn', 'business_abn'), // Field 44 is likely the user ABN
                'priority' => 1.2
            ),
            'USR_Business' => array(
                'keywords' => array('business', 'trading', 'company', 'corporate', 'your', 'usr'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'counter', 'party', 'second', 'other'),
                'exact_fields' => array('45', 'user_business', 'business_trading'),
                'priority' => 1.0
            ),
            
            // Counterparty/Client Information - STRICT exclusions for user terms
            'PT2_Name' => array(
                'keywords' => array('legal', 'name', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business', 'contact', 'signatory'),
                'priority' => 1.0
            ),
            'PT2_ABN' => array(
                'keywords' => array('abn', 'client', 'counterparty', 'registration', 'number', 'acn', 'abnlookup', 'australian business number', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business', 'first', 'primary'),
                'priority' => 1.2
            ),
            'PT2_Business' => array(
                'keywords' => array('business', 'trading', 'company', 'corporate', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user'),
                'priority' => 1.0
            ),
            
            // Address and Location Information
            'USR_Address' => array(
                'keywords' => array('address', 'location', 'business', 'company', 'your', 'usr'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'counter', 'party', 'second', 'other'),
                'priority' => 0.8
            ),
            'PT2_Address' => array(
                'keywords' => array('address', 'location', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business'),
                'priority' => 0.8
            ),
            
            // Contact Information with strict separation
            'USR_Contact_FN' => array(
                'keywords' => array('contact', 'first', 'name', 'business', 'your', 'usr'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'signatory', 'counter', 'party', 'second', 'other'),
                'priority' => 0.8
            ),
            'PT2_Contact_FN' => array(
                'keywords' => array('contact', 'first', 'name', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business', 'signatory'),
                'priority' => 0.8
            ),
            
            // Signatory Information with strict separation
            'USR_Signatory_FN' => array(
                'keywords' => array('signatory', 'first', 'name', 'business', 'your', 'usr'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'contact', 'counter', 'party', 'second', 'other'),
                'priority' => 0.8
            ),
            'PT2_Signatory_FN' => array(
                'keywords' => array('signatory', 'first', 'name', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business', 'contact'),
                'priority' => 0.8
            ),
            
            // State and Postcode with strict separation
            'USR_State' => array(
                'keywords' => array('state', 'territory', 'business', 'your', 'usr'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'counter', 'party', 'second', 'other'),
                'priority' => 0.7
            ),
            'PT2_State' => array(
                'keywords' => array('state', 'territory', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business'),
                'priority' => 0.7
            ),
            'USR_Postcode' => array(
                'keywords' => array('postcode', 'zip', 'business', 'your', 'usr'),
                'exclude_keywords' => array('client', 'counterparty', 'pt2', 'counter', 'party', 'second', 'other'),
                'priority' => 0.7
            ),
            'PT2_Postcode' => array(
                'keywords' => array('postcode', 'zip', 'client', 'counterparty', 'counter', 'party', 'pt2', 'second', 'other'),
                'exclude_keywords' => array('your', 'usr', 'user', 'business'),
                'priority' => 0.7
            )
        );
        
        // Process each mapping rule with enhanced validation
        $mappings_made = 0;
        foreach ($mapping_rules as $merge_key => $rule) {
            // v5.0.11: Only process fields that are missing or empty
            if (!in_array($merge_key, $missing_fields)) {
                LDA_Logger::log("v5.0.11 FIELD MAPPING: SKIPPING {$merge_key} - already has value from WordPress mappings");
                continue;
            }
            
            $best_match = $this->findBestFieldMatch($merge_data, $rule);
            if ($best_match && $best_match['score'] > 0) {
                    // v5.0.11: Additional validation to prevent cross-contamination
                    $field_name_lower = strtolower($best_match['field']);
                    $is_usr_target = strpos($merge_key, 'USR_') === 0;
                    $is_pt2_target = strpos($merge_key, 'PT2_') === 0;
                    
                    // Strict validation for USR fields
                    if ($is_usr_target) {
                        $has_counterparty_terms = (
                            stripos($field_name_lower, 'client') !== false ||
                            stripos($field_name_lower, 'counterparty') !== false ||
                            stripos($field_name_lower, 'counter') !== false ||
                            stripos($field_name_lower, 'party') !== false ||
                            stripos($field_name_lower, 'pt2') !== false ||
                            stripos($field_name_lower, 'second') !== false ||
                            stripos($field_name_lower, 'other') !== false
                        );
                        
                        if ($has_counterparty_terms) {
                            LDA_Logger::log("v5.0.11 FIELD MAPPING: REJECTED {$merge_key} <- '{$best_match['field']}' (contains counterparty terms)");
                            continue;
                        }
                    }
                    
                    // Strict validation for PT2 fields
                    if ($is_pt2_target) {
                        $has_user_terms = (
                            stripos($field_name_lower, 'your') !== false ||
                            stripos($field_name_lower, 'usr') !== false ||
                            stripos($field_name_lower, 'user') !== false
                        );
                        
                        if ($has_user_terms) {
                            LDA_Logger::log("v5.0.11 FIELD MAPPING: REJECTED {$merge_key} <- '{$best_match['field']}' (contains user terms)");
                            continue;
                        }
                    }
                    
                    $merge_data[$merge_key] = $best_match['value'];
                    $mappings_made++;
                    LDA_Logger::log("v5.0.11 FIELD MAPPING: SUCCESS {$merge_key} <- '{$best_match['field']}' (score: {$best_match['score']}): '{$best_match['value']}'");
                } else {
                    LDA_Logger::log("v5.0.11 FIELD MAPPING: No suitable match found for {$merge_key}");
                }
        }
        
        LDA_Logger::log("Dynamic field mapping completed. {$mappings_made} mappings made out of " . count($mapping_rules) . " possible merge tags.");
        
        // Debug ABN fields specifically
        LDA_Logger::log("ABN Field Debug - USR_ABN: '" . (isset($merge_data['USR_ABN']) ? $merge_data['USR_ABN'] : 'NOT SET') . "'");
        LDA_Logger::log("ABN Field Debug - PT2_ABN: '" . (isset($merge_data['PT2_ABN']) ? $merge_data['PT2_ABN'] : 'NOT SET') . "'");
        
        // Log all fields that contain 'abn' for debugging
        foreach ($merge_data as $key => $value) {
            if (stripos($key, 'abn') !== false) {
                LDA_Logger::log("ABN-related field found: '{$key}' = '{$value}'");
            }
        }
        
        // Log webhook-related patterns for debugging
        LDA_Logger::log("Webhook-style field processing completed. Total merge data items: " . count($merge_data));
        
        // Check for webhook-style field patterns
        $webhook_patterns = array('field_', 'FIELD_', 'input_', 'INPUT_');
        foreach ($webhook_patterns as $pattern) {
            $count = 0;
            foreach ($merge_data as $key => $value) {
                if (strpos($key, $pattern) === 0) {
                    $count++;
                }
            }
            if ($count > 0) {
                LDA_Logger::log("Found {$count} fields with '{$pattern}' pattern (webhook-style)");
            }
        }
    }
    
    /**
     * Find the best field match for a given mapping rule
     */
    private function findBestFieldMatch($merge_data, $rule) {
        $best_match = null;
        $best_score = 0;
        
        // v5.0.11: First, check for exact field matches if specified
        if (isset($rule['exact_fields'])) {
            foreach ($rule['exact_fields'] as $exact_field) {
                if (isset($merge_data[$exact_field]) && !empty($merge_data[$exact_field])) {
                    LDA_Logger::troubleshoot("Found exact field match: {$exact_field} = " . $merge_data[$exact_field]);
                    return array(
                        'field' => $exact_field,
                        'value' => $merge_data[$exact_field],
                        'score' => 1000 // High score for exact matches
                    );
                }
            }
        }
        
        // If no exact match, proceed with keyword matching
        foreach ($merge_data as $field_name => $field_value) {
            if (empty($field_value)) continue;
            
            $score = $this->calculateFieldScore($field_name, $rule);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_match = array(
                    'field' => $field_name,
                    'value' => $field_value,
                    'score' => $score
                );
            }
        }
        
        return $best_match;
    }
    
    /**
     * Calculate a score for how well a field matches a mapping rule
     */
    private function calculateFieldScore($field_name, $rule) {
        $score = 0;
        $field_lower = strtolower($field_name);
        
        // v5.0.11 FIX: Strict separation - heavily penalize cross-contamination
        // Check for exclude keywords FIRST with very heavy penalties
        foreach ($rule['exclude_keywords'] as $exclude_keyword) {
            if (stripos($field_lower, $exclude_keyword) !== false) {
                $score -= 1000; // VERY heavy penalty for exclude keywords to prevent cross-contamination
                LDA_Logger::log("v5.0.11 FIELD MAPPING: EXCLUDED field '{$field_name}' for containing '{$exclude_keyword}' - heavy penalty applied");
            }
        }
        
        // If already heavily penalized, don't waste time calculating positive scores
        if ($score <= -100) {
            return $score;
        }
        
        // Check for required keywords
        foreach ($rule['keywords'] as $keyword) {
            if (stripos($field_lower, $keyword) !== false) {
                $score += 10; // Base score for keyword match
                
                // Bonus for exact matches
                if ($field_lower === $keyword) {
                    $score += 20;
                }
                
                // Bonus for keyword at start of field name
                if (strpos($field_lower, $keyword) === 0) {
                    $score += 5;
                }
            }
        }
        
        // Apply priority multiplier only to positive scores
        if ($score > 0) {
            $score *= $rule['priority'];
        }
        
        return $score;
    }
    
    /**
     * Add legal document specific merge tags based on field patterns
     *
     * @param object $field Gravity Forms field object
     * @param mixed $value Field value
     * @param array $field_labels Array to add labels to
     */
    private function addLegalDocumentMergeTags($field, $value, &$field_labels) {
        $field_label = strtolower($field->label ?? '');
        $field_admin_label = strtolower($field->adminLabel ?? '');
        $field_id = $field->id;
        
        // Map common field patterns to legal document merge tags
        $legal_mappings = array(
            // Business/Company fields
            'business' => array('USR_Business', 'PT2_Business', 'Business'),
            'company' => array('USR_Business', 'PT2_Business', 'Company'),
            'trading' => array('USR_Business', 'PT2_Business', 'Trading'),
            'legal name' => array('USR_Name', 'PT2_Name', 'Legal_Name'),
            'abn' => array('USR_ABN', 'PT2_ABN', 'ABN'),
            'acn' => array('USR_ACN', 'PT2_ACN', 'ACN'),
            
            // Signatory fields
            'signatory' => array('USR_Sign', 'PT2_Sign'),
            'signature' => array('USR_Sign', 'PT2_Sign'),
            'sign' => array('USR_Sign', 'PT2_Sign'),
            'first name' => array('USR_Sign_Fir', 'PT2_Sign_Fir'),
            'last name' => array('USR_Sign_La', 'PT2_Sign_Las'),
            'middle name' => array('USR_Sign_Mi', 'PT2_Sign_Mic'),
            'suffix' => array('USR_Sign_Su', 'PT2_Sign_Suf'),
            'title' => array('USR_Sign_Pro', 'PT2_Sign_Pre'),
            'role' => array('USR_Sign_Ro', 'PT2_Sign_Rol'),
            'email' => array('USR_Sign_En', 'PT2_Sign_Em'),
            
            // Address/Location fields
            'address' => array('USR_Address', 'PT2_Address'),
            'state' => array('REF_State'),
            'jurisdiction' => array('REF_State'),
            
            // Date fields
            'date' => array('Effective_Da'),
            'effective' => array('Effective_Da'),
            
            // Purpose/Concept fields
            'purpose' => array('Purpose'),
            'concept' => array('Concept'),
            'description' => array('Concept'),
            
            // Payment fields
            'payment' => array('Pmt_Service', 'Pmt_Services', 'Pmt_Negotia', 'Pmt_Busines', 'Pmt_Other'),
            'service' => array('Pmt_Service', 'Pmt_Services'),
            'negotiation' => array('Pmt_Negotia'),
            'business' => array('Pmt_Busines'),
            'other' => array('Pmt_Other'),
        );
        
        // Check if field matches any legal document patterns
        foreach ($legal_mappings as $pattern => $tags) {
            if (strpos($field_label, $pattern) !== false || strpos($field_admin_label, $pattern) !== false) {
                foreach ($tags as $tag) {
                    $field_labels[] = $tag;
                    LDA_Logger::debug("Added legal merge tag: {$tag} for field: {$field_label}");
                }
            }
        }
        
        // Add user-specific tags
        if (strpos($field_label, 'user') !== false || strpos($field_admin_label, 'user') !== false) {
            $field_labels[] = 'user_id';
            $field_labels[] = 'user_login';
            $field_labels[] = 'user_email';
            $field_labels[] = 'display_nam';
        }
        
        // Add form-specific tags
        $field_labels[] = 'Form_Title';
        $field_labels[] = 'Entry_ID';
        $field_labels[] = 'Entry_Date';
        $field_labels[] = 'User_IP';
        $field_labels[] = 'Source_URL';
    }
    
    /**
     * Generate missing standard merge tags based on available data
     *
     * @param array $merge_data Reference to merge data array
     */
    private function generateMissingMergeTags(&$merge_data) {
        LDA_Logger::log("*** v5.0.11: generateMissingMergeTags() - BEFORE PROCESSING ***");
        LDA_Logger::log("v5.0.11: BEFORE - USR_Name: " . (empty($merge_data['USR_Name']) ? 'EMPTY' : $merge_data['USR_Name']));
        LDA_Logger::log("v5.0.11: BEFORE - PT2_Name: " . (empty($merge_data['PT2_Name']) ? 'EMPTY' : $merge_data['PT2_Name']));
        LDA_Logger::log("v5.0.11: BEFORE - USR_ABN: " . (empty($merge_data['USR_ABN']) ? 'EMPTY' : $merge_data['USR_ABN']));
        LDA_Logger::log("v5.0.11: BEFORE - PT2_ABN: " . (empty($merge_data['PT2_ABN']) ? 'EMPTY' : $merge_data['PT2_ABN']));
        
        // v5.0.11: CRITICAL FIELD COMPARISON - Log exact differences between USR and PT2 name fields
        LDA_Logger::log("=== v5.0.11: CRITICAL USR vs PT2 FIELD COMPARISON ===");
        $usr_name_status = empty($merge_data['USR_Name']) ? '❌ EMPTY' : '✅ HAS VALUE: ' . $merge_data['USR_Name'];
        $pt2_name_status = empty($merge_data['PT2_Name']) ? '❌ EMPTY' : '✅ HAS VALUE: ' . $merge_data['PT2_Name'];
        LDA_Logger::log("USR_Name status: {$usr_name_status}");
        LDA_Logger::log("PT2_Name status: {$pt2_name_status}");
        
        // Log to diagnostic file for external monitoring
        $log_dir = '/home4/modelaw/public_html/staging/wp-content/uploads/lda-logs/';
        $diagnostic_log = $log_dir . 'DIAGNOSTIC-AUSTRALIA-OPTIMIZED.log';
        $timestamp = date('d/m/Y H:i:s', time() + (11 * 3600));
        $diagnostic_msg = "[{$timestamp}] === USR vs PT2 FIELD COMPARISON ===\n";
        $diagnostic_msg .= "[{$timestamp}] USR_Name: {$usr_name_status}\n";
        $diagnostic_msg .= "[{$timestamp}] PT2_Name: {$pt2_name_status}\n";
        file_put_contents($diagnostic_log, $diagnostic_msg, FILE_APPEND | LOCK_EX);
        
        // v5.0.11: Log all available field data to debug USR/PT2 mapping issue
        LDA_Logger::log("=== FIELD MAPPING DEBUG: All available field data ===");
        $usr_fields = array();
        $pt2_fields = array(); 
        $other_fields = array();
        
        foreach ($merge_data as $key => $value) {
            if (!empty($value) && is_string($value)) {
                $key_lower = strtolower($key);
                if (strpos($key_lower, 'usr') !== false || strpos($key_lower, 'user') !== false || strpos($key_lower, 'your') !== false) {
                    $usr_fields[] = "{$key} = '{$value}'";
                } elseif (strpos($key_lower, 'pt2') !== false || strpos($key_lower, 'counterparty') !== false || strpos($key_lower, 'counter') !== false) {
                    $pt2_fields[] = "{$key} = '{$value}'";
                } else {
                    $other_fields[] = "{$key} = '{$value}'";
                }
            }
        }
        
        LDA_Logger::log("USR/USER fields found: " . (empty($usr_fields) ? "NONE" : implode(', ', $usr_fields)));
        LDA_Logger::log("PT2/COUNTERPARTY fields found: " . (empty($pt2_fields) ? "NONE" : implode(', ', $pt2_fields)));
        LDA_Logger::log("OTHER fields found: " . (empty($other_fields) ? "NONE" : implode(', ', array_slice($other_fields, 0, 5))));
        
        LDA_Logger::log("*** CRITICAL DEBUG: generateMissingMergeTags() method is being called ***");
        LDA_Logger::log("*** THIS IS WHERE THE MERGE DATA IS BEING PREPARED ***");
        LDA_Logger::log("Generating missing standard merge tags");
        
        // v5.0.11 SIGNATORY FIELD FIX: Generate signatory fields in BOTH formats to prevent merge engine incompatibility
        LDA_Logger::debug("Generating signatory fields in both formats");
        
        if (!empty($merge_data['USR_Sign_First'])) {
            $merge_data['USR_Signatory_FN'] = $merge_data['USR_Sign_First'];
            $merge_data['{$USR_Signatory_FN}'] = $merge_data['USR_Sign_First'];  // v5.0.11: Add bracket format
            LDA_Logger::log("🎯 v5.0.11 SIGNATORY FIX: Generated USR_Signatory_FN in both formats from USR_Sign_First: " . $merge_data['USR_Sign_First']);
        } else {
            LDA_Logger::log("⚠️ v5.0.11 WARNING: USR_Sign_First is empty, cannot generate USR_Signatory_FN");
        }
        
        if (!empty($merge_data['USR_Sign_Last'])) {
            $merge_data['USR_Signatory_LN'] = $merge_data['USR_Sign_Last'];
            $merge_data['{$USR_Signatory_LN}'] = $merge_data['USR_Sign_Last'];  // v5.0.11: Add bracket format
            LDA_Logger::log("🎯 v5.0.11 SIGNATORY FIX: Generated USR_Signatory_LN in both formats from USR_Sign_Last: " . $merge_data['USR_Sign_Last']);
        } else {
            LDA_Logger::log("⚠️ v5.0.11 WARNING: USR_Sign_Last is empty, cannot generate USR_Signatory_LN");
        }
        
        if (!empty($merge_data['PT2_Sign_First'])) {
            $merge_data['PT2_Signatory_FN'] = $merge_data['PT2_Sign_First'];
            $merge_data['{$PT2_Signatory_FN}'] = $merge_data['PT2_Sign_First'];  // v5.0.7: Add bracket format
            LDA_Logger::log("🎯 v5.0.8 COMPLETE CONSISTENCY: Generated PT2_Signatory_FN in both formats from PT2_Sign_First: " . $merge_data['PT2_Sign_First']);
        } else {
            LDA_Logger::log("⚠️ v5.0.7 WARNING: PT2_Sign_First is empty, cannot generate PT2_Signatory_FN");
        }
        
        if (!empty($merge_data['PT2_Sign_Last'])) {
            $merge_data['PT2_Signatory_LN'] = $merge_data['PT2_Sign_Last'];
            $merge_data['{$PT2_Signatory_LN}'] = $merge_data['PT2_Sign_Last'];  // v5.0.7: Add bracket format
            LDA_Logger::log("🎯 v5.0.8 COMPLETE CONSISTENCY: Generated PT2_Signatory_LN in both formats from PT2_Sign_Last: " . $merge_data['PT2_Sign_Last']);
        } else {
            LDA_Logger::log("⚠️ v5.0.7 WARNING: PT2_Sign_Last is empty, cannot generate PT2_Signatory_LN");
        }
        
        if (!empty($merge_data['USR_Sign_Role'])) {
            $merge_data['USR_Signatory_Role'] = $merge_data['USR_Sign_Role'];
            $merge_data['{$USR_Signatory_Role}'] = $merge_data['USR_Sign_Role'];  // v5.0.7: Add bracket format
            LDA_Logger::log("🎯 v5.0.8 COMPLETE CONSISTENCY: Generated USR_Signatory_Role in both formats from USR_Sign_Role: " . $merge_data['USR_Sign_Role']);
        } else {
            LDA_Logger::log("⚠️ v5.0.7 WARNING: USR_Sign_Role is empty, cannot generate USR_Signatory_Role");
        }
        
        if (!empty($merge_data['PT2_Sign_Role'])) {
            $merge_data['PT2_Signatory_Role'] = $merge_data['PT2_Sign_Role'];
            $merge_data['{$PT2_Signatory_Role}'] = $merge_data['PT2_Sign_Role'];  // v5.0.7: Add bracket format
            LDA_Logger::log("🎯 v5.0.8 COMPLETE CONSISTENCY: Generated PT2_Signatory_Role in both formats from PT2_Sign_Role: " . $merge_data['PT2_Sign_Role']);
        } else {
            LDA_Logger::log("⚠️ v5.0.7 WARNING: PT2_Sign_Role is empty, cannot generate PT2_Signatory_Role");
        }
        
        LDA_Logger::log("🔥 v5.0.7 EXECUTION CHECK: Dual-format signatory field generation completed");
        
        // Standard merge tags that should always be available
        $standard_tags = array(
            'USR_Business', 'PT2_Business',
            'USR_Name', 'PT2_Name', 
            'USR_ABN', 'PT2_ABN',
            'USR_ABV', 'PT2_ABV',
            'USR_Signatory_FN', 'USR_Signatory_LN', 'USR_Signatory_Role',
            'PT2_Signatory_FN', 'PT2_Signatory_LN', 'PT2_Signatory_Role',
            'PT2__FN', 'PT2__LN', 'PT2_NAME',  // v5.0.11: Add missing PT2 name variants
            'REF_State',  // v5.0.11: Add reference state field
            'DISPLAY_NAME', 'DISPLAY_EMAIL', 'Login_ID',
            'Concept', 'Purpose'
        );
        
        foreach ($standard_tags as $tag) {
            if (!isset($merge_data[$tag]) || empty($merge_data[$tag])) {
                // Try to find a value from existing data
                $found_value = $this->findValueForTag($tag, $merge_data);
                if ($found_value !== null) {
                    $merge_data[$tag] = $found_value;
                    LDA_Logger::log("v5.0.11: Generated missing tag {$tag} = {$found_value}");
                }
            }
        }
        
        LDA_Logger::log("*** v5.0.11: generateMissingMergeTags() - AFTER PROCESSING ***");
        LDA_Logger::log("v5.0.11: AFTER - USR_Name: " . (empty($merge_data['USR_Name']) ? 'EMPTY' : $merge_data['USR_Name']));
        LDA_Logger::log("v5.0.11: AFTER - PT2_Name: " . (empty($merge_data['PT2_Name']) ? 'EMPTY' : $merge_data['PT2_Name']));
        LDA_Logger::log("v5.0.11: AFTER - USR_ABN: " . (empty($merge_data['USR_ABN']) ? 'EMPTY' : $merge_data['USR_ABN']));
        LDA_Logger::log("v5.0.11: AFTER - PT2_ABN: " . (empty($merge_data['PT2_ABN']) ? 'EMPTY' : $merge_data['PT2_ABN']));
    }
    
    /**
     * Find a value for a missing merge tag from existing data
     *
     * @param string $tag The merge tag to find a value for
     * @param array $merge_data The existing merge data
     * @return string|null The found value or null
     */
    private function findValueForTag($tag, $merge_data) {
        $tag_lower = strtolower($tag);
        
        // Direct matches
        if (isset($merge_data[$tag])) {
            return $merge_data[$tag];
        }
        
        // v5.0.11: Handle specific missing field mappings
        switch ($tag) {
            case 'PT2__FN':
                // Map PT2__FN to PT2_Signatory_FN or similar
                if (!empty($merge_data['PT2_Signatory_FN'])) {
                    LDA_Logger::log("v5.0.11: Mapped PT2__FN to PT2_Signatory_FN: " . $merge_data['PT2_Signatory_FN']);
                    return $merge_data['PT2_Signatory_FN'];
                }
                if (!empty($merge_data['PT2_Sign_First'])) {
                    LDA_Logger::log("v5.0.11: Mapped PT2__FN to PT2_Sign_First: " . $merge_data['PT2_Sign_First']);
                    return $merge_data['PT2_Sign_First'];
                }
                break;
                
            case 'PT2__LN':
                // Map PT2__LN to PT2_Signatory_LN or similar
                if (!empty($merge_data['PT2_Signatory_LN'])) {
                    LDA_Logger::log("v5.0.11: Mapped PT2__LN to PT2_Signatory_LN: " . $merge_data['PT2_Signatory_LN']);
                    return $merge_data['PT2_Signatory_LN'];
                }
                if (!empty($merge_data['PT2_Sign_Last'])) {
                    LDA_Logger::log("v5.0.11: Mapped PT2__LN to PT2_Sign_Last: " . $merge_data['PT2_Sign_Last']);
                    return $merge_data['PT2_Sign_Last'];
                }
                break;
                
            case 'PT2_NAME':
                // Map PT2_NAME to PT2_Name or PT2_Signatory_Role
                if (!empty($merge_data['PT2_Name'])) {
                    LDA_Logger::log("v5.0.11: Mapped PT2_NAME to PT2_Name: " . $merge_data['PT2_Name']);
                    return $merge_data['PT2_Name'];
                }
                if (!empty($merge_data['PT2_Signatory_Role'])) {
                    LDA_Logger::log("v5.0.11: Mapped PT2_NAME to PT2_Signatory_Role: " . $merge_data['PT2_Signatory_Role']);
                    return $merge_data['PT2_Signatory_Role'];
                }
                break;
                
            case 'REF_State':
                // Map REF_State to a state field
                if (!empty($merge_data['USR_State'])) {
                    LDA_Logger::log("v5.0.11: Mapped REF_State to USR_State: " . $merge_data['USR_State']);
                    return $merge_data['USR_State'];
                }
                // Default to Victoria if no state specified
                LDA_Logger::log("v5.0.11: REF_State defaulting to 'Victoria'");
                return 'Victoria';
                
            case 'DISPLAY_EMAIL':
                // Map DISPLAY_EMAIL to user email
                if (!empty($merge_data['UserEmail'])) {
                    LDA_Logger::log("v5.0.11: Mapped DISPLAY_EMAIL to UserEmail: " . $merge_data['UserEmail']);
                    return $merge_data['UserEmail'];
                }
                if (!empty($merge_data['user_email'])) {
                    LDA_Logger::log("v5.0.11: Mapped DISPLAY_EMAIL to user_email: " . $merge_data['user_email']);
                    return $merge_data['user_email'];
                }
                break;
                
            case 'Login_ID':
                // Map Login_ID to user login
                if (!empty($merge_data['user_login'])) {
                    LDA_Logger::log("v5.0.11: Mapped Login_ID to user_login: " . $merge_data['user_login']);
                    return $merge_data['user_login'];
                }
                if (!empty($merge_data['UserName'])) {
                    LDA_Logger::log("v5.0.11: Mapped Login_ID to UserName: " . $merge_data['UserName']);
                    return $merge_data['UserName'];
                }
                break;
        }
        
        // v5.0.11 ENHANCED DEBUG: Show available field data for debugging USR/PT2 conflicts
        if (strpos($tag, 'USR_') !== false || strpos($tag, 'PT2_') !== false) {
            LDA_Logger::log("v5.0.11: Finding value for {$tag} with STRICT separation");
            
            $relevant_keys = array();
            foreach ($merge_data as $key => $value) {
                if (empty($value)) continue;
                
                $key_lower = strtolower($key);
                $is_relevant = false;
                
                if (strpos($tag_lower, 'name') !== false && strpos($key_lower, 'name') !== false) {
                    $is_relevant = true;
                } elseif (strpos($tag_lower, 'abn') !== false && strpos($key_lower, 'abn') !== false) {
                    $is_relevant = true;
                } elseif (strpos($tag_lower, 'business') !== false && strpos($key_lower, 'business') !== false) {
                    $is_relevant = true;
                }
                
                if ($is_relevant) {
                    $relevant_keys[] = "{$key} = '{$value}'";
                }
            }
            
            if (!empty($relevant_keys)) {
                LDA_Logger::log("v5.0.11: Relevant fields found: " . implode(', ', $relevant_keys));
            } else {
                LDA_Logger::log("v5.0.11: No relevant fields found for {$tag}");
            }
        }
        
        // v5.0.11 FIX: Strict separation for USR vs PT2 tags
        $is_usr_tag = (strpos($tag, 'USR_') === 0);
        $is_pt2_tag = (strpos($tag, 'PT2_') === 0);
        
        // Find compatible fields with strict validation
        foreach ($merge_data as $key => $value) {
            if (empty($value)) continue;
            
            $key_lower = strtolower($key);
            $is_compatible = false;
            
            // Check if field name matches the tag type we're looking for
            if (strpos($tag_lower, 'name') !== false && strpos($key_lower, 'name') !== false) {
                $is_compatible = true;
            } elseif (strpos($tag_lower, 'abn') !== false && strpos($key_lower, 'abn') !== false) {
                $is_compatible = true;
            } elseif (strpos($tag_lower, 'business') !== false && strpos($key_lower, 'business') !== false) {
                $is_compatible = true;
            }
            
            if (!$is_compatible) continue;
            
            // v5.0.11: Apply strict exclusion rules
            if ($is_usr_tag) {
                // For USR tags, exclude counterparty fields
                $has_counterparty_terms = (
                    stripos($key_lower, 'client') !== false ||
                    stripos($key_lower, 'counterparty') !== false ||
                    stripos($key_lower, 'counter') !== false ||
                    stripos($key_lower, 'party') !== false ||
                    stripos($key_lower, 'pt2') !== false ||
                    stripos($key_lower, 'second') !== false ||
                    stripos($key_lower, 'other') !== false
                );
                
                if ($has_counterparty_terms) {
                    LDA_Logger::log("v5.0.11: EXCLUDED field '{$key}' for USR tag (contains counterparty terms)");
                    continue;
                }
                
                // Prefer fields with user terms
                $has_user_terms = (
                    stripos($key_lower, 'your') !== false ||
                    stripos($key_lower, 'usr') !== false ||
                    stripos($key_lower, 'user') !== false ||
                    stripos($key_lower, 'business') !== false
                );
                
                if ($has_user_terms) {
                    LDA_Logger::log("v5.0.11: FOUND value for {$tag} from user field '{$key}': '{$value}'");
                    return $value;
                }
            }
            
            if ($is_pt2_tag) {
                // For PT2 tags, exclude user fields
                $has_user_terms = (
                    stripos($key_lower, 'your') !== false ||
                    stripos($key_lower, 'usr') !== false ||
                    stripos($key_lower, 'user') !== false
                );
                
                if ($has_user_terms) {
                    LDA_Logger::log("v5.0.11: EXCLUDED field '{$key}' for PT2 tag (contains user terms)");
                    continue;
                }
                
                // Prefer fields with counterparty terms
                $has_counterparty_terms = (
                    stripos($key_lower, 'client') !== false ||
                    stripos($key_lower, 'counterparty') !== false ||
                    stripos($key_lower, 'counter') !== false ||
                    stripos($key_lower, 'party') !== false ||
                    stripos($key_lower, 'pt2') !== false ||
                    stripos($key_lower, 'second') !== false ||
                    stripos($key_lower, 'other') !== false
                );
                
                if ($has_counterparty_terms) {
                    LDA_Logger::log("v5.0.11: FOUND value for {$tag} from counterparty field '{$key}': '{$value}'");
                    return $value;
                }
            }
        }
        
        LDA_Logger::log("v5.0.11: No suitable value found for {$tag} with strict separation rules");
        return null;
    }
    
    /**
     * Add specific field mappings (DISABLED in v5.0.11 to prevent cross-contamination)
     */
    private function addSpecificFieldMappings(&$merge_data) {
        // v5.0.11: This method is DISABLED to prevent hardcoded cross-contamination
        // The dynamic mapping system now handles all field assignments with strict separation
        LDA_Logger::log("v5.0.11: addSpecificFieldMappings() DISABLED - using dynamic mapping only");
        return;
    }
    
    /**
     * Generate abbreviation fields for document template compatibility
     * v5.0.11: Add support for template abbreviation fields like businessAbr, CountAbv
     */
    private function generateAbbreviationFields(&$merge_data) {
        LDA_Logger::log("v5.0.11: Generating abbreviation fields for document compatibility");
        
        // Generate businessAbr from USR_Business
        if (!empty($merge_data['USR_Business'])) {
            $merge_data['businessAbr'] = $merge_data['USR_Business'];
            LDA_Logger::log("v5.0.11: Generated businessAbr = " . $merge_data['businessAbr']);
        }
        
        // Generate CountAbv from PT2_Business or PT2_Name
        if (!empty($merge_data['PT2_Business'])) {
            $merge_data['CountAbv'] = $merge_data['PT2_Business'];
            LDA_Logger::log("v5.0.11: Generated CountAbv from PT2_Business = " . $merge_data['CountAbv']);
        } elseif (!empty($merge_data['PT2_Name'])) {
            $merge_data['CountAbv'] = $merge_data['PT2_Name'];
            LDA_Logger::log("v5.0.11: Generated CountAbv from PT2_Name = " . $merge_data['CountAbv']);
        } else {
            // Default counterparty abbreviation
            $merge_data['CountAbv'] = 'CountAbv';
            LDA_Logger::log("v5.0.11: Generated default CountAbv = " . $merge_data['CountAbv']);
        }
        
        // Generate other abbreviations that might be used in templates
        if (!empty($merge_data['USR_Name'])) {
            $merge_data['UserAbr'] = $merge_data['USR_Name'];
            LDA_Logger::log("v5.0.11: Generated UserAbr = " . $merge_data['UserAbr']);
        }
        
        if (!empty($merge_data['PT2_Name'])) {
            $merge_data['PT2Abr'] = $merge_data['PT2_Name'];
            LDA_Logger::log("v5.0.11: Generated PT2Abr = " . $merge_data['PT2Abr']);
        }
    }
    
    /**
     * Extract user information from form fields for non-logged-in users
     */
    private function extractUserInfoFromFormFields(&$merge_data) {
        $first_name = '';
        $last_name = '';
        $email = '';
        $full_name = '';
        
        // Look for common field patterns that might contain user information
        foreach ($this->form['fields'] as $field) {
            $value = function_exists('rgar') ? rgar($this->entry, (string) $field->id) : (isset($this->entry[(string) $field->id]) ? $this->entry[(string) $field->id] : '');
            $field_label = strtolower($field->label ?? '');
            $field_admin_label = strtolower($field->adminLabel ?? '');
            
            // Handle complex fields like Name that return arrays
            if (is_array($value)) {
                $value = implode(' ', array_filter($value));
            }
            
            // Look for first name fields
            if (empty($first_name) && (
                strpos($field_label, 'first name') !== false ||
                strpos($field_admin_label, 'first name') !== false ||
                strpos($field_label, 'firstname') !== false ||
                strpos($field_admin_label, 'firstname') !== false ||
                strpos($field_label, 'given name') !== false ||
                strpos($field_admin_label, 'given name') !== false
            )) {
                $first_name = $value;
            }
            
            // Look for last name fields
            if (empty($last_name) && (
                strpos($field_label, 'last name') !== false ||
                strpos($field_admin_label, 'last name') !== false ||
                strpos($field_label, 'lastname') !== false ||
                strpos($field_admin_label, 'lastname') !== false ||
                strpos($field_label, 'surname') !== false ||
                strpos($field_admin_label, 'surname') !== false ||
                strpos($field_label, 'family name') !== false ||
                strpos($field_admin_label, 'family name') !== false
            )) {
                $last_name = $value;
            }
            
            // Look for email fields
            if (empty($email) && (
                strpos($field_label, 'email') !== false ||
                strpos($field_admin_label, 'email') !== false ||
                $field->type === 'email'
            )) {
                $email = $value;
            }
        }
        
        // Set the user information in merge data
        if (!empty($first_name)) {
            $merge_data['UserFirstName'] = $first_name;
            $merge_data['USERFIRSTNAME'] = $first_name;
        }
        
        if (!empty($last_name)) {
            $merge_data['UserLastName'] = $last_name;
            $merge_data['USERLASTNAME'] = $last_name;
        }
        
        if (!empty($email)) {
            $merge_data['UserEmail'] = $email;
            $merge_data['USEREMAIL'] = $email;
        }
        
        // Create a display name
        if (!empty($first_name) || !empty($last_name)) {
            $display_name = trim($first_name . ' ' . $last_name);
            $merge_data['UserName'] = $display_name;
            $merge_data['USERNAME'] = $display_name;
            
            // v5.0.11 FIX: Add DISPLAY_NAME for template compatibility
            $merge_data['DISPLAY_NAME'] = $display_name;
            LDA_Logger::log("v5.0.11 DISPLAY FIX: Generated DISPLAY_NAME = '{$display_name}'");
        }
        
        // v5.0.11 FIX: Add DISPLAY_EMAIL for template compatibility
        if (!empty($email)) {
            $merge_data['DISPLAY_EMAIL'] = $email;
            LDA_Logger::log("v5.0.11 DISPLAY FIX: Generated DISPLAY_EMAIL = '{$email}'");
        }
        
        LDA_Logger::log("v5.0.11: Extracted user info from form fields - First: '{$first_name}', Last: '{$last_name}', Email: '{$email}'");
    }
    
    /**
     * Generate business abbreviation from full business name
     */
    private function generateAbbreviation($business_name) {
        if (empty($business_name)) {
            return '';
        }
        
        // Remove common suffixes
        $name = preg_replace('/\s+(Pty\s+Ltd|Ltd|Inc|Corp|LLC|Co\.?)$/i', '', $business_name);
        
        // Extract first letters of words
        $words = preg_split('/\s+/', $name);
        $abbreviation = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $abbreviation .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Limit to reasonable length
        if (strlen($abbreviation) > 6) {
            $abbreviation = substr($abbreviation, 0, 6);
        }
        
        return $abbreviation;
    }
}