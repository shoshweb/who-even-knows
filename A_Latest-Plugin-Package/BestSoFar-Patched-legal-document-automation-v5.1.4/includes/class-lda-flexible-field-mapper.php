<?php
/**
 * Flexible Field Mapping Fix for Tag Mixing Issue
 * Version: 5.1.0-FIELD-MAPPING-FIX
 * 
 * This replaces hardcoded field IDs with intelligent field detection
 * based on field labels and purposes, making it work with any form structure.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LDA_Flexible_Field_Mapper {
    
    /**
     * Intelligently map form fields to merge tags based on field purpose
     */
    public static function getFlexibleFieldMappings($form, $entry) {
        $mappings = array();
        
        if (!isset($form['fields']) || !is_array($form['fields'])) {
            return $mappings;
        }
        
        foreach ($form['fields'] as $field) {
            $field_id = (string) $field['id'];
            $field_label = strtolower($field['label']);
            $field_type = isset($field['type']) ? $field['type'] : '';
            
            // Map business/user name fields (USR_Name)
            if (self::isBusinessNameField($field_label, $field_type)) {
                $mappings['USR_Name'] = $field_id;
                continue;
            }
            
            // Map counterparty/contractor name fields (PT2_Name)
            if (self::isCounterpartyNameField($field_label, $field_type)) {
                $mappings['PT2_Name'] = $field_id;
                continue;
            }
            
            // Map business ABN fields (USR_ABN)
            if (self::isBusinessABNField($field_label, $field_type)) {
                $mappings['USR_ABN'] = $field_id;
                continue;
            }
            
            // Map counterparty ABN fields (PT2_ABN)
            if (self::isCounterpartyABNField($field_label, $field_type)) {
                $mappings['PT2_ABN'] = $field_id;
                continue;
            }
            
            // Handle name fields with sub-inputs (first/last name)
            if ($field_type === 'name' && isset($field['inputs'])) {
                self::mapNameSubFields($field, $mappings);
            }
        }
        
        return $mappings;
    }
    
    /**
     * Check if field is a business/user name field
     */
    private static function isBusinessNameField($label, $type) {
        $business_patterns = array(
            'business.*name',
            'company.*name', 
            'user.*name',
            'business.*signatory.*name',
            'your.*business',
            'legal.*name'
        );
        
        return self::matchesPatterns($label, $business_patterns);
    }
    
    /**
     * Check if field is a counterparty/contractor name field
     */
    private static function isCounterpartyNameField($label, $type) {
        $counterparty_patterns = array(
            'contractor.*name',
            'counterparty.*name',
            'contractor.*signatory.*name',
            'second.*party',
            'other.*party',
            'receiving.*party'
        );
        
        return self::matchesPatterns($label, $counterparty_patterns);
    }
    
    /**
     * Check if field is a business ABN field
     */
    private static function isBusinessABNField($label, $type) {
        $abn_patterns = array(
            'business.*abn',
            'company.*abn',
            'your.*abn',
            'user.*abn'
        );
        
        return self::matchesPatterns($label, $abn_patterns) && !self::isCounterpartyABNField($label, $type);
    }
    
    /**
     * Check if field is a counterparty ABN field
     */
    private static function isCounterpartyABNField($label, $type) {
        $counterparty_abn_patterns = array(
            'contractor.*abn',
            'counterparty.*abn',
            'second.*party.*abn',
            'other.*party.*abn'
        );
        
        return self::matchesPatterns($label, $counterparty_abn_patterns);
    }
    
    /**
     * Handle name fields with sub-inputs (first/last name combinations)
     */
    private static function mapNameSubFields($field, &$mappings) {
        $field_label = strtolower($field['label']);
        $base_id = $field['id'];
        
        // Check if this is a business signatory name field
        if (self::isBusinessNameField($field_label, 'name')) {
            // Map full name using sub-inputs
            $first_name_id = $base_id . '.3';  // Standard Gravity Forms first name input
            $last_name_id = $base_id . '.6';   // Standard Gravity Forms last name input
            
            $mappings['USR_Name_First'] = $first_name_id;
            $mappings['USR_Name_Last'] = $last_name_id;
            $mappings['USR_Name'] = $base_id; // Full name field
        }
        
        // Check if this is a counterparty signatory name field
        if (self::isCounterpartyNameField($field_label, 'name')) {
            $first_name_id = $base_id . '.3';
            $last_name_id = $base_id . '.6';
            
            $mappings['PT2_Name_First'] = $first_name_id;
            $mappings['PT2_Name_Last'] = $last_name_id;
            $mappings['PT2_Name'] = $base_id; // Full name field
        }
    }
    
    /**
     * Check if label matches any of the given patterns
     */
    private static function matchesPatterns($label, $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $label)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get field value handling name sub-inputs
     */
    public static function getFlexibleFieldValue($entry, $field_id, $field_type = '') {
        if (!is_array($entry)) {
            return '';
        }
        
        // For name fields, combine first and last name
        if ($field_type === 'name') {
            $first_name = isset($entry[$field_id . '.3']) ? $entry[$field_id . '.3'] : '';
            $last_name = isset($entry[$field_id . '.6']) ? $entry[$field_id . '.6'] : '';
            
            if (!empty($first_name) || !empty($last_name)) {
                return trim($first_name . ' ' . $last_name);
            }
        }
        
        // Standard field value
        return isset($entry[$field_id]) ? $entry[$field_id] : '';
    }
    
    /**
     * Apply flexible field mappings to merge data
     */
    public static function applyFlexibleMappings($form, $entry, $merge_data = array()) {
        $flexible_mappings = self::getFlexibleFieldMappings($form, $entry);
        
        foreach ($flexible_mappings as $merge_tag => $field_id) {
            $field_value = self::getFlexibleFieldValue($entry, $field_id, 'name');
            
            if (!empty($field_value)) {
                $merge_data[$merge_tag] = $field_value;
                
                // Log the successful mapping
                if (function_exists('LDA_Logger')) {
                    LDA_Logger::log("FLEXIBLE MAPPING: {$merge_tag} = '{$field_value}' (from field {$field_id})");
                }
            }
        }
        
        // Add template tag aliases to fix tag mixing issues
        $merge_data = self::addTemplateTagAliases($merge_data);
        
        return $merge_data;
    }
    
    /**
     * Add template tag aliases to fix tag mixing issues
     * Maps template tags like USR_Signatory_FN to actual field data like USR_Sign_First
     */
    private static function addTemplateTagAliases($merge_data) {
        // Map USR_Signatory_* template tags to USR_Sign_* field data
        if (isset($merge_data['USR_Sign_First'])) {
            $merge_data['USR_Signatory_FN'] = $merge_data['USR_Sign_First'];
            $merge_data['{$USR_Signatory_FN}'] = $merge_data['USR_Sign_First'];
        }
        
        if (isset($merge_data['USR_Sign_Last'])) {
            $merge_data['USR_Signatory_LN'] = $merge_data['USR_Sign_Last'];
            $merge_data['{$USR_Signatory_LN}'] = $merge_data['USR_Sign_Last'];
        }
        
        if (isset($merge_data['USR_Sign_Role'])) {
            $merge_data['USR_Signatory_Role'] = $merge_data['USR_Sign_Role'];
            $merge_data['{$USR_Signatory_Role}'] = $merge_data['USR_Sign_Role'];
        }
        
        // Map PT2_Signatory_* template tags to PT2_Sign_* field data
        if (isset($merge_data['PT2_Sign_First'])) {
            $merge_data['PT2_Signatory_FN'] = $merge_data['PT2_Sign_First'];
            $merge_data['{$PT2_Signatory_FN}'] = $merge_data['PT2_Sign_First'];
        }
        
        if (isset($merge_data['PT2_Sign_Last'])) {
            $merge_data['PT2_Signatory_LN'] = $merge_data['PT2_Sign_Last'];
            $merge_data['{$PT2_Signatory_LN}'] = $merge_data['PT2_Sign_Last'];
        }
        
        if (isset($merge_data['PT2_Sign_Role'])) {
            $merge_data['PT2_Signatory_Role'] = $merge_data['PT2_Sign_Role'];
            $merge_data['{$PT2_Signatory_Role}'] = $merge_data['PT2_Sign_Role'];
        }
        
        // Log the alias mappings
        if (function_exists('LDA_Logger')) {
            LDA_Logger::log("TEMPLATE ALIASES: Added USR_Signatory_* and PT2_Signatory_* mappings to fix tag mixing");
        }
        
        return $merge_data;
    }
}