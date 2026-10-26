path = 'C:/xampp/htdocs/nms/app/views/programs/mns.php'
with open(path, 'r', encoding='utf-8') as f:
    src = f.read()

marker = '\u2500\u2500 LNS-SQ Record Modal \u2500\u2500'
idx = src.find(marker)
if idx < 0:
    print('FAIL: LNS modal marker not found')
    exit(1)

lns_section = src[idx:]

old_date = ('                    <div class="mb-3">\n'
            '                        <label class="form-label fw-semibold">Date Given</label>\n'
            '                        <input type="date" name="date_given" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>\n'
            '                    </div>\n'
            '                    <div class="mb-3">\n'
            '                        <label class="form-label fw-semibold">Age Group</label>\n'
            '                        <select name="age_group" class="form-select" required>\n'
            '                            <option value="6-11 months">6\u201311 months</option>\n'
            '                            <option value="12-23 months">12\u201323 months</option>\n'
            '                            <option value="24 months">24 months</option>\n'
            '                        </select>\n'
            '                    </div>')

new_date = ('                    <div id="lnsEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>\n'
            '                    <div class="mb-3">\n'
            '                        <label class="form-label fw-semibold">Date Given</label>\n'
            '                        <input type="date" name="date_given" id="lnsDate" class="form-control" value="<?= date(\'Y-m-d\') ?>" required>\n'
            '                    </div>\n'
            '                    <div class="mb-3">\n'
            '                        <label class="form-label fw-semibold">Age Group <span class="text-muted fw-normal small">(auto-set)</span></label>\n'
            '                        <input type="text" id="lnsAgeGroupDisplay" class="form-control bg-light" readonly>\n'
            '                        <input type="hidden" name="age_group" id="lnsAgeGroupHidden">\n'
            '                    </div>')

if old_date in lns_section:
    lns_section = lns_section.replace(old_date, new_date, 1)
    src = src[:idx] + lns_section
    print('OK LNS modal')
else:
    print('FAIL: pattern not found')
    dg = lns_section.find('Date Given')
    print('SNIPPET:', repr(lns_section[dg:dg+400]))
    exit(1)

with open(path, 'w', encoding='utf-8') as f:
    f.write(src)
print('Done.')
