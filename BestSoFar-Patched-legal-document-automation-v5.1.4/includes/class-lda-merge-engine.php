<?php
/**
 * Simple M    public function mergeDocument($template_path, $merge_data, $output_path) {
        try {
            // DIRECT FILE DEBUG - bypasses all logging systems
            $debug_file = ABSPATH . 'wp-content/uploads/lda-debug.txt';
            $debug_msg = "\n" . date('Y-m-d H:i:s') . " - 🚨🚨🚨 VERSION 3.1.0 MERGE ENGINE IS LOADING! 🚨🚨🚨\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            LDA_Logger::log("🚨🚨🚨 EMERGENCY: VERSION 3.0.9 MERGE ENGINE IS LOADING! 🚨🚨🚨");
            LDA_Logger::log("*** CRITICAL DEBUG: LDA_MergeEngine::mergeDocument() is being called ***"); Engine for Legal Document Automation
 * No external dependencies - uses only WordPress built-in functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class LDA_MergeEngine {

    private $settings;

    public function __construct($settings = array()) {
        $this->settings = $settings;
    }

    /**
     * Merge data into DOCX template
     */
    public function mergeDocument($template_path, $merge_data, $output_path) {
        try {
            LDA_Logger::log("🚨🚨🚨 VERSION 3.1.6 MERGE ENGINE IS LOADING! 🚨🚨🚨");
            LDA_Logger::log("Starting document merge process");
            LDA_Logger::log("Template: {$template_path}");
            LDA_Logger::log("Output: {$output_path}");
            
            // Check if template exists
            if (!file_exists($template_path)) {
                throw new Exception("Template file not found: {$template_path}");
            }
            
            // Apply field mappings to enhance merge data
            LDA_Logger::log("Applying field mappings to enhance merge data");
            $enhanced_merge_data = $this->applyFieldMappingsDirectly($merge_data);
            LDA_Logger::log("Enhanced merge data with field mappings prepared");
            
            // Use the SimpleDOCX processor (same as working version)
            LDA_Logger::log("Using LDA_SimpleDOCX processor for document generation (WORKING VERSION METHOD)");
            $result = LDA_SimpleDOCX::processMergeTags($template_path, $enhanced_merge_data, $output_path);
            
            if ($result['success']) {
                LDA_Logger::log("Document merge completed successfully");
                return array('success' => true, 'file_path' => $output_path);
            } else {
                throw new Exception("DOCX processing failed: " . (isset($result['error']) ? $result['error'] : 'Unknown error'));
            }
            
        } catch (Exception $e) {
            LDA_Logger::error("Document merge failed: " . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Apply field mappings directly to merge data
     */
    private function applyFieldMappingsDirectly($merge_data) {
        LDA_Logger::log("Field mapping enhancement starting");
        
        // Check if field mappings were already applied by document processor
        if (isset($merge_data['_field_mappings_applied']) && $merge_data['_field_mappings_applied'] === true) {
            LDA_Logger::log("Field mappings already applied by document processor - skipping duplicate processing");
            unset($merge_data['_field_mappings_applied']); // Remove flag from merge data
            return $merge_data;
        }
        
        // Get the form ID from the merge data
        $form_id = isset($merge_data['form_id']) ? $merge_data['form_id'] : null;
        LDA_Logger::log("Form ID from merge data: " . ($form_id ?: 'NOT FOUND'));
        
        if (!$form_id) {
            LDA_Logger::log("No form ID found in merge data, skipping field mappings");
            return $merge_data;
        }
        
        // Get field mappings for this form
        $mappings = get_option('lda_field_mappings', array());
        $form_mappings = isset($mappings[$form_id]) ? $mappings[$form_id] : array();
        
        LDA_Logger::log("Found " . count($form_mappings) . " field mappings for form " . $form_id);
        
        if (empty($form_mappings)) {
            LDA_Logger::log("No field mappings configured for form " . $form_id);
            return $merge_data;
        }
        
        // Apply field mappings
        $mapping_applied = 0;
        foreach ($form_mappings as $merge_tag => $field_id) {
            // Check multiple possible keys: direct field_id, string field_id, and with {$} format
            $possible_keys = array(
                $field_id,
                (string)$field_id,
                '{$' . $merge_tag . '}',
                $merge_tag
            );
            
            $field_value = '';
            $found_key = '';
            foreach ($possible_keys as $key) {
                if (isset($merge_data[$key]) && !empty($merge_data[$key])) {
                    $field_value = $merge_data[$key];
                    $found_key = $key;
                    break;
                }
            }
            
            if (!empty($field_value)) {
                // Add data in BOTH formats - with and without {$} brackets
                $merge_data[$merge_tag] = $field_value;                    // Original format: USR_Business
                $merge_data['{$' . $merge_tag . '}'] = $field_value;       // Template format: {$USR_Business}
                LDA_Logger::log("FIELD MAPPING: {$merge_tag} = '{$field_value}' (from key: {$found_key})");
                $mapping_applied++;
            }
        }
        
        LDA_Logger::log("Field mappings applied successfully: {$mapping_applied} mappings processed");
        
        // Apply intelligent mapping fixes for common template inconsistencies
        $merge_data = $this->applyIntelligentMappings($merge_data);
        
        return $merge_data;
    }
    
    /**
     * Apply intelligent mappings to handle common template inconsistencies
     */
    private function applyIntelligentMappings($merge_data) {
        LDA_Logger::log("Applying intelligent mappings for template consistency");
        
        // Handle signatory name variations
        if (isset($merge_data['{$USR_Sign_First}']) && isset($merge_data['{$USR_Sign_Last}'])) {
            $merge_data['{$USR_Signatory_FN}'] = $merge_data['{$USR_Sign_First}'];
            $merge_data['{$USR_Signatory_LN}'] = $merge_data['{$USR_Sign_Last}'];
            LDA_Logger::log("Mapped USR signatory names: FN='{$merge_data['{$USR_Sign_First}']}', LN='{$merge_data['{$USR_Sign_Last}']}'");
        }
        
        if (isset($merge_data['{$PT2_Sign_First}']) && isset($merge_data['{$PT2_Sign_Last}'])) {
            $merge_data['{$PT2_Signatory_FN}'] = $merge_data['{$PT2_Sign_First}'];
            $merge_data['{$PT2_Signatory_LN}'] = $merge_data['{$PT2_Sign_Last}'];
            LDA_Logger::log("Mapped PT2 signatory names: FN='{$merge_data['{$PT2_Sign_First}']}', LN='{$merge_data['{$PT2_Sign_Last}']}'");
        }
        
        // Handle role variations
        if (isset($merge_data['{$USR_Sign_Role}'])) {
            $merge_data['{$USR_Signatory_Role}'] = $merge_data['{$USR_Sign_Role}'];
            LDA_Logger::log("Mapped USR signatory role: '{$merge_data['{$USR_Sign_Role}']}'");
        }
        
        if (isset($merge_data['{$PT2_Sign_Role}'])) {
            $merge_data['{$PT2_Signatory_Role}'] = $merge_data['{$PT2_Sign_Role}'];
            LDA_Logger::log("Mapped PT2 signatory role: '{$merge_data['{$PT2_Sign_Role}']}'");
        }
        
        // Handle effective date variations
        if (isset($merge_data['{$Effective_Date}'])) {
            $merge_data['{$Eff_date}'] = $merge_data['{$Effective_Date}'];
            LDA_Logger::log("Mapped effective date: '{$merge_data['{$Effective_Date}']}'");
        }
        
        // Handle missing payment/purpose fields with intelligent defaults
        $checkbox_mappings = array(
            '{$Pmt_Services}' => '{$Pmt_Services}',      // Checkbox ID 27.yes1
            '{$Pmt_Negotiate}' => '{$Pmt_Negotiate}',    // Checkbox ID 27.yes2  
            '{$Pmt_Business}' => '{$Pmt_Business}',      // Checkbox ID 27.yes3
            '{$Pmt_Other}' => '{$Pmt_Other}',            // Checkbox ID 27.yes4
            '{$Pmt_Agreements}' => '{$Pmt_Negotiate}',   // Template expects this but uses negotiate data
            '{$Pmt_Relations}' => '{$Pmt_Business}',     // Template expects this but uses business data
            '{$Pmt_Purpose}' => '{$Purpose}'             // Map purpose field
        );
        
        foreach ($checkbox_mappings as $template_field => $source_field) {
            if (!isset($merge_data[$template_field])) {
                if (isset($merge_data[$source_field])) {
                    $merge_data[$template_field] = $merge_data[$source_field];
                    LDA_Logger::log("Intelligent checkbox mapping: $template_field = '{$merge_data[$source_field]}' (from $source_field)");
                } else {
                    $merge_data[$template_field] = ''; // Default to empty
                    LDA_Logger::log("Set default empty value for missing checkbox field: $template_field");
                }
            }
        }
        
        LDA_Logger::log("Intelligent mappings applied successfully");
        return $merge_data;
    }
    
    /**
     * Merge document and generate PDF
     */
    public function mergeDocumentWithPdf($template_path, $merge_data, $docx_output_path, $pdf_output_path) {
        try {
            // First merge the DOCX document
            $docx_result = $this->mergeDocument($template_path, $merge_data, $docx_output_path);
            if (!$docx_result['success']) {
                return $docx_result;
            }

            // Generate PDF version (optional)
            if (isset($this->settings['enable_pdf_output']) && $this->settings['enable_pdf_output']) {
                $pdf_handler = new LDA_PDFHandler($this->settings);
                $pdf_result = $pdf_handler->convertDocxToPdf($docx_output_path, $pdf_output_path);

                if (!$pdf_result['success']) {
                    LDA_Logger::warn("PDF generation failed, but DOCX was created successfully: " . $pdf_result['error']);
                    return array(
                        'success' => true,
                        'docx_path' => $docx_output_path,
                        'pdf_path' => null,
                        'pdf_error' => $pdf_result['error'],
                        'message' => 'DOCX created successfully, but PDF generation failed'
                    );
                }

                LDA_Logger::log("Document and PDF generated successfully");
                return array(
                    'success' => true,
                    'docx_path' => $docx_output_path,
                    'pdf_path' => $pdf_output_path,
                    'message' => 'Both DOCX and PDF generated successfully'
                );
            }

            return array(
                'success' => true,
                'docx_path' => $docx_output_path,
                'pdf_path' => null,
                'message' => 'DOCX generated successfully (PDF disabled)'
            );

        } catch (Exception $e) {
            LDA_Logger::error("Document merge with PDF failed: " . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * Validate template file
     */
    public function validateTemplate($template_path) {
        try {
            if (!file_exists($template_path)) {
                return array('success' => false, 'message' => 'Template file not found');
            }

            if (!is_readable($template_path)) {
                return array('success' => false, 'message' => 'Template file is not readable');
            }

            // Check if it's a valid DOCX file
            $zip = new ZipArchive();
            if ($zip->open($template_path) !== TRUE) {
                return array('success' => false, 'message' => 'Invalid DOCX file format');
            }

            // Check for required files
            if ($zip->locateName('word/document.xml') === false) {
                $zip->close();
                return array('success' => false, 'message' => 'Invalid DOCX structure - missing document.xml');
            }

            // Enhanced validation: Check for merge tags and syntax
            $validation_details = $this->validateMergeTags($zip);
            
            $zip->close();
            
            if ($validation_details['has_errors']) {
                return array(
                    'success' => false, 
                    'message' => 'Template has merge tag errors',
                    'details' => $validation_details['details']
                );
            }
            
            return array(
                'success' => true, 
                'message' => 'Template is valid and ready for use',
                'details' => $validation_details['details']
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Template validation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate merge tags in the template
     */
    private function validateMergeTags($zip) {
        $details = array();
        $has_errors = false;
        $merge_tags_found = 0;
        $conditional_blocks = 0;
        $modifiers_found = 0;
        
        // Check main document
        $document_xml = $zip->getFromName('word/document.xml');
        if ($document_xml) {
            $result = $this->analyzeMergeTags($document_xml, 'Main Document');
            $details[] = $result['summary'];
            $merge_tags_found += $result['merge_tags'];
            $conditional_blocks += $result['conditionals'];
            $modifiers_found += $result['modifiers'];
            if ($result['has_errors']) $has_errors = true;
        }
        
        // Check headers
        for ($i = 1; $i <= 3; $i++) {
            $header_xml = $zip->getFromName("word/header{$i}.xml");
            if ($header_xml) {
                $result = $this->analyzeMergeTags($header_xml, "Header {$i}");
                $details[] = $result['summary'];
                $merge_tags_found += $result['merge_tags'];
                $conditional_blocks += $result['conditionals'];
                $modifiers_found += $result['modifiers'];
                if ($result['has_errors']) $has_errors = true;
            }
        }
        
        // Check footers
        for ($i = 1; $i <= 3; $i++) {
            $footer_xml = $zip->getFromName("word/footer{$i}.xml");
            if ($footer_xml) {
                $result = $this->analyzeMergeTags($footer_xml, "Footer {$i}");
                $details[] = $result['summary'];
                $merge_tags_found += $result['merge_tags'];
                $conditional_blocks += $result['conditionals'];
                $modifiers_found += $result['modifiers'];
                if ($result['has_errors']) $has_errors = true;
            }
        }
        
        // Summary
        $summary = "Validation Summary:\n";
        $summary .= "• Merge tags found: {$merge_tags_found}\n";
        $summary .= "• Conditional blocks: {$conditional_blocks}\n";
        $summary .= "• Modifiers used: {$modifiers_found}\n";
        $summary .= "• Sections analyzed: " . count($details) . "\n";
        
        array_unshift($details, $summary);
        
        return array(
            'has_errors' => $has_errors,
            'details' => implode("\n", $details)
        );
    }
    
    /**
     * Analyze merge tags in XML content
     */
    private function analyzeMergeTags($xml_content, $section_name) {
        $merge_tags = 0;
        $conditionals = 0;
        $modifiers = 0;
        $errors = array();
        
        // Count merge tags (both regular and HTML entity formats)
        preg_match_all('/\{\$[^}]+\}/', $xml_content, $matches);
        preg_match_all('/\{&#36;[^}]+\}/', $xml_content, $entity_matches);
        $merge_tags = count($matches[0]) + count($entity_matches[0]);
        
        // Count conditionals
        preg_match_all('/\{if[^}]+\}/', $xml_content, $if_matches);
        preg_match_all('/\{\/if\}/', $xml_content, $endif_matches);
        $conditionals = min(count($if_matches[0]), count($endif_matches[0]));
        
        // Count modifiers (both regular and HTML entity formats)
        preg_match_all('/\{\$[^|]+\|[^}]+\}/', $xml_content, $modifier_matches);
        preg_match_all('/\{&#36;[^|]+\|[^}]+\}/', $xml_content, $entity_modifier_matches);
        $modifiers = count($modifier_matches[0]) + count($entity_modifier_matches[0]);
        
        // Check for syntax errors
        if (count($if_matches[0]) !== count($endif_matches[0])) {
            $errors[] = "Mismatched conditional blocks (if/endif)";
        }
        
        // Check for unclosed merge tags
        preg_match_all('/\{\$[^}]*$/', $xml_content, $unclosed_matches);
        if (count($unclosed_matches[0]) > 0) {
            $errors[] = "Unclosed merge tags detected";
        }
        
        $summary = "{$section_name}: {$merge_tags} merge tags, {$conditionals} conditionals, {$modifiers} modifiers";
        if (!empty($errors)) {
            $summary .= " - ERRORS: " . implode(", ", $errors);
        }
        
        return array(
            'summary' => $summary,
            'merge_tags' => $merge_tags,
            'conditionals' => $conditionals,
            'modifiers' => $modifiers,
            'has_errors' => !empty($errors)
        );
    }
}