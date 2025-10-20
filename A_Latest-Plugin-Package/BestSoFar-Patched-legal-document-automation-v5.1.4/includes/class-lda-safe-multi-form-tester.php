<?php
/**
 * Safe Multi-Form Testing Enhancement v5.1.3 ENHANCED
 * 
 * Provides optional testing for multiple forms while preserving all existing functionality.
 * Uses identical processing paths as manual submissions - no new code paths that could break.
 */

class LDA_Safe_Multi_Form_Tester {
    
    /**
     * Get safe test scenarios for different document types
     * These are carefully curated scenarios that match common use patterns
     */
    public static function getTestScenarios() {
        return array(
            'confidentiality_one_way' => array(
                'name' => 'Confidentiality Agreement (One Way)',
                'description' => 'Tests one-way NDA with purpose checkboxes and conditional logic',
                'form_pattern' => 'Confidentiality.*One.*Way',
                'data' => array(
                    // Basic parties
                    'USR_Name' => 'Disclosing Party Pty Ltd',
                    'PT2_Name' => 'Receiving Party Services Ltd', 
                    'USR_Business' => 'Disclosing Party Pty Ltd',
                    'PT2_Business' => 'Receiving Party Services Ltd',
                    'USR_ABN' => '11111111111',
                    'PT2_ABN' => '22222222222',
                    'REF_State' => 'New South Wales',
                    'Effective_Date' => date('Y-m-d'),
                    
                    // Purpose checkboxes (key for conditional logic)
                    'Pmt_Services' => 'yes1',     // Will show service-related text
                    'Pmt_Negotiate' => 'yes2',    // Will show negotiation text  
                    'Pmt_Business' => '',         // Empty - will hide business-related text
                    'Pmt_Other' => '',            // Empty - will hide other text
                    
                    // Aliases for templates that use old field names
                    'Pmt_Agreements' => 'yes2',   // Alias for Pmt_Negotiate
                    'Pmt_Relations' => '',        // Alias for Pmt_Business
                    
                    // Concept
                    'Concept' => 'Innovative software solution for business process automation including proprietary algorithms and confidential business methodologies.',
                    
                    // User context
                    'UserFirstName' => 'Test',
                    'UserLastName' => 'Administrator', 
                    'user_email' => 'test@example.com',
                    'FormTitle' => 'Confidentiality Agreement (One Way) - TEST'
                )
            ),
            
            'contractor_agreement' => array(
                'name' => 'Contractor Agreement',
                'description' => 'Tests contractor/client relationship with fees and IP ownership',
                'form_pattern' => 'Contractor.*Agreement',
                'data' => array(
                    // Parties (USR = Client, PT2 = Contractor)
                    'USR_Name' => 'Client Business Pty Ltd',
                    'PT2_Name' => 'Contractor Services Pty Ltd',
                    'USR_Business' => 'Client Business Pty Ltd', 
                    'PT2_Business' => 'Contractor Services Pty Ltd',
                    'USR_ABN' => '33333333333',
                    'PT2_ABN' => '44444444444',
                    'REF_State' => 'Victoria',
                    
                    // Financial terms
                    'contractor_fees' => 'per hour',
                    'amount_of_fees' => '150',
                    'invoice_due_timeframe' => '14',
                    
                    // Term details
                    'commencement_date' => date('Y-m-d'),
                    'term_of_agreement' => '12',
                    'length_of_agreement' => 'month',
                    
                    // Notice periods
                    'business_notice_period' => '30',
                    'length_of_notice_period' => 'days',
                    'contractor_notice_period' => '14',
                    'include_contractor_notice' => 'yes',
                    
                    // IP ownership
                    'intellectual_property' => 'USR', // Business owns IP
                    
                    // Signatory details
                    'USR_Sign_First' => 'Client',
                    'USR_Sign_Last' => 'Director',
                    'USR_Sign_Role' => 'Managing Director',
                    'PT2_Sign_First' => 'Contractor',
                    'PT2_Sign_Last' => 'Principal',
                    'PT2_Sign_Role' => 'Principal Contractor',
                    
                    'UserFirstName' => 'Test',
                    'user_email' => 'test@example.com',
                    'FormTitle' => 'Contractor Agreement - TEST'
                )
            ),
            
            'employment_agreement' => array(
                'name' => 'Employment Agreement',
                'description' => 'Tests employer/employee relationship with standard employment terms',
                'form_pattern' => 'Employment.*Agreement',
                'data' => array(
                    // Parties (USR = Employer, PT2 = Employee)
                    'USR_Name' => 'Employer Company Pty Ltd',
                    'PT2_Name' => 'Employee Name',
                    'USR_Business' => 'Employer Company Pty Ltd',
                    'PT2_Business' => '', // Employees don't have business names
                    'USR_ABN' => '55555555555', 
                    'PT2_ABN' => '', // Employees don't have ABNs
                    'REF_State' => 'Queensland',
                    
                    // Employment details
                    'employment_type' => 'Full Time',
                    'position_title' => 'Senior Software Developer',
                    'annual_salary' => '95000',
                    'commencement_date' => date('Y-m-d'),
                    
                    // Leave entitlements
                    'annual_leave' => '4', // weeks
                    'sick_leave' => '10', // days
                    
                    // Notice periods  
                    'probation_period' => '3', // months
                    'notice_period' => '4', // weeks
                    
                    'UserFirstName' => 'Test',
                    'user_email' => 'test@example.com',
                    'FormTitle' => 'Employment Agreement - TEST'
                )
            )
        );
    }
    
    /**
     * Generate test data for a specific scenario
     * This uses the SAME data structure as real form submissions
     */
    public static function generateTestDataForScenario($scenario_key, $template_name = '') {
        $scenarios = self::getTestScenarios();
        
        if (!isset($scenarios[$scenario_key])) {
            return null;
        }
        
        $scenario = $scenarios[$scenario_key];
        
        // Check if template name matches scenario pattern
        if (!empty($template_name) && !empty($scenario['form_pattern'])) {
            if (!preg_match('/' . $scenario['form_pattern'] . '/i', $template_name)) {
                // Template doesn't match this scenario
                return null;
            }
        }
        
        return $scenario['data'];
    }
    
    /**
     * Get all available test scenarios for admin display
     */
    public static function getAvailableScenarios() {
        $scenarios = self::getTestScenarios();
        $available = array();
        
        foreach ($scenarios as $key => $scenario) {
            $available[$key] = array(
                'name' => $scenario['name'],
                'description' => $scenario['description']
            );
        }
        
        return $available;
    }
    
    /**
     * Suggest best scenario for a template name
     */
    public static function suggestScenarioForTemplate($template_name) {
        $scenarios = self::getTestScenarios();
        
        foreach ($scenarios as $key => $scenario) {
            if (!empty($scenario['form_pattern'])) {
                if (preg_match('/' . $scenario['form_pattern'] . '/i', $template_name)) {
                    return $key;
                }
            }
        }
        
        return null; // No specific match - use default
    }
    
    /**
     * Validate test data before processing
     * Ensures data follows same format as real submissions
     */
    public static function validateTestData($test_data) {
        $validation = array(
            'valid' => true,
            'warnings' => array(),
            'errors' => array()
        );
        
        // Check required USR fields
        $required_usr_fields = array('USR_Name', 'USR_Business');
        foreach ($required_usr_fields as $field) {
            if (empty($test_data[$field])) {
                $validation['warnings'][] = "Missing {$field} - may cause template issues";
            }
        }
        
        // Check ABN format if provided
        if (!empty($test_data['USR_ABN'])) {
            if (!preg_match('/^\d{11}$/', $test_data['USR_ABN'])) {
                $validation['warnings'][] = "USR_ABN should be 11 digits";
            }
        }
        
        if (!empty($test_data['PT2_ABN'])) {
            if (!preg_match('/^\d{11}$/', $test_data['PT2_ABN'])) {
                $validation['warnings'][] = "PT2_ABN should be 11 digits";
            }
        }
        
        // Check date format
        if (!empty($test_data['Effective_Date'])) {
            if (!strtotime($test_data['Effective_Date'])) {
                $validation['warnings'][] = "Effective_Date may not be a valid date";
            }
        }
        
        return $validation;
    }
}