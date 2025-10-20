=== A1 Legal Document Automation Pro ===
Contributors: modelaw
Tags: legal, documents, automation, gravity-forms, google-drive, merge-tags, docx, xml, conditional-logic
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 5.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced legal document automation with nuclear XML reconstruction for split merge tags and conditional logic processing.

== Description ==

Legal Document Automation Pro is a powerful WordPress plugin that automates the generation of legal documents by processing Microsoft Word templates with advanced merge tags and conditional logic. This version includes nuclear patterns for conditional logic reconstruction.

**Key Features:**

* **Nuclear XML Reconstruction**: Handles inconsistent merge tag processing due to XML splitting
* **Conditional Logic Processing**: Support for {if !empty($FIELD)} and {/if} conditional statements
* **Effective Date Processing**: Enhanced support for {$Effective_Date} merge tags
* **Complex Merge Tags**: Date formatting, phone formatting, text modifiers (upper, lower, etc.)
* **Split Tag Recovery**: Advanced reconstruction for tags split across XML elements
* **Gravity Forms Integration**: Seamlessly pull data from form submissions
* **Google Drive Integration**: Automatic document upload and organization

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/legal-document-automation-pro` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure field mappings in the admin interface
4. Set up template assignments for your forms

== Changelog ==

= 5.1.0 =
* MAJOR RELEASE: Complete nuclear XML reconstruction system
* Enhanced conditional logic processing for {if !empty($FIELD)} and {/if} tags
* Advanced Effective Date handling including {$Eff_date|date_format:"d F Y"} patterns
* Improved Purpose replace patterns for {$Pmt_Purpose|replace:.:} reconstruction  
* Comprehensive version control with dynamic LDA_VERSION constant
* Ultra-aggressive patterns for Login_ID, underscore fields, and complex modifiers
* Complete WordPress plugin compliance and security standards
* Major stability improvements and enhanced logging

= 5.1.011-CONDITIONAL-LOGIC-FIX =
* CRITICAL FIX: Added nuclear patterns for conditional logic reconstruction
* Enhanced {if !empty($FIELD)} and {/if} tag processing
* Fixed {$Effective_Date} field recognition and reconstruction  
* Improved handling of complex conditional logic split across XML elements
* Added comprehensive logging for conditional logic debugging
* Fixed ABN conditional logic that was split across XML elements

= 5.1.011-ULTRA-AGGRESSIVE-XML-FIX =
* Fixed inconsistent merge tag processing due to Microsoft Word XML splitting
* Added ultra-aggressive reconstruction for modifier tags like {$USR_Name|upper}
* Enhanced support for complex signatory fields and formatting tags
* Multi-pass processing to catch all split patterns

== Changelog ==
You can find the Changelog in the [Documentation](https://www.wpcloudplugins.com/wp-content/plugins/use-your-drive/_documentation/index.html#releasenotes).

== Upgrade Notice ==

If you have multiple WP Cloud Plugins on your site, please make sure they are all updated to avoid compatibility issues.

= 3.3.2 = 
This version fixes a security-related bug.