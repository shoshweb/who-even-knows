DEPLOYMENT CHECKLIST — v5.1.4-UPDATED

Pre-deploy:
- Backup current plugin and database.
- Ensure staging site is running PHP 7.4+ and has write permissions to wp-content/uploads/lda-templates and lda-logs.
- Ensure Google Drive integration settings (if used) are configured on staging.

Deployment:
1. Deactivate existing Legal Document Automation plugin on staging.
2. Upload and install the provided ZIP: legal-document-automation-v5.1.4-UPDATED.zip
3. Activate the plugin.
4. Verify plugin version and that admin pages load (Settings -> Doc Automation).
5. Go to Templates tab and ensure templates list displays and upload works.

Post-deploy test (Form 30):
- Submit a fresh Form 30 with unique test values (avoid copying previous test data).
- In Admin -> Doc Automation -> Templates, run Test for Confidentiality-Agreement-One-Way.docx using provided sample data.
- Download resulting DOCX and unzip locally.
- Inspect word/document.xml for leftover merge tags: grep -n "{\$" word/document.xml
- Check LDA logs: wp-content/uploads/lda-logs/lda-main.log for warnings/errors during processing.

Acceptance criteria:
- No leftover merge tags (strings beginning with "{$") in the final DOCX.
- No PHP errors in logs related to the merge engine.
- Document formatting preserved; conditionals behave as expected.

If failure:
- Collect the merged DOCX, extract word/document.xml and include it in the bug report.
- Provide the LDA logs for the run and the Gravity Forms entry ID used for testing.
- We'll iterate with narrowly-scoped conservative patterns.

Rollback:
- Re-activate backed-up plugin if severe failures encountered.
