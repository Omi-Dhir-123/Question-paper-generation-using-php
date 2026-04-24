<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Programming Language Question Paper Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-image: url(''); background-size: cover; background-position: center; background-repeat: no-repeat;">

<header class="header">
    <h1>Programming Language Question Paper Generator</h1>
    <p>GET READY FOR YOUR EXAM</p>
</header>

<main class="main-layout">

    <!-- LEFT: Form Panel -->
    <section class="form-section">
        <form id="paperForm" class="card">

            <div class="field">
                <label>Board</label>
                <select name="board" required>
                    <option value="">Select Board</option>
                    <option value="CBSE">CBSE</option>
                    <option value="ICSE">ICSE</option>
                    <option value="ISC">ISC</option>
                </select>
            </div>

            <div class="field">
                <label>Class</label>
                <select name="class" required>
                    <option value="">Select Class</option>
                    <?php for ($i = 6; $i <= 12; $i++) {
                        echo "<option value='$i'>Class $i</option>";
                    } ?>
                </select>
            </div>

            <div class="field">
                <label>Difficulty Level</label>
                <select name="level" required>
                    <option value="">Select Level</option>
                    <option value="Easy">Easy</option>
                    <option value="Medium">Medium</option>
                    <option value="Hard">Hard</option>
                    <option value="True Genius">True Genius</option>
                </select>
            </div>

            <div class="field">
                <label>Programming Language</label>
                <select name="language" required>
                    <option value="">Select Language</option>
                    <option value="Java">Java</option>
                    <option value="Python">Python</option>
                    <option value="C">C</option>
                    <option value="C++">C++</option>
                    <option value="HTML">HTML</option>
                    <option value="JavaScript">JavaScript</option>
                </select>
            </div>

            <div class="field">
                <label>Question Type</label>
                <select name="qtype" required>
                    <option value="">Select Type</option>
                    <option value="SAQ">SAQ — Short Answer (80 Qs x 1 mark)</option>
                    <option value="LAQ">LAQ — Long Answer (16 Qs x 5 marks)</option>
                    <option value="Mixed">SAQ + MCQ + LAQ — Mixed Pattern (80 marks)</option>
                </select>
            </div>

            <button type="submit" class="btn-primary" id="generateBtn">
                Generate Question Paper
            </button>

        </form>
    </section>

    <!-- RIGHT: Question Paper Preview Panel -->
    <section class="result-section" id="resultSection">

        <div class="empty-state" id="emptyState">
            <div class="empty-icon">&#128196;</div>
            <p>Your question paper will appear here</p>
            <span>Fill the form and click Generate</span>
        </div>

        <div class="loading-state" id="loadingState" style="display:none;">
            <div class="spinner"></div>
            <p>Generating your question paper...</p>
            <span>This may take a few seconds</span>
        </div>

        <div class="paper-preview" id="paperPreview" style="display:none;">
            <div class="paper-actions">
                <button class="btn-action btn-download" id="btnDownload">&#8595; Download PDF</button>
                <button class="btn-action btn-regenerate" id="btnRegenerate">&#8635; Regenerate</button>
            </div>
            <div class="exam-paper" id="examPaper">
                <div class="exam-header" id="examHeader"></div>
                <hr class="exam-divider"/>
                <div class="exam-questions" id="examQuestions"></div>
            </div>
        </div>

    </section>

</main>

<footer class="footer">
    <p>&copy; <?= date("Y"); ?> Made By Omi Dhir</p>
</footer>

<script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>