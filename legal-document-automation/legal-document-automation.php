<?php
/*
Plugin Name: Legal Document Automation
Description: A plugin to automate the creation of legal documents.
Version: 1.0
Author: Jules
*/

function legal_doc_automation_menu() {
    add_menu_page(
        'Legal Document Automation',
        'Doc Automation',
        'manage_options',
        'legal-doc-automation',
        'legal_doc_automation_page',
        'dashicons-media-document'
    );
}

add_action('admin_menu', 'legal_doc_automation_menu');

function legal_doc_automation_page() {
    if (isset($_POST['generate_document'])) {
        $template_file = $_FILES['docx_template'];
        $data_file = $_FILES['json_data'];

        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/legal-doc-automation-temp';
        wp_mkdir_p($temp_dir);

        $template_path = $temp_dir . '/' . $template_file['name'];
        $data_path = $temp_dir . '/' . $data_file['name'];
        $output_path = $temp_dir . '/output.docx';

        move_uploaded_file($template_file['tmp_name'], $template_path);
        move_uploaded_file($data_file['tmp_name'], $data_path);

        $command = "python3 " . plugin_dir_path(__FILE__) . "process_docx.py " . escapeshellarg($template_path) . " " . escapeshellarg($data_path) . " " . escapeshellarg($output_path);
        $output = shell_exec($command);

        if (file_exists($output_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="output.docx"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($output_path));
            readfile($output_path);
            exit;
        } else {
            echo '<div class="error"><p>Error generating document.</p><pre>' . $output . '</pre></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Legal Document Automation</h1>
        <form method="post" enctype="multipart/form-data">
            <p>
                <label for="docx_template">.docx Template</label>
                <input type="file" name="docx_template" id="docx_template" required>
            </p>
            <p>
                <label for="json_data">JSON Data</label>
                <input type="file" name="json_data" id="json_data" required>
            </p>
            <p>
                <input type="submit" name="generate_document" value="Generate Document" class="button button-primary">
            </p>
        </form>
    </div>
    <?php
}
