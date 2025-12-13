<?php
require_once 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$student_ids = $_POST['student_ids'] ?? [];

if (empty($student_ids)) {
    die("No students selected.");
}

if (!isset($_FILES['template_file']) || $_FILES['template_file']['error'] !== 0) {
    die("Please upload a valid .docx template.");
}

$template_tmp = $_FILES['template_file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['template_file']['name'], PATHINFO_EXTENSION));

if ($ext !== 'docx') {
    die("Only .docx files are supported.");
}

// working directory
$work_dir = 'uploads/temp_gen/';
if (!file_exists($work_dir)) mkdir($work_dir, 0777, true);

// 1. Prepare to merge. We need to extract the BODY content from the template once.
$zip = new ZipArchive;
$res = $zip->open($template_tmp);
if ($res !== TRUE) {
    die("Could not open template.");
}

$template_xml = $zip->getFromName('word/document.xml');
$zip->close(); // Close it, we will reopen a copy to write the final result later.

// Extract <w:body> ... </w:body> content
// We need everything INSIDE the body tags.
preg_match('/<w:body[^>]*>(.*?)<\/w:body>/s', $template_xml, $matches);
if (!isset($matches[1])) {
    die("Could not parse template XML body.");
}
$body_content = $matches[1];

// Isolate the final Section Properties (sectPr)
// This is typically at the very end of the body.
// We need to move this sectPr to a paragraph for all pages except the last one.
$sectPr_content = '';
if (preg_match('/(<w:sectPr[^>]*>.*?<\/w:sectPr>)/s', $body_content, $sect_matches)) {
    $sectPr_content = $sect_matches[1];
    // Remove it from the body template, we will re-add it manually
    $body_template_clean = str_replace($sectPr_content, '', $body_content);
} else {
    // If no sectPr found (unlikely in Word), just use full body
    $body_template_clean = $body_content;
}

// 2. Loop through students and build the specific page content
$final_body_xml = '';
$total_students = count($student_ids);
$counter = 0;

foreach ($student_ids as $id) {
    $counter++;
    
    // Fetch Student
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $student = $res->fetch_assoc();
        
        // Format Name
        $m_initial = !empty($student['middle_name']) ? substr($student['middle_name'], 0, 1) . '.' : '';
        $full_string = $student['first_name'] . ' ' . ($m_initial ? $m_initial . ' ' : '') . $student['last_name'];
        $full_name = ucwords(strtolower($full_string));
        
        // Start with clean body
        $page_xml = $body_template_clean;
        
        // --- XML CLEANUP (Super Merge) ---
        $page_xml = preg_replace('/<w:proofErr[^>]*>/', '', $page_xml);
        $page_xml = preg_replace('/<w:lang[^>]*>/', '', $page_xml);
        $page_xml = preg_replace('/<w:gramE[^>]*>/', '', $page_xml);
        $page_xml = preg_replace('/<\/w:t>(?:<(?!\/?w:p|\/?w:br|\/?w:tc)[^>]+>)*<w:t[^>]*>/', '', $page_xml); // Super merge
        
        // --- REPLACE PLACEHOLDERS ---
        $placeholders = ['<student_name>', '<employee_name>'];
        foreach ($placeholders as $tag) {
             $base_tag = str_replace(['<','>'], '', $tag);
             $pattern = '/(?:<|&lt;|&amp;lt;)?\s*' . preg_quote($base_tag) . '\s*(?:>|&gt;|&amp;gt;)?/i';
             $safe_name = htmlspecialchars($full_name);
             $page_xml = preg_replace($pattern, $safe_name, $page_xml);
        }
        
        // --- APPEND TO MASTER XML ---
        $final_body_xml .= $page_xml;
        
        // --- HANDLE PAGE BREAK / SECTION BREAK ---
        if ($counter < $total_students) {
            // Not the last student? We need a section break that acts as page break.
            // In Word, if you want a Section Break Next Page, you wrap it in a paragraph.
            // <w:p><w:pPr><w:sectPr ... /></w:pPr></w:p>
            if ($sectPr_content) {
                $final_body_xml .= '<w:p><w:pPr>' . $sectPr_content . '</w:pPr></w:p>';
            } else {
                // Fallback hard break if no sectPr found
                $final_body_xml .= '<w:br w:type="page"/>'; 
            }
        } else {
            // LAST STUDENT: Needs the final naked sectPr to close the document properties
            $final_body_xml .= $sectPr_content;
        }
    }
}

// 3. Create the Final Document
// We copy the template logic again
$final_filename = $work_dir . "Merged_Certificates_" . time() . ".docx";
copy($template_tmp, $final_filename);

$zip = new ZipArchive;
if ($zip->open($final_filename) === TRUE) {
    // Read original full document to keep header/footer structure
    $original_xml = $zip->getFromName('word/document.xml');
    
    // Replace the body content with our new massive body
    $new_full_xml = preg_replace('/<w:body[^>]*>(.*?)<\/w:body>/s', '<w:body>' . $final_body_xml . '</w:body>', $original_xml);
    
    $zip->addFromString('word/document.xml', $new_full_xml);
    $zip->close();
    
    // Download
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="All Certificates.docx"');
    header('Content-Length: ' . filesize($final_filename));
    readfile($final_filename);
    
    // Cleanup
    unlink($final_filename);
    exit;
} else {
    die("Failed to build final document.");
}
?>
