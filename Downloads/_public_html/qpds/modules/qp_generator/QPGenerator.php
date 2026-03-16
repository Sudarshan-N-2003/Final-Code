<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

class QPGenerator {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────
    // MAIN GENERATE FUNCTION
    // ─────────────────────────────────────
    public function generate(int $subjectId, int $templateId, array $options = []): array {
        $template = $this->db->fetchOne("SELECT * FROM qp_templates WHERE id=?", [$templateId]);
        $subject  = $this->db->fetchOne("SELECT s.*, d.name as dept_name, d.code as dept_code FROM subjects s JOIN departments d ON s.department_id=d.id WHERE s.id=?", [$subjectId]);

        if (!$template || !$subject) {
            return ['success' => false, 'message' => 'Invalid template or subject'];
        }

        $rules = json_decode($template['vtu_rules'], true);
        $paper = [
            'subject'       => $subject,
            'template'      => $template,
            'exam_type'     => $template['exam_type'],
            'total_marks'   => $template['total_marks'],
            'duration'      => $template['duration_minutes'],
            'academic_year' => $options['academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
            'semester'      => $subject['semester'],
            'sections'      => [],
            'co_po_summary' => [],
            'generated_at'  => date('Y-m-d H:i:s'),
            'set_number'    => $options['set_number'] ?? 1,
        ];

        // Generate each section
        foreach ($rules['sections'] as $section) {
            $sectionQuestions = $this->selectQuestionsForSection($subjectId, $section, $options);
            if (!$sectionQuestions['success']) {
                return $sectionQuestions;
            }
            $paper['sections'][] = [
                'section_label'  => $section['section'],
                'title'          => $section['title'],
                'instruction'    => $section['instruction'],
                'marks_per_q'    => $section['marks_per_question'],
                'questions'      => $sectionQuestions['questions'],
                'or_choice'      => $section['or_choice'] ?? false,
            ];
        }

        // Shuffling
        if (!empty($options['shuffle'])) {
            $paper = $this->shufflePaper($paper, $options['shuffle_type'] ?? 'within_section');
        }

        // CO-PO Summary
        $paper['co_po_summary'] = $this->buildCOPOSummary($paper);

        // Save to DB
        $paperCode = $this->generatePaperCode($subject, $template);
        $paperId   = $this->savePaper($paper, $paperCode, $subjectId, $templateId, $options);

        return [
            'success'    => true,
            'paper_id'   => $paperId,
            'paper_code' => $paperCode,
            'paper'      => $paper,
        ];
    }

    // ─────────────────────────────────────
    // SELECT QUESTIONS FOR A SECTION
    // ─────────────────────────────────────
    private function selectQuestionsForSection(int $subjectId, array $section, array $options): array {
        $unitsClause = implode(',', array_fill(0, count($section['units_covered']), '?'));
        $params = [$subjectId, ...$section['units_covered'], $section['marks_per_question']];

        // Exclude recently used if option set
        $excludeClause = '';
        if (!empty($options['avoid_recent_days'])) {
            $excludeClause = "AND (q.last_used IS NULL OR q.last_used < DATE_SUB(NOW(), INTERVAL ? DAY))";
            $params[] = (int)$options['avoid_recent_days'];
        }

        $needed = $section['questions_to_attempt'];

        $sql = "SELECT q.*, co.co_code, co.co_description, co.bloom_level,
                       su.unit_title, su.unit_number
                FROM questions q
                JOIN subject_units su ON q.unit_id=su.id
                JOIN course_outcomes co ON q.co_id=co.id
                WHERE q.subject_id=?
                  AND su.unit_number IN ($unitsClause)
                  AND q.marks=?
                  AND q.is_active=1
                  AND q.is_approved=1
                  $excludeClause
                ORDER BY RAND()
                LIMIT ?";
        $params[] = $needed * 2; // Fetch extra for diversity

        $questions = $this->db->fetchAll($sql, $params);

        // Try unnapproved if not enough
        if (count($questions) < $needed) {
            $sql2 = "SELECT q.*, co.co_code, co.co_description, co.bloom_level,
                           su.unit_title, su.unit_number
                    FROM questions q
                    JOIN subject_units su ON q.unit_id=su.id
                    JOIN course_outcomes co ON q.co_id=co.id
                    WHERE q.subject_id=?
                      AND su.unit_number IN ($unitsClause)
                      AND q.marks=?
                      AND q.is_active=1
                    ORDER BY RAND() LIMIT ?";
            $questions = $this->db->fetchAll($sql2, [$subjectId, ...$section['units_covered'], $section['marks_per_question'], $needed]);
        }

        if (count($questions) < $needed) {
            return [
                'success' => false,
                'message' => "Not enough questions for Section {$section['section']}. Need $needed, found " . count($questions) . ". Please add more questions to the question bank."
            ];
        }

        // Ensure CO diversity if possible
        $selected = $this->selectWithCODiversity($questions, $needed);

        return ['success' => true, 'questions' => $selected];
    }

    // ─────────────────────────────────────
    // SELECT QUESTIONS WITH CO DIVERSITY
    // ─────────────────────────────────────
    private function selectWithCODiversity(array $questions, int $needed): array {
        // Group by CO
        $byCO = [];
        foreach ($questions as $q) {
            $byCO[$q['co_id']][] = $q;
        }

        $selected = [];
        $coKeys   = array_keys($byCO);
        shuffle($coKeys);

        // Round-robin across COs
        $i = 0;
        while (count($selected) < $needed) {
            $co = $coKeys[$i % count($coKeys)];
            if (!empty($byCO[$co])) {
                $q = array_shift($byCO[$co]);
                if (!in_array($q['id'], array_column($selected, 'id'))) {
                    $selected[] = $q;
                }
            }
            $i++;
            if ($i > 1000) break; // safety
        }

        return array_slice($selected, 0, $needed);
    }

    // ─────────────────────────────────────
    // SHUFFLING ENGINE
    // ─────────────────────────────────────
    public function shufflePaper(array $paper, string $type = 'within_section'): array {
        switch ($type) {
            case 'within_section':
                foreach ($paper['sections'] as &$section) {
                    shuffle($section['questions']);
                }
                break;

            case 'full_shuffle':
                // Shuffle within each section but also reorder sections randomly
                foreach ($paper['sections'] as &$section) {
                    shuffle($section['questions']);
                }
                // Note: don't shuffle sections themselves for VTU format
                break;

            case 'question_swap':
                // Swap questions between OR pairs (for SEE)
                foreach ($paper['sections'] as &$section) {
                    if (!empty($section['or_choice']) && count($section['questions']) >= 2) {
                        $mid = intdiv(count($section['questions']), 2);
                        $first  = array_slice($section['questions'], 0, $mid);
                        $second = array_slice($section['questions'], $mid);
                        shuffle($first);
                        shuffle($second);
                        $section['questions'] = array_merge($first, $second);
                    }
                }
                break;
        }

        return $paper;
    }

    // ─────────────────────────────────────
    // GENERATE MULTIPLE SETS
    // ─────────────────────────────────────
    public function generateSets(int $subjectId, int $templateId, int $numSets, array $options = []): array {
        $sets = [];
        for ($i = 1; $i <= $numSets; $i++) {
            $opts = array_merge($options, ['set_number' => $i, 'shuffle' => true, 'shuffle_type' => 'within_section']);
            $result = $this->generate($subjectId, $templateId, $opts);
            if (!$result['success']) return $result;
            $sets[] = $result;
        }
        return ['success' => true, 'sets' => $sets, 'count' => $numSets];
    }

    // ─────────────────────────────────────
    // CO-PO SUMMARY BUILDER
    // ─────────────────────────────────────
    private function buildCOPOSummary(array $paper): array {
        $coIds = [];
        foreach ($paper['sections'] as $section) {
            foreach ($section['questions'] as $q) {
                $coIds[] = $q['co_id'];
            }
        }
        $coIds = array_unique($coIds);
        if (empty($coIds)) return [];

        $inClause = implode(',', array_fill(0, count($coIds), '?'));
        $mappings = $this->db->fetchAll(
            "SELECT co.co_code, co.co_description, co.bloom_level,
                    po.po_code, po.po_description, cpm.mapping_level
             FROM co_po_mapping cpm
             JOIN course_outcomes co ON cpm.co_id=co.id
             JOIN program_outcomes po ON cpm.po_id=po.id
             WHERE cpm.co_id IN ($inClause)",
            $coIds
        );

        $summary = [];
        foreach ($mappings as $m) {
            $summary[$m['co_code']][$m['po_code']] = $m['mapping_level'];
        }
        return $summary;
    }

    // ─────────────────────────────────────
    // PAPER CODE GENERATOR
    // ─────────────────────────────────────
    private function generatePaperCode(array $subject, array $template): string {
        return strtoupper($subject['dept_code']) . '-' .
               $subject['subject_code'] . '-' .
               $template['exam_type'] . '-' .
               date('Ymd') . '-' .
               strtoupper(substr(uniqid(), -4));
    }

    // ─────────────────────────────────────
    // SAVE PAPER TO DB
    // ─────────────────────────────────────
    private function savePaper(array $paper, string $code, int $subjectId, int $templateId, array $options): int {
        $userId = $_SESSION['user_id'] ?? 0;

        $paperId = $this->db->insert('question_papers', [
            'paper_code'     => $code,
            'subject_id'     => $subjectId,
            'template_id'    => $templateId,
            'exam_type'      => $paper['exam_type'],
            'academic_year'  => $paper['academic_year'],
            'semester'       => $paper['semester'],
            'generated_by'   => $userId,
            'status'         => 'draft',
            'paper_data'     => json_encode($paper),
            'set_number'     => $paper['set_number'],
            'total_marks'    => $paper['total_marks'],
            'duration_minutes' => $paper['duration'],
            'instructions'   => $options['instructions'] ?? '',
            'watermark_text' => $options['watermark'] ?? WATERMARK_DEFAULT,
        ]);

        // Save individual questions
        $order = 1;
        foreach ($paper['sections'] as $section) {
            $qNum = 1;
            foreach ($section['questions'] as $q) {
                $this->db->insert('paper_questions', [
                    'paper_id'       => $paperId,
                    'question_id'    => $q['id'],
                    'section'        => $section['section_label'],
                    'question_number' => $qNum++,
                    'marks'          => $q['marks'],
                    'display_order'  => $order++,
                ]);
                // Update usage count
                $this->db->update('questions', [
                    'times_used' => $q['times_used'] + 1,
                    'last_used'  => date('Y-m-d')
                ], 'id=?', [$q['id']]);
            }
        }

        Auth::logActivity($userId, 'GENERATE_QP', 'qp_generator', "Generated paper: $code");

        return $paperId;
    }

    // ─────────────────────────────────────
    // RENDER PAPER HTML (for print/preview)
    // ─────────────────────────────────────
    public function renderPaperHTML(int $paperId, bool $showAnswers = false): string {
        $paper = $this->db->fetchOne("SELECT qp.*, s.subject_name, s.subject_code, s.semester, d.name as dept_name, u.full_name as generated_by_name, t.template_name FROM question_papers qp JOIN subjects s ON qp.subject_id=s.id JOIN departments d ON s.department_id=d.id JOIN users u ON qp.generated_by=u.id JOIN qp_templates t ON qp.template_id=t.id WHERE qp.id=?", [$paperId]);
        if (!$paper) return '<p>Paper not found</p>';

        $college = $this->db->fetchOne("SELECT * FROM college_info LIMIT 1");
        $data    = json_decode($paper['paper_data'], true);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($paper['paper_code']) ?></title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman&family=Arial&display=swap');
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #000; background: #fff; padding: 20mm; }
                .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
                .college-name { font-size: 18pt; font-weight: bold; text-transform: uppercase; }
                .affiliation { font-size: 10pt; margin: 4px 0; }
                .paper-title { font-size: 14pt; font-weight: bold; margin: 8px 0; }
                .meta-table { width: 100%; margin: 10px 0; }
                .meta-table td { padding: 3px 8px; font-size: 11pt; }
                .meta-table .label { font-weight: bold; width: 40%; }
                .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-45deg); font-size: 72pt; color: rgba(200,200,200,0.15); font-weight: bold; pointer-events: none; z-index: -1; text-transform: uppercase; letter-spacing: 10px; }
                .section-header { background: #f0f0f0; border: 1px solid #999; padding: 6px 12px; font-weight: bold; font-size: 12pt; margin: 15px 0 10px; }
                .instruction { font-style: italic; margin: 5px 0 10px 15px; font-size: 11pt; }
                .question-row { display: flex; margin: 8px 0; padding: 5px 0; border-bottom: 1px dotted #ccc; }
                .q-num { min-width: 35px; font-weight: bold; }
                .q-text { flex: 1; line-height: 1.6; }
                .q-meta { min-width: 120px; text-align: right; font-size: 10pt; color: #333; }
                .co-badge { background: #e8f4fd; border: 1px solid #b3d9f5; border-radius: 3px; padding: 1px 5px; font-size: 9pt; margin-left: 5px; }
                .marks-badge { background: #000; color: #fff; padding: 2px 8px; font-weight: bold; font-size: 10pt; }
                .or-divider { text-align: center; font-weight: bold; margin: 8px 0; font-size: 13pt; letter-spacing: 4px; color: #555; }
                .co-po-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10pt; }
                .co-po-table th, .co-po-table td { border: 1px solid #999; padding: 4px 8px; text-align: center; }
                .co-po-table th { background: #333; color: #fff; }
                .page-break { page-break-after: always; }
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 15mm; }
                    .watermark { position: fixed; }
                }
            </style>
        </head>
        <body>
            <div class="watermark"><?= htmlspecialchars($paper['watermark_text']) ?></div>

            <!-- HEADER -->
            <div class="header">
                <div class="college-name"><?= htmlspecialchars($college['college_name'] ?? 'YOUR COLLEGE') ?></div>
                <div class="affiliation"><?= htmlspecialchars($college['affiliated_to'] ?? VTU_AFFILIATION) ?></div>
                <div class="affiliation">Department of <?= htmlspecialchars($paper['dept_name']) ?></div>
                <div class="paper-title">
                    <?= htmlspecialchars($paper['exam_type']) ?> EXAMINATION - <?= htmlspecialchars($paper['academic_year']) ?>
                </div>
            </div>

            <!-- META INFO -->
            <table class="meta-table">
                <tr>
                    <td class="label">Subject Code:</td>
                    <td><?= htmlspecialchars($paper['subject_code']) ?></td>
                    <td class="label">Subject Name:</td>
                    <td><?= htmlspecialchars($paper['subject_name']) ?></td>
                </tr>
                <tr>
                    <td class="label">Semester:</td>
                    <td><?= htmlspecialchars($paper['semester']) ?></td>
                    <td class="label">Max Marks:</td>
                    <td><?= htmlspecialchars($paper['total_marks']) ?></td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td>_____________________</td>
                    <td class="label">Duration:</td>
                    <td><?= htmlspecialchars($paper['duration_minutes'] / 60) ?> Hours</td>
                </tr>
                <tr>
                    <td class="label">Paper Code:</td>
                    <td colspan="3"><strong><?= htmlspecialchars($paper['paper_code']) ?></strong>
                        <?php if ($data['set_number'] > 1): ?>
                            &nbsp;&nbsp;<span style="background:#000;color:#fff;padding:2px 10px;font-weight:bold;">SET <?= chr(64 + $data['set_number']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php if ($paper['instructions']): ?>
            <div style="border: 1px solid #999; padding: 8px 12px; margin: 10px 0; background: #fffbf0;">
                <strong>Instructions:</strong> <?= nl2br(htmlspecialchars($paper['instructions'])) ?>
            </div>
            <?php endif; ?>

            <!-- SECTIONS & QUESTIONS -->
            <?php foreach ($data['sections'] as $sIdx => $section): ?>
            <div class="section-header">
                PART <?= htmlspecialchars($section['section_label']) ?> – <?= htmlspecialchars($section['title']) ?>
                &nbsp;&nbsp;<small>(<?= count($section['questions']) ?> Questions × <?= $section['marks_per_q'] ?> Marks)</small>
            </div>
            <div class="instruction"><?= htmlspecialchars($section['instruction']) ?></div>

            <?php
            $qNum = ($sIdx * 10) + 1;
            $total = count($section['questions']);
            foreach ($section['questions'] as $idx => $q):
            ?>
            <?php if (!empty($section['or_choice']) && $idx > 0 && $idx % 2 === 0): ?>
                <div style="height:10px"></div>
            <?php endif; ?>
            <div class="question-row">
                <div class="q-num">Q<?= $qNum++ ?>.</div>
                <div class="q-text"><?= nl2br(htmlspecialchars($q['question_text'])) ?>
                    <?php if ($q['diagram_required']): ?>
                        <br><em style="color:#666;">[Draw a neat diagram where necessary]</em>
                    <?php endif; ?>
                </div>
                <div class="q-meta">
                    <span class="marks-badge">[<?= $q['marks'] ?> M]</span>
                    <br><span class="co-badge"><?= htmlspecialchars($q['co_code']) ?></span>
                    <br><small><?= htmlspecialchars($q['bloom_level']) ?></small>
                </div>
            </div>
            <?php if (!empty($section['or_choice']) && $idx % 2 === 0 && $idx < $total - 1): ?>
                <div class="or-divider">— OR —</div>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php endforeach; ?>

            <!-- CO-PO TABLE -->
            <?php if (!empty($data['co_po_summary'])): ?>
            <div style="margin-top:20px;page-break-inside:avoid;">
                <div class="section-header" style="font-size:11pt;">CO-PO ATTAINMENT MAPPING</div>
                <table class="co-po-table">
                    <tr>
                        <th>CO/PO</th>
                        <?php
                        $poKeys = [];
                        foreach ($data['co_po_summary'] as $co => $pos) {
                            foreach ($pos as $po => $level) $poKeys[] = $po;
                        }
                        $poKeys = array_unique($poKeys);
                        sort($poKeys);
                        foreach ($poKeys as $po): ?>
                        <th><?= htmlspecialchars($po) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php foreach ($data['co_po_summary'] as $co => $pos): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($co) ?></strong></td>
                        <?php foreach ($poKeys as $po): ?>
                        <td><?= isset($pos[$po]) ? $pos[$po] : '-' ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <small style="display:block;margin-top:5px;color:#666;">Mapping: 1=Low, 2=Medium, 3=High</small>
            </div>
            <?php endif; ?>

            <div style="margin-top:25px;border-top:1px solid #999;padding-top:10px;font-size:10pt;color:#666;text-align:center;">
                Generated by QPDS | Paper: <?= htmlspecialchars($paper['paper_code']) ?> | Generated by: <?= htmlspecialchars($paper['generated_by_name']) ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
