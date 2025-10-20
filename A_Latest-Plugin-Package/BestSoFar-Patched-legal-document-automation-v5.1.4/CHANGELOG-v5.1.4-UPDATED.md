LEGAL DOCUMENT AUTOMATION — CHANGELOG v5.1.4-UPDATED

Summary:
- Integrated conservative "safe brace-collapse" pre-clean into fixSplitMergeTagsConservative().
- Retained previous conservative reconstruction logic and added narrowly-targeted fragment fixes.
- Removed special-character/emoji workspace path references to ensure CLI compatibility.
- Added public test helper test_fixSplitMergeTagsConservative() for standalone testing.

Why this change:
- Previously aggressive fallback reconstructor caused contamination by joining unrelated runs.
- The safe pre-clean collapses XML fragments inside braces only when the cleaned content matches an allowed tag or conditional pattern. This reduces fragmented tokens while avoiding false joins.

Files changed:
- includes/class-lda-simple-docx.php (core conservative fixer + test helper)
- admin/class-lda-admin.php (emoji path references normalized)
- Various local test harness scripts under /tmp (standalone_fix.php)

Notes:
- This update requires end-to-end staging tests inside WordPress because LDA_Logger and WP APIs are only available in that environment.
- If staging shows remaining tag fragments, we will add narrowly-scoped patterns for those specific tokens only.

How to test (quick):
1. Upload ZIP to staging, activate plugin.
2. Use the Templates tab to upload the Confidentiality-Agreement-One-Way.docx (if not already assigned).
3. Run "Test" with sample data or submit a real Form 30 entry; download the generated DOCX.
4. Unzip the DOCX and inspect word/document.xml for leftover merge-tags (search for "{$").
5. Report any remaining tokens and include the merged DOCX and logs.

Contact:
- For follow-up, reply here and I'll iterate quickly with targeted, conservative fixes.
