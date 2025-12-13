<?php
require_once 'config.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($_FILES['template_file']) || $_FILES['template_file']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid .docx file.']);
    exit;
}

$file_tmp = $_FILES['template_file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['template_file']['name'], PATHINFO_EXTENSION));

if ($ext !== 'docx') {
    echo json_encode(['success' => false, 'message' => 'Only .docx files are supported.']);
    exit;
}

$zip = new ZipArchive;
if ($zip->open($file_tmp) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    
    // --- COPY CLEANING LOGIC FROM GENERATOR ---
    $xml = preg_replace('/<w:proofErr[^>]*>/', '', $xml);
    $xml = preg_replace('/<w:lang[^>]*>/', '', $xml);
    $xml = preg_replace('/<w:gramE[^>]*>/', '', $xml);
    // --- SUPER MERGE ---
    // Aggressively merge text nodes that are split by inline formatting or run breaks.
    // We remove the closing </w:t> ... opening <w:t>, allowing any tags in between 
    // EXCEPT block-level tags like paragraphs (w:p), breaks (w:br), or table cells (w:tc).
    $xml = preg_replace('/<\/w:t>(?:<(?!\/?w:p|\/?w:br|\/?w:tc)[^>]+>)*<w:t[^>]*>/', '', $xml);
    
    // Search
    $found_tags = [];
    $placeholders = ['<student_name>', '<employee_name>'];
    
    foreach ($placeholders as $tag) {
        // Regex Search: Case insensitive. Matches "student_name" with OR without brackets.
        // It consumes surrounding brackets/entities if they exist, so we don't get <John Doe>.
        $base = preg_quote(str_replace(['<','>'], '', $tag));
        $pattern = '/(?:<|&lt;|&amp;lt;)?\s*' . $base . '\s*(?:>|&gt;|&amp;gt;)?/i';
        
        if (preg_match($pattern, $xml)) {
             $found_tags[] = $tag;
        }
        
        // Also check raw <tag> just in case
        if (strpos($xml, $tag) !== false) {
             if (!in_array($tag, $found_tags)) $found_tags[] = $tag;
        }
    }
    
    $zip->close();
    
    if (count($found_tags) > 0) {
        $msg = "Success! Found tags: " . implode(", ", $found_tags);
        $preview = "Preview: The text <strong>" . implode(" / ", $found_tags) . "</strong> will be replaced with the student's name (e.g. <em>John Doe</em>).";
        echo json_encode(['success' => true, 'message' => $msg, 'preview' => $preview]);
    } else {
        // Debug: Show what we DID find
        $clean_text = strip_tags($xml);
        // Limit length
        $snippet = substr($clean_text, 0, 500);
        $msg = 'Warning: No tags found. Here is the text I see in your file:<br><code>' . htmlspecialchars($snippet) . '</code><br><br>Make sure you typed <strong>&lt;student_name&gt;</strong> exactly.';
        echo json_encode(['success' => false, 'message' => $msg, 'preview' => '']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Could not open the .docx file. It might be corrupted.']);
}
?>
