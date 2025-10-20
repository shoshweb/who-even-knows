<?php
/**
 * Gravity Forms Structure Analyzer for Enhanced Test Data Generation
 * v5.1.3 ENHANCED - Dynamic test data based on actual form structures
 */

class LDA_Gravity_Forms_Analyzer {
    
    private static $forms_data = null;
    
    /**
     * Load and parse Gravity Forms export data
     */
    public static function loadFormsData() {
        if (self::$forms_data !== null) {
            return self::$forms_data;
        }
        
        // Check for Gravity Forms export file in common locations
        $possible_paths = array(
            ABSPATH . 'wp-content/uploads/gravity_forms_export.json',
            ABSPATH . 'wp-content/uploads/lda-export/gravity_forms_export.json',
            ABSPATH . 'wp-content/plugins/legal-document-automation-pro/gravity_forms_export.json'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $json_content = file_get_contents($path);
                self::$forms_data = json_decode($json_content, true);
                LDA_Logger::log("✅ Loaded Gravity Forms data from: {$path}");
                return self::$forms_data;
            }
        }
        
        LDA_Logger::log("⚠️ No Gravity Forms export found - using default test data");
        return null;
    }
    
    /**
     * Generate intelligent test data for a specific form ID
     */
    public static function generateTestDataForForm($form_id) {
        $forms_data = self::loadFormsData();
        
        if (!$forms_data || !isset($forms_data['forms'])) {
            return self::getDefaultTestData();
        }
        
        // Find the specific form
        $target_form = null;
        foreach ($forms_data['forms'] as $form) {
            if (isset($form['id']) && $form['id'] == $form_id) {
                $target_form = $form;
                break;
            }
        }
        
        if (!$target_form) {
            LDA_Logger::log("Form ID {$form_id} not found in export - using default test data");
            return self::getDefaultTestData();
        }
        
        LDA_Logger::log("🎯 Generating intelligent test data for form: {$target_form['title']} (ID: {$form_id})");
        
        return self::generateIntelligentTestData($target_form);
    }
    
    /**
     * Generate intelligent test data based on form structure
     */
    private static function generateIntelligentTestData($form) {
        $test_data = array();
        
        // Base test data
        $test_data = array_merge($test_data, self::getBaseTestData());
        
        // Analyze form fields and generate appropriate test data
        if (isset($form['fields'])) {
            foreach ($form['fields'] as $field) {
                $field_data = self::generateFieldTestData($field);
                $test_data = array_merge($test_data, $field_data);
            }
        }
        
        // Add conditional logic test scenarios
        $conditional_data = self::generateConditionalTestData($form);
        $test_data = array_merge($test_data, $conditional_data);
        
        LDA_Logger::log("📊 Generated " . count($test_data) . " test data fields for form analysis");
        
        return $test_data;
    }
    
    /**
     * Generate test data for individual fields
     */
    private static function generateFieldTestData($field) {
        $data = array();
        $field_id = isset($field['id']) ? $field['id'] : 0;
        $field_type = isset($field['type']) ? $field['type'] : 'text';
        $field_label = isset($field['label']) ? $field['label'] : '';
        
        // Generate data based on field type and label patterns
        switch ($field_type) {
            case 'name':
                if (stripos($field_label, 'business') !== false) {
                    $data[$field_id . '.3'] = 'Business';
                    $data[$field_id . '.6'] = 'Owner';
                } else if (stripos($field_label, 'contractor') !== false) {
                    $data[$field_id . '.3'] = 'Contractor';
                    $data[$field_id . '.6'] = 'Services';
                } else {
                    $data[$field_id . '.3'] = 'John';
                    $data[$field_id . '.6'] = 'Smith';
                }
                break;
                
            case 'email':
                $data[$field_id] = 'test@example.com';
                break;
                
            case 'text':
                if (stripos($field_label, 'abn') !== false) {
                    $data[$field_id] = '11111111111';
                } else if (stripos($field_label, 'business') !== false) {
                    $data[$field_id] = 'Test Business Pty Ltd';
                } else if (stripos($field_label, 'role') !== false) {
                    $data[$field_id] = 'Director';
                } else {
                    $data[$field_id] = 'Test ' . $field_label;
                }
                break;
                
            case 'number':
                if (stripos($field_label, 'fee') !== false || stripos($field_label, 'amount') !== false) {
                    $data[$field_id] = '1000';
                } else {
                    $data[$field_id] = '10';
                }
                break;
                
            case 'date':
                $data[$field_id] = date('Y-m-d');
                break;
                
            case 'radio':
            case 'select':
                // Select first available choice
                if (isset($field['choices']) && !empty($field['choices'])) {
                    $first_choice = $field['choices'][0];
                    $data[$field_id] = isset($first_choice['value']) ? $first_choice['value'] : $first_choice['text'];
                }
                break;
                
            case 'checkbox':
                // For checkboxes, activate first and third options if available
                if (isset($field['choices']) && !empty($field['choices'])) {
                    foreach ($field['choices'] as $index => $choice) {
                        if ($index == 0 || $index == 2) { // Activate 1st and 3rd options
                            $choice_id = $field_id . '.' . ($index + 1);
                            $data[$choice_id] = isset($choice['value']) ? $choice['value'] : $choice['text'];
                        }
                    }
                }
                break;
        }
        
        return $data;
    }
    
    /**
     * Generate conditional logic test scenarios
     */
    private static function generateConditionalTestData($form) {
        $data = array();
        
        // Look for purpose/disclosure checkboxes (commonly field 27)
        if (isset($form['fields'])) {
            foreach ($form['fields'] as $field) {
                $field_label = strtolower(isset($field['label']) ? $field['label'] : '');
                
                if (stripos($field_label, 'purpose') !== false && 
                    isset($field['type']) && $field['type'] === 'checkbox') {
                    
                    // Generate Pmt_* test data for this form
                    $data['Pmt_Services'] = 'yes1';
                    $data['Pmt_Negotiate'] = 'yes2';  
                    $data['Pmt_Business'] = 'yes3';
                    $data['Pmt_Other'] = '';  // Leave empty to test conditional logic
                    
                    // Also create field mappings
                    $field_id = $field['id'];
                    $data[$field_id . '.yes1'] = 'Providing services regarding the concept';
                    $data[$field_id . '.yes2'] = 'Negotiating further agreements';
                    
                    LDA_Logger::log("🎯 Generated Purpose checkbox test data for field {$field_id}");
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Get base test data that applies to all forms
     */
    private static function getBaseTestData() {
        return array(
            'USR_Name' => 'Test Business Pty Ltd',
            'PT2_Name' => 'Counterparty Services Ltd',
            'USR_Business' => 'Test Business Pty Ltd',
            'PT2_Business' => 'Counterparty Services Ltd',
            'USR_ABN' => '11111111111',
            'PT2_ABN' => '22222222222',
            'USR_ABV' => 'TB',
            'PT2_ABV' => 'CS',
            'REF_State' => 'New South Wales',
            'Effective_Date' => date('Y-m-d'),
            'FormTitle' => 'Test Agreement Document',
            'Concept' => 'This is intelligent test data generated from the actual form structure to validate template functionality and conditional logic processing.',
            'UserFirstName' => 'Test',
            'UserLastName' => 'User',
            'user_id' => '1',
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'display_name' => 'Test User'
        );
    }
    
    /**
     * Fallback to default test data
     */
    private static function getDefaultTestData() {
        return array(
            'USR_Name' => 'John Smith',
            'PT2_Name' => 'Jane Doe',
            'USR_Business' => 'Smith & Associates',
            'PT2_Business' => 'Doe Enterprises',
            'USR_ABN' => '12345678901',
            'PT2_ABN' => '98765432109',
            'USR_ABV' => 'S&A',
            'PT2_ABV' => 'DE',
            'REF_State' => 'New South Wales',
            'EffectiveDate' => date('d F Y'),
            'FormTitle' => 'Test Agreement',
            'Concept' => 'This is a test concept for template validation.',
            'UserFirstName' => 'John',
            'UserLastName' => 'Smith',
            'CounterpartyFirstName' => 'Jane',
            'CounterpartyLastName' => 'Doe',
            'Pmt_Services' => 'yes1',
            'Pmt_Agreements' => 'no',
            'Pmt_Other' => 'yes4',
            'Pmt_Relations' => 'yes3',
            'Pmt_Purpose' => 'This is the test purpose for the agreement.'
        );
    }
    
    /**
     * Suggest field mappings based on form analysis
     */
    public static function suggestFieldMappings($form_id) {
        $forms_data = self::loadFormsData();
        
        if (!$forms_data) {
            return array();
        }
        
        // Find form and analyze field patterns
        foreach ($forms_data['forms'] as $form) {
            if (isset($form['id']) && $form['id'] == $form_id) {
                return self::analyzeFieldMappings($form);
            }
        }
        
        return array();
    }
    
    /**
     * Analyze form fields and suggest USR/PT2 mappings
     */
    private static function analyzeFieldMappings($form) {
        $suggestions = array();
        
        if (!isset($form['fields'])) {
            return $suggestions;
        }
        
        foreach ($form['fields'] as $field) {
            $field_id = isset($field['id']) ? $field['id'] : 0;
            $field_label = strtolower(isset($field['label']) ? $field['label'] : '');
            $field_type = isset($field['type']) ? $field['type'] : 'text';
            
            // Business name mappings
            if (stripos($field_label, 'business') !== false && stripos($field_label, 'name') !== false) {
                if (stripos($field_label, 'contractor') !== false) {
                    $suggestions[$field_id] = 'PT2_Business';
                } else {
                    $suggestions[$field_id] = 'USR_Business';
                }
            }
            
            // ABN mappings
            if (stripos($field_label, 'abn') !== false) {
                if (stripos($field_label, 'contractor') !== false) {
                    $suggestions[$field_id] = 'PT2_ABN';
                } else {
                    $suggestions[$field_id] = 'USR_ABN';
                }
            }
            
            // Name field mappings
            if ($field_type === 'name') {
                if (stripos($field_label, 'contractor') !== false) {
                    $suggestions[$field_id . '.3'] = 'PT2_Sign_First';
                    $suggestions[$field_id . '.6'] = 'PT2_Sign_Last';
                } else if (stripos($field_label, 'business') !== false) {
                    $suggestions[$field_id . '.3'] = 'USR_Sign_First';
                    $suggestions[$field_id . '.6'] = 'USR_Sign_Last';
                }
            }
            
            // Purpose checkboxes
            if (stripos($field_label, 'purpose') !== false && $field_type === 'checkbox') {
                $suggestions[$field_id . '.yes1'] = 'Pmt_Services';
                $suggestions[$field_id . '.yes2'] = 'Pmt_Negotiate';
                $suggestions[$field_id . '.yes3'] = 'Pmt_Business';
                $suggestions[$field_id . '.yes4'] = 'Pmt_Other';
            }
        }
        
        return $suggestions;
    }
}