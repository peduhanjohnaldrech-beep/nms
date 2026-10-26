path = 'C:/xampp/htdocs/nms/app/views/programs/mns.php'
with open(path, 'r', encoding='utf-8') as f:
    src = f.read()

# ── 1. Vita quick modal: add eligibility panel after "Recording for" ────────
old1 = '                    <p class="mb-3">Recording for: <strong id="vitaName"></strong></p>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Distribution Date</label>\n                        <input type="date" name="distribution_date" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>'
new1 = '                    <p class="mb-3">Recording for: <strong id="vitaName"></strong></p>\n                    <div id="vitaEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Distribution Date</label>\n                        <input type="date" name="distribution_date" id="vitaDate" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>'

if old1 in src:
    src = src.replace(old1, new1, 1)
    print('OK vita quick modal')
else:
    print('FAIL vita quick modal')

# ── 2. Manual vita modal: add eligibility panel after beneficiary select ────
old2 = '                        <input type="hidden" name="date_of_birth" id="manualVitaDob">\n                    </div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Distribution Date</label>\n                        <input type="date" name="distribution_date" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>'
new2 = '                        <input type="hidden" name="date_of_birth" id="manualVitaDob">\n                    </div>\n                    <div id="manualVitaEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Distribution Date</label>\n                        <input type="date" name="distribution_date" id="manualVitaDate" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>'

if old2 in src:
    src = src.replace(old2, new2, 1)
    print('OK manual vita modal')
else:
    print('FAIL manual vita modal')

# ── 3. MNP modal: add eligibility panel after beneficiary select ─────────────
old3 = '                    </div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Date Given</label>\n                        <input type="date" name="date_given" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>\n                    </div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Age Group</label>\n                        <select name="age_group" class="form-select" required>\n                            <option value="6-11 months">6\xe2\x80\x9311 months</option>\n                            <option value="12-23 months">12\xe2\x80\x9323 months</option>\n                        </select>'
new3 = '                    </div>\n                    <div id="mnpEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Date Given</label>\n                        <input type="date" name="date_given" id="mnpDate" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>\n                    </div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Age Group <span class="text-muted fw-normal small">(auto-set)</span></label>\n                        <input type="text" id="mnpAgeGroupDisplay" class="form-control bg-light" readonly>\n                        <input type="hidden" name="age_group" id="mnpAgeGroupHidden">'

if old3 in src:
    src = src.replace(old3, new3, 1)
    print('OK MNP modal')
else:
    # Try bytes version
    old3b = old3.replace('\xe2\x80\x93', '\u2013')
    if old3b in src:
        src = src.replace(old3b, new3, 1)
        print('OK MNP modal (unicode)')
    else:
        print('FAIL MNP modal - trying partial match')
        # Find the section manually
        idx = src.find('id="mnpBeneficiarySelect"')
        if idx >= 0:
            snippet = src[idx:idx+800]
            print('SNIPPET:', repr(snippet[:300]))

# ── 4. LNS modal: add eligibility panel ──────────────────────────────────────
# Find after lnsModal beneficiary select closing tag
old4_marker = '<!-- \u2500\u2500 LNS-SQ Record Modal \u2500\u2500 -->'
idx4 = src.find(old4_marker)
if idx4 < 0:
    old4_marker = '<!-- \xe2\x94\x80\xe2\x94\x80 LNS-SQ Record Modal \xe2\x94\x80\xe2\x94\x80 -->'
    idx4 = src.find(old4_marker)

# Find the date_given input in lnsModal (after idx4)
lns_date_old = '                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Date Given</label>\n                        <input type="date" name="date_given" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>\n                    </div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Age Group</label>\n                        <select name="age_group" class="form-select" required>\n                            <option value="6-11 months">6\u201311 months</option>\n                            <option value="12-23 months">12\u201323 months</option>\n                            <option value="24 months">24 months</option>\n                        </select>'
lns_date_new = '                    <div id="lnsEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Date Given</label>\n                        <input type="date" name="date_given" id="lnsDate" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>\n                    </div>\n                    <div class="mb-3">\n                        <label class="form-label fw-semibold">Age Group <span class="text-muted fw-normal small">(auto-set)</span></label>\n                        <input type="text" id="lnsAgeGroupDisplay" class="form-control bg-light" readonly>\n                        <input type="hidden" name="age_group" id="lnsAgeGroupHidden">'

if lns_date_old in src:
    # Only replace the SECOND occurrence (lnsModal, not mnpModal)
    first = src.find(lns_date_old)
    second = src.find(lns_date_old, first + 1)
    if second >= 0:
        src = src[:second] + lns_date_new + src[second + len(lns_date_old):]
        print('OK LNS modal')
    else:
        print('FAIL LNS modal - only one occurrence found')
else:
    print('FAIL LNS modal - pattern not found')

with open(path, 'w', encoding='utf-8') as f:
    f.write(src)
print('View patches done.')
