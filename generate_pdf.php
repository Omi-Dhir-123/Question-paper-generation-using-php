<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists("../fpdf/fpdf.php")) {
    die(json_encode(["error" => "FPDF library not found"]));
}

require_once("../fpdf/fpdf.php");

$data     = json_decode(file_get_contents("php://input"), true);
$board    = $data["board"]    ?? "";
$class    = $data["class"]    ?? "";
$level    = $data["level"]    ?? "";
$language = $data["language"] ?? "";
$qtype    = $data["qtype"]    ?? "SAQ";
$mode     = $data["mode"]     ?? "pdf";

$apiKey = "your_api_key_here"; // Replace with your actual API key

/* 
   PROMPT BUILDER
 */

if ($qtype === "SAQ") {

    $fullMarks = 80;
    $time      = "2 Hours";
    $prompt =
        "You are a question paper generator. Return ONLY a single raw JSON object starting with {. No markdown, no backticks, no explanation, no wrapper keys.\n\n"
      . "Generate an SAQ question paper for $board Board, Class $class, Subject: $language Programming, Difficulty: $level.\n\n"
      . "Rules:\n"
      . "- Generate exactly 80 Short Answer Questions.\n"
      . "- Each question carries exactly 1 mark. Total = 80 marks.\n"
      . "- Questions must be short and factual.\n"
      . "- ONLY include the question text. NO answers, NO options, NO [Answer:] in the text.\n"
      . "- Plain text only. No **, no *, no #, no <tags>, no backticks, no markdown.\n\n"
      . "Return this exact JSON structure:\n"
      . "{ \"full_marks\": 80, \"time\": \"2 Hours\", \"qtype\": \"SAQ\",\n"
      . "  \"sections\": [ { \"name\": \"Short Answer Questions\", \"instruction\": \"Answer all questions. Each carries 1 mark.\",\n"
      . "    \"questions\": [ { \"number\": 1, \"text\": \"question text only, no answer\", \"marks\": 1 } ] } ] }";

} elseif ($qtype === "LAQ") {

    $fullMarks = 80;
    $time      = "3 Hours";
    $prompt =
        "You are a question paper generator. Return ONLY a single raw JSON object starting with {. No markdown, no backticks, no explanation, no wrapper keys.\n\n"
      . "Generate an LAQ question paper for $board Board, Class $class, Subject: $language Programming, Difficulty: $level.\n\n"
      . "Rules:\n"
      . "- Generate exactly 16 Long Answer Questions.\n"
      . "- Each question carries exactly 5 marks. Total = 80 marks.\n"
      . "- Questions must require detailed answers with explanation and examples.\n"
      . "- ONLY include the question text. NO answers, NO sample answers in the text.\n"
      . "- Plain text only. No **, no *, no #, no <tags>, no backticks, no markdown.\n\n"
      . "Return this exact JSON structure:\n"
      . "{ \"full_marks\": 80, \"time\": \"3 Hours\", \"qtype\": \"LAQ\",\n"
      . "  \"sections\": [ { \"name\": \"Long Answer Questions\", \"instruction\": \"Answer all questions. Each carries 5 marks.\",\n"
      . "    \"questions\": [ { \"number\": 1, \"text\": \"question text only, no answer\", \"marks\": 5 } ] } ] }";

} else {
    // Mixed: SAQ + MCQ pattern
    $fullMarks = 80;
    $time      = "3 Hours";
    $prompt =
        "You are a question paper generator. Return ONLY a single raw JSON object starting with {. No markdown, no backticks, no explanation, no wrapper keys.\n\n"
      . "Generate a Mixed question paper for $board Board, Class $class, Subject: $language Programming, Difficulty: $level.\n\n"
      . "The paper must have exactly 5 sections:\n"
      . "- Section A: 20 MCQ, 1 mark each = 20 marks. EVERY MCQ must have 4 real options. Format: Question text? A) actual_option_text B) actual_option_text C) actual_option_text D) actual_option_text. Never write A) B) C) D) without actual text after each letter.\n"
      . "- Section B: 5 SA-I questions, 2 marks each = 10 marks.\n"
      . "- Section C: 6 SA-II questions, 3 marks each = 18 marks.\n"
      . "- Section D: 4 LA questions, 5 marks each = 20 marks.\n"
      . "- Section E: 3 Case-Based questions, 4 marks each = 12 marks. Give a scenario then ask a question.\n"
      . "Total = 80 marks.\n"
      . "CRITICAL RULES:\n"
      . "- Do NOT include any answers, [Answer: X], or correct answer indicators anywhere.\n"
      . "- Plain text only. No **, no *, no #, no <html_tags>, no backticks, no markdown at all.\n\n"
      . "Return this exact JSON structure:\n"
      . "{ \"full_marks\": 80, \"time\": \"3 Hours\", \"qtype\": \"Mixed\",\n"
      . "  \"sections\": [\n"
      . "    { \"name\": \"Section A - Multiple Choice Questions\", \"instruction\": \"Choose the correct answer. Each carries 1 mark.\",\n"
      . "      \"questions\": [ { \"number\": 1, \"text\": \"What is HTML? A) HyperText Markup Language B) Hyperlink Text Machine Language C) High Text Markup Language D) Home Tool Markup Language\", \"marks\": 1 } ] },\n"
      . "    { \"name\": \"Section B - Short Answer I (SA-I)\", \"instruction\": \"Answer in 2-3 sentences. Each carries 2 marks.\",\n"
      . "      \"questions\": [ { \"number\": 21, \"text\": \"question only\", \"marks\": 2 } ] },\n"
      . "    { \"name\": \"Section C - Short Answer II (SA-II)\", \"instruction\": \"Answer in 4-5 sentences. Each carries 3 marks.\",\n"
      . "      \"questions\": [ { \"number\": 26, \"text\": \"question only\", \"marks\": 3 } ] },\n"
      . "    { \"name\": \"Section D - Long Answer\", \"instruction\": \"Answer in detail. Each carries 5 marks.\",\n"
      . "      \"questions\": [ { \"number\": 32, \"text\": \"question only\", \"marks\": 5 } ] },\n"
      . "    { \"name\": \"Section E - Case-Based Questions\", \"instruction\": \"Read the scenario and answer. Each carries 4 marks.\",\n"
      . "      \"questions\": [ { \"number\": 36, \"text\": \"scenario then question\", \"marks\": 4 } ] }\n"
      . "  ] }";
}

/*  Call Gemini  */
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$postData = [
    "contents" => [[
        "parts" => [["text" => $prompt]]
    ]],
    "generationConfig" => [
        "temperature"      => 0.5,
        "responseMimeType" => "application/json"
    ]
];

$options = [
    "http" => [
        "header"        => "Content-Type: application/json\r\n",
        "method"        => "POST",
        "content"       => json_encode($postData),
        "ignore_errors" => true
    ],
    "ssl" => [
        "verify_peer"      => false,
        "verify_peer_name" => false
    ]
];

$response = file_get_contents($url, false, stream_context_create($options));

if ($response === false) {
    $err = error_get_last();
    respondError("HTTP Error: " . $err['message'], $mode); exit;
}

$result = json_decode($response, true);

if (isset($result['error'])) {
    respondError("Gemini Error: " . $result['error']['message'], $mode); exit;
}

$rawText = $result["candidates"][0]["content"]["parts"][0]["text"] ?? "";
$rawText = preg_replace('/^```json\s*/i', '', trim($rawText));
$rawText = preg_replace('/```\s*$/',      '', trim($rawText));
$rawText = trim($rawText);

$paper = json_decode($rawText, true);

if (!$paper) {
    respondError("JSON decode failed. Raw response: " . substr($rawText, 0, 500), $mode); exit;
}

// Handle case where Gemini wraps sections inside a nested key
if (!isset($paper['sections'])) {
    // Try common alternative structures
    if (isset($paper['question_paper']['sections'])) {
        $paper = $paper['question_paper'];
    } elseif (isset($paper['paper']['sections'])) {
        $paper = $paper['paper'];
    } else {
        respondError("Missing 'sections' key. Keys found: " . implode(', ', array_keys($paper)) . ". Raw: " . substr($rawText, 0, 400), $mode); exit;
    }
}

// Clean markdown from all question texts
foreach ($paper['sections'] as &$sec) {
    foreach ($sec['questions'] as &$q) {
        $q['text'] = cleanMarkdown($q['text'] ?? '');
    }
}
unset($sec, $q);

/*  JSON mode  */
if ($mode === "json") {
    header("Content-Type: application/json");
    echo json_encode([
        "success"    => true,
        "board"      => $board,
        "class"      => $class,
        "level"      => $level,
        "language"   => $language,
        "qtype"      => $paper["qtype"]      ?? $qtype,
        "full_marks" => $paper["full_marks"] ?? $fullMarks,
        "time"       => $paper["time"]       ?? $time,
        "date"       => date("d-m-Y"),
        "sections"   => $paper["sections"] ?? [],
        "_debug_keys"=> array_keys($paper),
        "_debug_raw" => substr($rawText, 0, 300)
    ]);
    exit;
}

/*  PDF mode  */
generatePDF($board, $class, $level, $language, $qtype, $fullMarks, $time, $paper);

/* 
   HELPERS
 */

function cleanMarkdown($text) {
    // Remove bold/italic markdown
    $text = preg_replace('/\*{1,3}(.*?)\*{1,3}/s', '$1', $text);
    $text = preg_replace('/_([^_]+)_/',             '$1', $text);
    // Remove headings
    $text = preg_replace('/^#{1,6}\s+/m',            '',   $text);
    // Remove horizontal rules
    $text = preg_replace('/^-{3,}$/m',               '',   $text);
    // Remove code blocks
    $text = preg_replace('/```[\s\S]*?```/',          '',   $text);
    $text = preg_replace('/`([^`]+)`/',              '$1', $text);
    // Remove bullet points
    $text = preg_replace('/^[\\*\\-]\\s+/m', '', $text);
    // Remove [Answer: X] patterns
    $text = preg_replace('/\[Answer:?\s*[A-Za-z0-9]*\]/i', '', $text);
    $text = preg_replace('/\(Answer:?\s*[A-Za-z0-9]*\)/i', '', $text);
    $text = preg_replace('/Answer:?\s*[A-D]\b/i',           '', $text);
    // Remove HTML tags
    $text = strip_tags($text);
    // Clean extra whitespace
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = preg_replace('/  +/', ' ', $text);
    return trim($text);
}

function respondError($msg, $mode) {
    if ($mode === "json") {
        header("Content-Type: application/json");
        echo json_encode(["error" => $msg]);
    } else {
        die($msg);
    }
}

/* 
   PDF GENERATOR
 */

function generatePDF($board, $class, $level, $language, $qtype, $fullMarks, $time, $paper) {

    $sections = $paper["sections"] ?? [];
    $today    = date("d-m-Y");

    class ExamPDF extends FPDF {

        function Header() {
            // Page border — drawn on every page
            $this->SetDrawColor(40, 40, 40);
            $this->SetLineWidth(0.8);
            $this->Rect(8, 8, $this->GetPageWidth() - 16, $this->GetPageHeight() - 16);
            // Inner thinner border
            $this->SetDrawColor(120, 120, 120);
            $this->SetLineWidth(0.3);
            $this->Rect(11, 11, $this->GetPageWidth() - 22, $this->GetPageHeight() - 22);
        }

        function Footer() {
            // Watermark text — centered at bottom
            $this->SetY(-22);
            $this->SetFont("Arial", "B", 9);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 6, "PBA INSTITUTE-9239412412", 0, 1, "C");

            // Page number below watermark
            $this->SetFont("Arial", "I", 8);
            $this->SetTextColor(160, 160, 160);
            $this->Cell(0, 5, "Page " . $this->PageNo(), 0, 0, "C");
        }
    }

    $pdf = new ExamPDF();
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 28);
    $pdf->AddPage();

    $pageW = 170;
    $lx    = 20;
    $rowH  = 8;

    /* 
       HEADER : each field on its own row to avoid overlap
     */

    // Row 1: BOARD left | CLASS center | DATE right
    $pdf->SetFont("Arial", "B", 11);
    $pdf->SetX($lx);
    $pdf->Cell(57, $rowH, "BOARD  :  " . $board,    0, 0, "L");
    $pdf->Cell(56, $rowH, "CLASS  :  " . $class,    0, 0, "C");
    $pdf->Cell(57, $rowH, "DATE  :  " . $today,     0, 1, "R");

    // Row 2: SUBJECT left | DIFFICULTY center | FULL MARKS right
    $pdf->SetX($lx);
    $pdf->Cell(57, $rowH, "SUBJECT  :  " . $language,    0, 0, "L");
    $pdf->Cell(56, $rowH, "DIFFICULTY  :  " . $level,    0, 0, "C");
    $pdf->Cell(57, $rowH, "FULL MARKS  :  " . $fullMarks, 0, 1, "R");

    // Row 3: TIME left | TYPE right
    $pdf->SetX($lx);
    $pdf->Cell(85, $rowH, "TIME  :  " . $time,   0, 0, "L");
    $pdf->Cell(85, $rowH, "TYPE  :  " . $qtype,  0, 1, "R");

    /*  Divider  */
    $pdf->Ln(2);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.7);
    $pdf->Line($lx, $pdf->GetY(), $lx + $pageW, $pdf->GetY());
    $pdf->Ln(7);

    /* 
       SECTIONS & QUESTIONS
     */

    // Reserve right column for marks (fixed 26mm)
    $marksColW = 26;
    $textColW  = $pageW - $marksColW; // 144mm for text

    foreach ($sections as $section) {
        $secName   = iconv("UTF-8", "ISO-8859-1//TRANSLIT//IGNORE", $section["name"]        ?? "");
        $secInstr  = iconv("UTF-8", "ISO-8859-1//TRANSLIT//IGNORE", $section["instruction"] ?? "");
        $questions = $section["questions"] ?? [];

        // Section heading
        $pdf->SetFont("Arial", "B", 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetX($lx);
        $pdf->Cell($textColW, 8, $secName, 0, 1, "L");

        // Instruction line
        if ($secInstr) {
            $pdf->SetFont("Arial", "I", 10);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->SetX($lx);
            $pdf->MultiCell($textColW, 6, $secInstr, 0, "L");
            $pdf->SetTextColor(0, 0, 0);
        }
        $pdf->Ln(3);

        // Questions
        foreach ($questions as $q) {
            $num       = $q["number"] ?? "";
            $marks     = $q["marks"]  ?? "";
            $cleanText = iconv("UTF-8", "ISO-8859-1//TRANSLIT//IGNORE", $q["text"] ?? "");

            // Number prefix width
            $pdf->SetFont("Arial", "B", 11);
            $numStr = $num . ".";
            $numW   = $pdf->GetStringWidth($numStr) + 3;

            // Measure how tall this question will be using GetStringWidth-based calculation
            // Write number
            $pdf->SetX($lx);
            $pdf->Cell($numW, 7, $numStr, 0, 0, "L");

            // Write question text : X is already after number
            $pdf->SetFont("Arial", "", 11);
            $qX = $lx + $numW;
            $pdf->SetX($qX);
            $pdf->MultiCell($textColW - $numW, 7, $cleanText, 0, "L");

            $endY = $pdf->GetY();

            // Marks : italic, placed at TOP-RIGHT, same line as question start
            $pdf->SetFont("Arial", "I", 10);
            $pdf->SetTextColor(80, 80, 80);
            // Place marks at end of text column, vertically aligned with end of question
            $pdf->SetXY($lx + $textColW, $endY - 7);
            $pdf->Cell($marksColW, 7, "(" . $marks . " marks)", 0, 0, "R");
            $pdf->SetTextColor(0, 0, 0);

            // Next question starts right after this one + small gap
            $pdf->SetXY($lx, $endY + 3);
        }

        // Section separator
        $pdf->Ln(2);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($lx, $pdf->GetY(), $lx + $pageW, $pdf->GetY());
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Ln(5);
    }

    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"question_paper.pdf\"");
    $pdf->Output("I", "question_paper.pdf");
}

function headerCell($pdf, $label, $value, $colW, $rowH, $align) {
    $em      = "\x97";
    $spacing = "  ";
    $pdf->SetFont("Arial", "B", 11);
    $labelStr = $label . $spacing . $em . $spacing;
    $labelW   = $pdf->GetStringWidth($labelStr);
    $pdf->SetFont("Arial", "", 11);
    $valueW   = $pdf->GetStringWidth($value);
    $totalW   = $labelW + $valueW;
    $curX     = $pdf->GetX();
    if ($align === "C")      $pdf->SetX($curX + ($colW - $totalW) / 2);
    elseif ($align === "R")  $pdf->SetX($curX + $colW - $totalW);
    $pdf->SetFont("Arial", "B", 11);
    $pdf->Cell($labelW, $rowH, $labelStr, 0, 0, "L");
    $pdf->SetFont("Arial", "", 11);
    $pdf->Cell($valueW, $rowH, $value, 0, 0, "L");
    $pdf->SetX($curX + $colW);
}

function printRightHeaderCell($pdf, $fullStr, $colW, $rowH) {
    $pdf->SetFont("Arial", "B", 11);
    $totalW = $pdf->GetStringWidth($fullStr);
    $curX   = $pdf->GetX();
    $pdf->SetX($curX + $colW - $totalW);
    $pdf->Cell($totalW, $rowH, $fullStr, 0, 0, "L");
    $pdf->SetX($curX + $colW);
}