document.getElementById("paperForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const payload = {
        board:    document.querySelector('[name="board"]').value,
        class:    document.querySelector('[name="class"]').value,
        level:    document.querySelector('[name="level"]').value,
        language: document.querySelector('[name="language"]').value,
        qtype:    document.querySelector('[name="qtype"]').value,
        mode:     "json"
    };

    showLoading();

    fetch("API/generate_pdf.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        console.log("Full API response:", JSON.stringify(data, null, 2));

        if (data.error) throw new Error("API Error: " + data.error);

        // Normalize: if sections missing but questions exists at top level, wrap it
        if (!data.sections && data.questions) {
            data.sections = [{
                name: "Questions",
                instruction: "",
                questions: data.questions
            }];
        }

        if (!data.sections || !Array.isArray(data.sections) || data.sections.length === 0) {
            throw new Error(
                "sections missing in response.\n" +
                "Keys: " + Object.keys(data).join(", ") + "\n" +
                "Raw: " + JSON.stringify(data).substring(0, 200)
            );
        }

        renderPaper(data);
    })
    .catch(err => {
        hideLoading();
        alert("Error generating paper:\n\n" + err.message);
        console.error(err);
    });
});

/* ── Render paper on screen ── */
function renderPaper(data) {

    // Header
    document.getElementById("examHeader").innerHTML = `
        <div class="exam-header-row">
            <span><strong>BOARD</strong> \u2014 ${data.board}</span>
            <span><strong>CLASS</strong> \u2014 ${data.class}</span>
            <span><strong>DATE</strong> \u2014 ${data.date}</span>
        </div>
        <div class="exam-header-row">
            <span><strong>SUBJECT</strong> \u2014 ${data.language}</span>
            <span><strong>DIFFICULTY</strong> \u2014 ${data.level}</span>
            <span><strong>FULL MARKS</strong> \u2014 ${data.full_marks} &nbsp;|&nbsp; <strong>TIME</strong> \u2014 ${data.time}</span>
        </div>
        <div class="exam-header-row">
            <span><strong>TYPE</strong> \u2014 ${data.qtype}</span>
        </div>
    `;

    // Sections + Questions
    const qContainer = document.getElementById("examQuestions");
    qContainer.innerHTML = "";

    data.sections.forEach(function(section) {
        // Section heading
        const secDiv = document.createElement("div");
        secDiv.className = "section-header";
        secDiv.textContent = section.name || "";
        qContainer.appendChild(secDiv);

        // Instruction
        if (section.instruction) {
            const instrDiv = document.createElement("div");
            instrDiv.className = "section-instruction";
            instrDiv.textContent = section.instruction;
            qContainer.appendChild(instrDiv);
        }

        // Questions
        const qs = section.questions || [];
        qs.forEach(function(q) {
            // Strip any leftover markdown or answer text
            q.text = (q.text || "")
                .replace(/\[Answer:?\s*[A-Za-z0-9]*\]/gi, "")
                .replace(/\(Answer:?\s*[A-Za-z0-9]*\)/gi, "")
                .replace(/Answer:?\s*[A-D]\b/gi, "")
                .replace(/\*{1,3}(.*?)\*{1,3}/g, "$1")
                .replace(/<[^>]+>/g, "")
                .trim();
            const div = document.createElement("div");
            div.className = "exam-question";
            div.innerHTML =
                '<div class="q-text">' +
                    '<span class="q-number">' + q.number + '.</span> ' +
                    '<span class="q-body">' + q.text + '</span>' +
                '</div>' +
                '<div class="q-marks">(' + q.marks + ' mark' + (q.marks > 1 ? 's' : '') + ')</div>';
            qContainer.appendChild(div);
        });
    });

    // Show panel
    hideLoading();
    document.getElementById("emptyState").style.display   = "none";
    document.getElementById("paperPreview").style.display = "block";

    if (window.innerWidth <= 768) {
        document.getElementById("resultSection").scrollIntoView({ behavior: "smooth" });
    }

    // Store for PDF download
    window._lastPayload = {
        board:    data.board,
        class:    data.class,
        level:    data.level,
        language: data.language,
        qtype:    data.qtype,
        mode:     "pdf"
    };
}

/* ── Download PDF ── */
document.getElementById("btnDownload").addEventListener("click", function () {
    if (!window._lastPayload) return;
    this.textContent = "⏳ Generating PDF...";
    this.disabled = true;
    const btn = this;

    fetch("API/generate_pdf.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(window._lastPayload)
    })
    .then(function(res) {
        const ct = res.headers.get("Content-Type");
        if (!ct || !ct.includes("application/pdf")) {
            return res.text().then(function(t) { throw new Error(t); });
        }
        return res.blob();
    })
    .then(function(blob) {
        const url = window.URL.createObjectURL(blob);
        const a   = document.createElement("a");
        a.href     = url;
        a.download = "question_paper.pdf";
        document.body.appendChild(a);
        a.click();
        a.remove();
        btn.textContent = "⬇ Download PDF";
        btn.disabled    = false;
    })
    .catch(function(err) {
        alert("PDF Error:\n\n" + err.message);
        btn.textContent = "⬇ Download PDF";
        btn.disabled    = false;
    });
});

/* ── Regenerate ── */
document.getElementById("btnRegenerate").addEventListener("click", function () {
    document.getElementById("paperForm").dispatchEvent(new Event("submit"));
});

/* ── Helpers ── */
function showLoading() {
    document.getElementById("emptyState").style.display   = "none";
    document.getElementById("paperPreview").style.display = "none";
    document.getElementById("loadingState").style.display = "flex";
    document.getElementById("generateBtn").disabled       = true;
    document.getElementById("generateBtn").textContent    = "Generating...";
}

function hideLoading() {
    document.getElementById("loadingState").style.display = "none";
    document.getElementById("generateBtn").disabled       = false;
    document.getElementById("generateBtn").textContent    = "Generate Question Paper";
}