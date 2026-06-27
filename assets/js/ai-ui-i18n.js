(function (global) {
    'use strict';

    var CFG = global.__TOPCV_AI_I18N__ || {};
    var STORAGE_KEY = CFG.storageKey || 'topcv_ai_ui_lang';
    var DEFAULT_LANG = CFG.defaultLang || 'en';
    var LABELS = CFG.labels || {};
    var PHRASES = CFG.phrases || {};
    var ENUMS = CFG.enums || {};

    var employerRawPayload = null;
    var candidateRawJob = null;

    function normalizeLang(lang) {
        return String(lang || '').toLowerCase() === 'vi' ? 'vi' : 'en';
    }

    function isEnglish(lang) {
        return normalizeLang(lang) === 'en';
    }

    function getLang() {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            if (stored === 'vi' || stored === 'en') {
                return stored;
            }
        } catch (e) {
            /* ignore */
        }
        return DEFAULT_LANG;
    }

    function setLang(lang) {
        lang = normalizeLang(lang);
        try {
            localStorage.setItem(STORAGE_KEY, lang);
        } catch (e) {
            /* ignore */
        }
        return lang;
    }

    function tLabel(key, lang) {
        lang = isEnglish(lang) ? 'en' : 'vi';
        var row = LABELS[key];
        if (row && row[lang]) {
            return row[lang];
        }
        return key;
    }

    function tEnum(group, value, lang) {
        if (isEnglish(lang) || value == null || value === '') {
            return value == null ? '' : String(value);
        }
        var map = ENUMS[group] || {};
        var str = String(value).trim();
        if (map[str]) {
            return map[str];
        }
        var lower = str.toLowerCase();
        var keys = Object.keys(map);
        for (var i = 0; i < keys.length; i++) {
            if (keys[i].toLowerCase() === lower) {
                return map[keys[i]];
            }
        }
        return str;
    }

    var FRAGMENT_RULES = [
        [/If you have real experience with (.+?), add it explicitly in your Skills section and mention one concrete usage example in work or projects\.?/gi,
            'Nếu bạn có kinh nghiệm thực tế với $1, hãy ghi rõ trong mục Kỹ năng và nêu một ví dụ cụ thể trong kinh nghiệm hoặc dự án.'],
        [/If you have real exposure to (.+?), add it explicitly in your Skills section and mention one concrete usage example in work or projects\.?/gi,
            'Nếu bạn có kinh nghiệm thực tế với $1, hãy ghi rõ trong mục Kỹ năng và nêu một ví dụ cụ thể trong kinh nghiệm hoặc dự án.'],
        [/To improve your fit,\s*/gi, 'Để cải thiện mức phù hợp, '],
        [/your profile only partially matches the must-have requirements\.?/gi,
            'hồ sơ của bạn chỉ khớp một phần với các yêu cầu bắt buộc.'],
        [/Source-aware calibration adjusted the score from (\d+)\/100 to (\d+)\/100 because /gi,
            'Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm từ $1/100 lên $2/100 vì '],
        [/Source-aware calibration adjusted the score:\s*/gi, 'Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm: '],
        [/This sparse JD relies on promoted technical signals, and the promoted core requirements are strongly confirmed in the CV\.?/gi,
            'JD thiếu chi tiết nên AI dựa vào tín hiệu kỹ thuật được promote; các yêu cầu cốt lõi được promote đã có bằng chứng mạnh trong CV.'],
        [/The candidate's strongest technical profile aligns with the JD role family\.?/gi,
            'Hồ sơ kỹ thuật mạnh nhất của ứng viên phù hợp với nhóm vai trò trong JD.'],
        [/explicit must-have requirement coverage is still too limited for a stronger score\.?/gi,
            'độ phủ yêu cầu bắt buộc rõ ràng vẫn còn hạn chế nên chưa đủ điểm cao hơn.'],
        [/The candidate appears to meet the experience requirement, but hard-skill evidence is incomplete; review missing and weakly evidenced must-have skills before shortlisting\.?/gi,
            'Ứng viên có vẻ đáp ứng yêu cầu kinh nghiệm, nhưng bằng chứng kỹ năng cứng chưa đầy đủ; cần rà soát các kỹ năng bắt buộc còn thiếu hoặc bằng chứng yếu trước khi shortlist.'],
        [/Domain alignment may need recruiter review before relying on the semantic fit score\.?/gi,
            'Mức phù hợp domain có thể cần recruiter xem lại trước khi tin hoàn toàn vào điểm phù hợp ngữ nghĩa.'],
        [/Clear evidence for (.+?) via (.+?), and (.+?)\./gi, 'Có bằng chứng rõ cho $1 thông qua $2 và $3.'],
        [/Clear evidence for (.+?) via (.+?)\./gi, 'Có bằng chứng rõ cho $1 thông qua $2.'],
        [/Have experience in /gi, 'Có kinh nghiệm với '],
        [/add it explicitly in your Skills section/gi, 'ghi rõ trong mục Kỹ năng'],
        [/mention one concrete usage example in work or projects/gi, 'nêu một ví dụ cụ thể trong kinh nghiệm hoặc dự án'],
        [/before shortlisting/gi, 'trước khi shortlist'],
        [/before relying on the semantic fit score/gi, 'trước khi tin hoàn toàn vào điểm phù hợp ngữ nghĩa'],
        [/hard-skill evidence is incomplete/gi, 'bằng chứng kỹ năng cứng chưa đầy đủ'],
        [/weakly evidenced must-have skills/gi, 'các kỹ năng bắt buộc có bằng chứng yếu'],
        [/review missing and/gi, 'rà soát các mục còn thiếu và'],
        [/The candidate appears to meet the experience requirement, but /gi, 'Ứng viên có vẻ đáp ứng yêu cầu kinh nghiệm, nhưng '],
        [/Domain alignment may need recruiter review/gi, 'Mức phù hợp domain có thể cần recruiter xem lại'],
        [/Graduated University in /gi, 'Tốt nghiệp đại học ngành '],
        [/should be reviewed separately:/gi, 'cần xem xét riêng:'],
        [/should be verified during interview:/gi, 'cần xác minh khi phỏng vấn:'],
        [/Education requirements /gi, 'Yêu cầu học vấn '],
        [/Soft skills /gi, 'Kỹ năng mềm '],
        [/Language requirements /gi, 'Yêu cầu ngoại ngữ '],
        [/Enthusiastic and eager to learn/gi, 'Nhiệt tình và ham học hỏi'],
        [/Good at writing & speaking English to work with oversea team/gi, 'Viết và nói tiếng Anh tốt để làm việc với team nước ngoài'],
        [/related subject/gi, 'liên quan'],
        [/or related subject/gi, 'hoặc ngành liên quan'],
        [/Preferred Qualifications/gi, 'yêu cầu ưu tiên'],
        [/optional growth strength for similar roles/gi, 'điểm phát triển thêm cho các vị trí tương tự'],
        [/If you have real exposure to /gi, 'Nếu bạn có kinh nghiệm thực tế với '],
        [/, add it as an /gi, ', hãy bổ sung như một '],
        [/several core requirements are still missing or weakly evidenced\.?/gi,
            'một số yêu cầu cốt lõi vẫn còn thiếu hoặc có bằng chứng còn yếu'],
        [/The candidate's strongest technical profile points to a different role family than the JD\.?/gi,
            'Hồ sơ kỹ thuật mạnh nhất của ứng viên thuộc nhóm vai trò khác với JD'],
        [/Hard-skill gate applied: the base score was capped from (\d+)\/100 to (\d+)\/100\. Confirmed coverage for explicit must-have requirements is below the Strong Review threshold\.?/gi,
            'Đã áp dụng cổng kỹ năng cứng: điểm cơ bản bị giới hạn từ $1/100 xuống $2/100. Độ phủ xác nhận cho yêu cầu bắt buộc rõ ràng thấp hơn ngưỡng đề xuất mạnh'],
        [/ideally on mobile \/ edge platforms/gi, 'lý tưởng trên nền tảng mobile / edge'],
        [/Bachelor'?s \/ Master'?s \/ PhD in Computer Science, AI, Electrical Engineering, or related fields\.?/gi,
            'Cử nhân / Thạc sĩ / Tiến sĩ ngành Khoa học Máy tính, AI, Kỹ thuật Điện hoặc các ngành liên quan'],
        [/Strong problem solving, debugging, and analytical skills/gi, 'Kỹ năng giải quyết vấn đề, debug và phân tích tốt'],
        [/Good communication skills/gi, 'Kỹ năng giao tiếp tốt'],
        [/ability to explain trade offs to product, mobile, and operations teams/gi,
            'khả năng giải thích trade-off với team sản phẩm, mobile và vận hành'],
        [/Domain context to consider:/gi, 'Bối cảnh domain cần cân nhắc:'],
        [/Research state-of-the-art techniques in /gi, 'Nghiên cứu các kỹ thuật tiên tiến về '],
        [/face recognition/gi, 'nhận diện khuôn mặt'],
        [/spoof detection/gi, 'phát hiện giả mạo'],
        [/domain generalization/gi, 'tổng quát hóa domain'],
        [/apply to real-world constraints/gi, 'áp dụng vào ràng buộc thực tế'],
        [/How would you handle /gi, 'Bạn sẽ xử lý '],
        [/Built a /gi, 'Đã xây dựng '],
        [/Built /gi, 'Đã xây dựng '],
        [/Developed a /gi, 'Đã phát triển '],
        [/Developed /gi, 'Đã phát triển '],
        [/Managed /gi, 'Đã quản lý '],
        [/Implemented /gi, 'Đã triển khai '],
        [/Designed /gi, 'Đã thiết kế '],
        [/facial verification platform/gi, 'nền tảng xác minh khuôn mặt'],
        [/for online banking onboarding/gi, 'cho onboarding ngân hàng trực tuyến'],
        [/and customer identity verification/gi, 'và xác minh danh tính khách hàng'],
        [/customer identity verification/gi, 'xác minh danh tính khách hàng'],
        [/online banking/gi, 'ngân hàng trực tuyến'],
        [/Technologies:/gi, 'Công nghệ:'],
        [/Technologies/gi, 'Công nghệ'],
        [/Computer Science/gi, 'Khoa học Máy tính'],
        [/Electrical Engineering/gi, 'Kỹ thuật Điện'],
        [/related fields/gi, 'các ngành liên quan'],
        [/problem solving/gi, 'giải quyết vấn đề'],
        [/analytical skills/gi, 'kỹ năng phân tích'],
        [/communication skills/gi, 'kỹ năng giao tiếp'],
        [/trade offs/gi, 'trade-off'],
        [/trade-offs/gi, 'trade-off'],
        [/operations teams/gi, 'team vận hành'],
        [/product, mobile, and operations teams/gi, 'team sản phẩm, mobile và vận hành'],
        [/Strong Review threshold/gi, 'ngưỡng đề xuất mạnh'],
        [/Strong Review/gi, 'đề xuất mạnh'],
        [/must-have requirements/gi, 'yêu cầu bắt buộc'],
        [/weakly evidenced/gi, 'bằng chứng còn yếu'],
        [/missing or weakly evidenced/gi, 'còn thiếu hoặc bằng chứng yếu'],
        [/role family/gi, 'nhóm vai trò'],
        [/different role family/gi, 'nhóm vai trò khác'],
        [/edge platforms/gi, 'nền tảng edge'],
        [/mobile \/ edge/gi, 'mobile / edge'],
        [/debugging/gi, 'debug'],
        [/state-of-the-art/gi, 'tiên tiến'],
        [/real-world constraints/gi, 'ràng buộc thực tế'],
        [/real-world/gi, 'thực tế'],
        [/onboarding/gi, 'onboarding'],
        [/verification/gi, 'xác minh'],
        [/platform/gi, 'nền tảng'],
        [/platforms/gi, 'nền tảng'],
        [/requirements/gi, 'yêu cầu'],
        [/requirement/gi, 'yêu cầu'],
        [/experience/gi, 'kinh nghiệm'],
        [/skills/gi, 'kỹ năng'],
        [/skill/gi, 'kỹ năng'],
        [/teams/gi, 'team'],
        [/team/gi, 'team'],
        [/ and /g, ' và '],
        [/ or /g, ' hoặc '],
        [/; /g, '; ']
    ];

    function applyFragmentRules(text, lang, depth) {
        depth = depth || 0;
        if (isEnglish(lang) || !text || depth > 5) {
            return text;
        }
        var out = String(text);
        var changed = false;
        for (var i = 0; i < FRAGMENT_RULES.length; i++) {
            var next = out.replace(FRAGMENT_RULES[i][0], FRAGMENT_RULES[i][1]);
            if (next !== out) {
                out = next;
                changed = true;
            }
        }
        if (changed) {
            return applyFragmentRules(out, lang, depth + 1);
        }
        return out;
    }

    function translateColonSegments(text, lang, depth) {
        depth = depth || 0;
        if (isEnglish(lang) || !text || text.indexOf(': ') < 0 || depth > 3) {
            return text;
        }
        if (/\(level\s*\d+/i.test(text)) {
            return text;
        }
        var idx = text.indexOf(': ');
        var head = applyFragmentRules(text.substring(0, idx), lang);
        var tail = text.substring(idx + 2);
        var newTail = applyFragmentRules(tail, lang);
        if (newTail.indexOf(': ') >= 0) {
            newTail = translateColonSegments(newTail, lang, depth + 1);
        }
        return head + ': ' + newTail;
    }

    function finalizeViText(text, lang) {
        if (isEnglish(lang) || !text) {
            return text;
        }
        var out = applyFragmentRules(String(text), lang);
        out = translateColonSegments(out, lang);
        return applyFragmentRules(out, lang);
    }

    function tReasonCode(code, lang) {
        var str = String(code || '').trim();
        if (str === '' || isEnglish(lang)) {
            return str;
        }
        var map = ENUMS.reason_code || {};
        var key = str.toLowerCase();
        if (map[key]) {
            return map[key];
        }
        return str.replace(/_/g, ' ');
    }

    function formatRequirementItem(item) {
        if (item == null) {
            return '';
        }
        if (typeof item === 'string' || typeof item === 'number') {
            return String(item);
        }
        if (typeof item === 'object') {
            return String(
                item.skill || item.requirement || item.label || item.text
                || item.name || item.term || item.value || ''
            );
        }
        return String(item);
    }

    function humanizeRecoverySummary(value, lang) {
        if (value == null || value === '') {
            return '';
        }
        if (typeof value === 'string') {
            return tText(value, lang);
        }
        if (typeof value !== 'object') {
            return String(value);
        }

        var lines = [];
        if (value.recovery_triggered === true) {
            lines.push(isEnglish(lang)
                ? 'Sparse technical recovery was triggered for this JD.'
                : 'Đã kích hoạt khôi phục yêu cầu kỹ thuật vì JD thiếu thông tin rõ ràng.');
        }
        if (value.recovery_reason) {
            var reasonKey = String(value.recovery_reason).toLowerCase();
            if (reasonKey === 'sparse_explicit_technical_requirements') {
                lines.push(isEnglish(lang)
                    ? 'Reason: explicit technical requirements in the JD were sparse.'
                    : 'Lý do: JD có quá ít yêu cầu kỹ thuật được nêu rõ.');
            } else {
                lines.push((isEnglish(lang) ? 'Reason: ' : 'Lý do: ') + tReasonCode(reasonKey, lang));
            }
        }
        if (value.usable_explicit_technical_count != null) {
            lines.push((isEnglish(lang) ? 'Usable explicit technical lines: ' : 'Số dòng yêu cầu kỹ thuật dùng được: ')
                + value.usable_explicit_technical_count);
        }
        if (value.technical_responsibility_candidate_count != null) {
            lines.push((isEnglish(lang) ? 'Technical responsibility candidates: ' : 'Ứng viên trách nhiệm kỹ thuật: ')
                + value.technical_responsibility_candidate_count);
        }
        if (value.supported_high_specificity_signal_count != null) {
            lines.push((isEnglish(lang) ? 'High-specificity signals: ' : 'Tín hiệu đặc thù cao: ')
                + value.supported_high_specificity_signal_count);
        }

        return lines.join(' ');
    }

    function tText(text, lang, context) {
        if (text == null) {
            return '';
        }
        text = String(text).trim();
        if (text === '' || isEnglish(lang)) {
            return text;
        }
        context = context || {};

        if (PHRASES[text]) {
            return finalizeViText(PHRASES[text], lang);
        }
        var lower = text.toLowerCase();
        var keys = Object.keys(PHRASES);
        for (var i = 0; i < keys.length; i++) {
            if (keys[i].toLowerCase() === lower) {
                return finalizeViText(PHRASES[keys[i]], lang);
            }
        }

        var m;
        if ((m = text.match(/^Missing must-have skills:\s*(.+)$/i))) {
            return finalizeViText('Thiếu kỹ năng bắt buộc: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Optional nice-to-have gaps:\s*(.+)$/i))) {
            return finalizeViText('Các kỹ năng cộng thêm còn thiếu: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^This role is a Strong Fit because (.+)$/i))) {
            return finalizeViText('Vị trí này rất phù hợp vì ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^This role is a Good Fit because (.+)$/i))) {
            return finalizeViText('Vị trí này phù hợp tốt vì ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^This role is currently a Stretch because (.+)$/i))) {
            return finalizeViText('Vị trí này hiện chỉ phù hợp một phần vì ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^This role is currently a Low Fit because (.+)$/i))) {
            return finalizeViText('Vị trí này hiện chưa phù hợp cao vì ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^This role is currently a Potential Fit because (.+)$/i))) {
            return finalizeViText('Vị trí này hiện có tiềm năng phù hợp vì ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Can you explain how you used (.+?) in this work example:\s*(.+)$/i))) {
            return finalizeViText('Bạn có thể giải thích cách bạn sử dụng ' + m[1] + ' trong ví dụ công việc này: ' + tText(m[2], lang, context), lang);
        }
        if ((m = text.match(/^How would you handle (.+?) in this role\?$/i))) {
            return finalizeViText('Bạn sẽ xử lý ' + tText(m[1], lang, context) + ' như thế nào trong vai trò này?', lang);
        }
        if ((m = text.match(/^How would you handle (.+)\?$/i))) {
            return finalizeViText('Bạn sẽ xử lý ' + tText(m[1], lang, context) + ' như thế nào?', lang);
        }
        if ((m = text.match(/^How did you apply (.+?) in practice\?\s*Example:\s*(.+)$/i))) {
            return finalizeViText('Bạn đã áp dụng ' + m[1] + ' như thế nào trong thực tế? Ví dụ: ' + m[2], lang);
        }
        if ((m = text.match(/^(.+?)\s*\(level\s*(\d+),\s*([^)]+)\)\s*:\s*(.+)$/i))) {
            return finalizeViText(m[1].trim() + ' (mức ' + m[2] + ', ' + tEnum('evidence_source', m[3].trim(), lang) + '): ' + tText(m[4], lang, context), lang);
        }
        if ((m = text.match(/^Do you have production experience with (.+)\?$/i))) {
            return finalizeViText('Bạn có kinh nghiệm production với ' + m[1] + ' không?', lang);
        }
        if ((m = text.match(/^Clear evidence for (.+)\.$/i))) {
            return finalizeViText('Có bằng chứng rõ cho ' + m[1] + '.', lang);
        }
        if ((m = text.match(/^Education requirements should be reviewed separately:\s*(.+)$/i))) {
            return finalizeViText('Yêu cầu học vấn cần xem xét riêng: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Soft skills should be verified during interview:\s*(.+)$/i))) {
            return finalizeViText('Kỹ năng mềm cần xác minh khi phỏng vấn: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Language requirements should be reviewed separately:\s*(.+)$/i))) {
            return finalizeViText('Yêu cầu ngoại ngữ cần xem xét riêng: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Domain context to consider:\s*(.+)$/i))) {
            return finalizeViText('Bối cảnh domain cần cân nhắc: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Hard-skill gate applied:\s*(.+)$/i))) {
            return finalizeViText('Đã áp dụng cổng kỹ năng cứng: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Source-aware calibration adjusted the score:\s*(.+)$/i))) {
            return finalizeViText('Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm: ' + tText(m[1], lang, context), lang);
        }
        if ((m = text.match(/^Source-aware calibration adjusted the score from (\d+)\/100 to (\d+)\/100 because (.+)$/i))) {
            return finalizeViText('Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm từ ' + m[1] + '/100 lên ' + m[2] + '/100 vì ' + tText(m[3], lang, context), lang);
        }
        if ((m = text.match(/^(.+?) is a (.+?) candidate for (.+?) with a final score of (\d+)\/100\.\s*(.+)$/is))) {
            var tail = tText(m[5].trim(), lang, context);
            return finalizeViText(m[1] + ' là ứng viên ' + tEnum('recommendation', m[2], lang)
                + ' cho vị trí ' + m[3] + ' với điểm cuối ' + m[4] + '/100.'
                + (tail ? ' ' + tail : ''), lang);
        }
        if ((m = text.match(/^(.+?) is a (.+?) candidate for (.+?) with a final score of (\d+)\/100$/i))) {
            return finalizeViText(m[1] + ' là ứng viên ' + tEnum('recommendation', m[2], lang)
                + ' cho vị trí ' + m[3] + ' với điểm cuối ' + m[4] + '/100.', lang);
        }
        if (text.indexOf('Source-aware calibration adjusted the score from') !== -1) {
            return tText(text.replace(
                /Source-aware calibration adjusted the score from (\d+)\/100 to (\d+)\/100 because (.+)/i,
                function (_all, from, to, because) {
                    return 'Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm từ ' + from + '/100 lên ' + to + '/100 vì ' + because;
                }
            ), lang, context);
        }
        if ((m = text.match(/^This sparse JD relies on promoted technical signals, and the promoted core requirements are strongly confirmed in the CV\.?$/i))) {
            return finalizeViText('JD thiếu chi tiết nên AI dựa vào tín hiệu kỹ thuật được promote; các yêu cầu cốt lõi được promote đã có bằng chứng mạnh trong CV.', lang);
        }

        return finalizeViText(text, lang);
    }

    function tList(items, lang, context) {
        if (!Array.isArray(items)) {
            return [];
        }
        return items.map(function (item) {
            if (typeof item === 'string' || typeof item === 'number') {
                return tText(String(item), lang, context);
            }
            if (item && typeof item === 'object') {
                var label = item.skill || item.requirement || item.name || item.label || '';
                var detail = item.reason || item.detail || item.note || '';
                if (detail) {
                    return String(label) + ' — ' + tText(String(detail), lang, context);
                }
                if (label) {
                    return String(label);
                }
                try {
                    return JSON.stringify(item);
                } catch (e) {
                    return '';
                }
            }
            return '';
        }).filter(function (line) { return line !== ''; });
    }

    function translateReviewCard(card, lang) {
        card = card || {};
        if (isEnglish(lang)) {
            return card;
        }
        var out = Object.assign({}, card);
        out.summary = tText(card.summary || '', lang);
        ['strengths', 'concerns', 'requirement_notes', 'suggested_interview_questions'].forEach(function (key) {
            out[key] = tList(card[key] || [], lang, { field: key });
        });
        out.evidence_highlights = tList(card.evidence_highlights || [], lang, { field: 'evidence' });
        if (card.role_alignment_impact && typeof card.role_alignment_impact === 'object') {
            out.role_alignment_impact = Object.assign({}, card.role_alignment_impact);
            if (card.role_alignment_impact.reason) {
                out.role_alignment_impact.reason = tText(card.role_alignment_impact.reason, lang);
            }
        }
        return out;
    }

    function translateCandidateJob(job, lang) {
        job = job || {};
        if (isEnglish(lang)) {
            return job;
        }
        var out = Object.assign({}, job);
        out.fit_label = tEnum('fit_label', job.fit_label, lang);
        out.fit_summary = tText(job.fit_summary || '', lang);
        ['why_fit', 'next_best_actions', 'cv_improvement_suggestions', 'matched_must_have_skills'].forEach(function (key) {
            out[key] = tList(job[key] || [], lang);
        });
        var gaps = job.skill_gaps || {};
        out.skill_gaps = {};
        Object.keys(gaps).forEach(function (gapKey) {
            out.skill_gaps[gapKey] = Array.isArray(gaps[gapKey]) ? tList(gaps[gapKey], lang, { gap: gapKey }) : gaps[gapKey];
        });
        if (job.review_card && typeof job.review_card === 'object') {
            out.review_card = translateReviewCard(job.review_card, lang);
        }
        if (job.job_quality && typeof job.job_quality === 'object') {
            out.job_quality = Object.assign({}, job.job_quality);
            if (job.job_quality.quality_label) {
                out.job_quality.quality_label = tText(job.job_quality.quality_label, lang);
            }
            if (Array.isArray(job.job_quality.reasons)) {
                out.job_quality.reasons = tList(job.job_quality.reasons, lang);
            }
        }
        if (job.role_alignment_impact && typeof job.role_alignment_impact === 'object') {
            out.role_alignment_impact = Object.assign({}, job.role_alignment_impact);
            if (job.role_alignment_impact.reason) {
                out.role_alignment_impact.reason = tText(job.role_alignment_impact.reason, lang);
            }
        }
        return out;
    }

    function escapeHtml(text) {
        if (text == null) {
            return '';
        }
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function listHtml(items, emptyText) {
        if (!Array.isArray(items) || items.length === 0) {
            return '<p class="text-muted small mb-0">' + escapeHtml(emptyText) + '</p>';
        }
        return '<ul class="mb-0 ps-3">' + items.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul>';
    }

    function gapListHtml(gaps, key, lang) {
        var items = gaps && gaps[key] ? gaps[key] : [];
        if (!Array.isArray(items) || items.length === 0) {
            return '<p class="text-muted small mb-0">' + escapeHtml(tLabel('no_gap_items', lang)) + '</p>';
        }
        return '<ul class="mb-0 ps-3">' + items.map(function (item) {
            if (typeof item === 'string') {
                return '<li>' + escapeHtml(item) + '</li>';
            }
            if (item && typeof item === 'object') {
                var label = item.skill || item.requirement || item.name || item.label || '';
                var detail = item.reason || item.detail || item.note || '';
                var line = escapeHtml(String(label));
                if (detail) {
                    line += ' — <span class="text-muted">' + escapeHtml(tText(String(detail), lang)) + '</span>';
                }
                return '<li>' + line + '</li>';
            }
            return '<li>' + escapeHtml(String(item)) + '</li>';
        }).join('') + '</ul>';
    }

    function formatRatio(value) {
        if (value == null || value === '') {
            return null;
        }
        var num = Number(value);
        if (Number.isNaN(num)) {
            return String(value);
        }
        if (num >= 0 && num <= 1) {
            return Math.round(num * 100) + '%';
        }
        return String(num);
    }

    function yesNo(value, lang) {
        return value ? tLabel('yes', lang) : tLabel('no', lang);
    }

    function confidenceLevelLabel(level, lang) {
        if (!level) {
            return '';
        }
        return tEnum('confidence_level', String(level).toLowerCase(), lang);
    }

    function renderLangToggleHtml(lang, toggleId) {
        lang = normalizeLang(lang);
        return '<div class="btn-group btn-group-sm ai-lang-toggle" role="group" id="' + escapeHtml(toggleId) + '" data-ai-lang-toggle>'
            + '<button type="button" class="btn btn-outline-secondary' + (lang === 'en' ? ' active' : '') + '" data-lang="en">English</button>'
            + '<button type="button" class="btn btn-outline-secondary' + (lang === 'vi' ? ' active' : '') + '" data-lang="vi">Tiếng Việt</button>'
            + '</div>';
    }

    function bindLangToggle(toggleId, onChange) {
        var el = document.getElementById(toggleId);
        if (!el) {
            return;
        }
        el.querySelectorAll('[data-lang]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var lang = normalizeLang(btn.getAttribute('data-lang'));
                setLang(lang);
                el.querySelectorAll('[data-lang]').forEach(function (b) {
                    b.classList.toggle('active', b.getAttribute('data-lang') === lang);
                });
                if (typeof onChange === 'function') {
                    onChange(lang);
                }
            });
        });
    }

    function updateLangToggle(toggleId, lang) {
        var el = document.getElementById(toggleId);
        if (!el) {
            return;
        }
        lang = normalizeLang(lang);
        el.querySelectorAll('[data-lang]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
        });
    }

    function screeningConfidenceHtml(confidence, lang) {
        if (!confidence || typeof confidence !== 'object') {
            return '';
        }
        var html = '';
        if (confidence.level) {
            html += '<div><strong>' + escapeHtml(tLabel('screening_confidence_level', lang)) + ':</strong> '
                + escapeHtml(confidenceLevelLabel(confidence.level, lang) || confidence.level) + '</div>';
        }
        if (confidence.known_requirement_count != null) {
            html += '<div><strong>' + escapeHtml(tLabel('known_requirements', lang)) + ':</strong> '
                + escapeHtml(confidence.known_requirement_count) + '</div>';
        }
        if (confidence.open_set_requirement_count != null) {
            html += '<div><strong>' + escapeHtml(tLabel('open_set_requirements', lang)) + ':</strong> '
                + escapeHtml(confidence.open_set_requirement_count) + '</div>';
        }
        if (confidence.embedding_enabled != null) {
            html += '<div><strong>' + escapeHtml(tLabel('embedding_enabled', lang)) + ':</strong> '
                + escapeHtml(yesNo(confidence.embedding_enabled, lang)) + '</div>';
        }
        if (Array.isArray(confidence.warnings) && confidence.warnings.length > 0) {
            html += '<div class="mt-1"><strong>' + escapeHtml(tLabel('warnings', lang)) + ':</strong></div>';
            html += listHtml(tList(confidence.warnings, lang), '');
        }
        return html;
    }

    function scoreFlowHtml(card, finalScore, lang) {
        var rawBase = card.raw_base_score;
        var roleCalibrated = card.role_calibrated_score;
        var adjustment = card.role_score_adjustment;
        var resolvedFinal = finalScore != null ? finalScore : card.final_score;
        var impact = card.role_alignment_impact || {};
        var hasScoreFlow = rawBase != null || roleCalibrated != null || resolvedFinal != null || adjustment != null;
        if (!hasScoreFlow) {
            return '';
        }
        var html = '<section class="mb-4"><h6 class="fw-bold text-dark">' + escapeHtml(tLabel('score_flow', lang)) + '</h6><div class="small">';
        if (rawBase != null) {
            html += '<div><strong>' + escapeHtml(tLabel('weighted_score', lang)) + ':</strong> ' + escapeHtml(rawBase) + '</div>';
        }
        if (roleCalibrated != null) {
            html += '<div><strong>' + escapeHtml(tLabel('role_calibrated_score', lang)) + ':</strong> ' + escapeHtml(roleCalibrated) + '</div>';
        }
        if (resolvedFinal != null) {
            html += '<div><strong>' + escapeHtml(tLabel('final_score', lang)) + ':</strong> ' + escapeHtml(resolvedFinal) + '</div>';
        }
        if (adjustment != null) {
            var adjText = Number(adjustment) > 0 ? '+' + adjustment : String(adjustment);
            html += '<div><strong>' + escapeHtml(tLabel('role_score_adjustment', lang)) + ':</strong> ' + escapeHtml(adjText) + '</div>';
        }
        if (impact.applied === true && impact.reason) {
            html += '<div class="mt-2 text-muted"><strong>' + escapeHtml(tLabel('role_adjustment_reason', lang)) + ':</strong> '
                + escapeHtml(tText(impact.reason, lang)) + '</div>';
        }
        html += '</div></section>';
        return html;
    }

    function coreRequirementFitHtml(summary, lang) {
        var core = summary && summary.core;
        if (!core || typeof core !== 'object') {
            return '';
        }
        var html = '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('core_requirement_fit', lang)) + '</h6><div class="small">';
        if (core.total != null) {
            html += '<div><strong>' + escapeHtml(tLabel('core_requirements_counted', lang)) + ':</strong> ' + escapeHtml(core.total) + '</div>';
        }
        var positive = formatRatio(core.positive_coverage);
        if (positive != null) {
            html += '<div><strong>' + escapeHtml(tLabel('positive_coverage', lang)) + ':</strong> ' + escapeHtml(positive) + '</div>';
        }
        var confirmed = formatRatio(core.confirmed_coverage);
        if (confirmed != null) {
            html += '<div><strong>' + escapeHtml(tLabel('confirmed_coverage', lang)) + ':</strong> ' + escapeHtml(confirmed) + '</div>';
        }
        var semanticOnly = formatRatio(core.semantic_only_ratio);
        if (semanticOnly != null) {
            html += '<div><strong>' + escapeHtml(tLabel('semantic_only_ratio', lang)) + ':</strong> ' + escapeHtml(semanticOnly) + '</div>';
        }
        html += '</div></section>';
        return html;
    }

    function coreRequirementFitCompactHtml(summary, lang) {
        var core = summary && summary.core;
        if (!core || typeof core !== 'object') {
            return '';
        }
        var html = '<div class="border rounded-3 bg-light p-3 mb-3 small">';
        html += '<div class="fw-bold mb-2">' + escapeHtml(tLabel('core_requirement_fit', lang)) + '</div>';
        if (core.total != null) {
            html += '<div><strong>' + escapeHtml(tLabel('core_requirements_counted', lang)) + ':</strong> ' + escapeHtml(core.total) + '</div>';
        }
        var confirmed = formatRatio(core.confirmed_coverage);
        if (confirmed != null) {
            html += '<div><strong>' + escapeHtml(tLabel('confirmed_coverage', lang)) + ':</strong> ' + escapeHtml(confirmed) + '</div>';
        }
        var semanticOnly = formatRatio(core.semantic_only_ratio);
        if (semanticOnly != null) {
            html += '<div><strong>' + escapeHtml(tLabel('semantic_only_ratio', lang)) + ':</strong> ' + escapeHtml(semanticOnly) + '</div>';
        }
        html += '</div>';
        return html;
    }

    function decisionConfidenceHtml(confidence, lang) {
        if (!confidence || typeof confidence !== 'object') {
            return '';
        }
        var html = '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('decision_confidence', lang)) + '</h6><div class="small">';
        if (confidence.level) {
            html += '<div><strong>' + escapeHtml(tLabel('level', lang)) + ':</strong> '
                + escapeHtml(confidenceLevelLabel(confidence.level, lang) || confidence.level) + '</div>';
        }
        if (confidence.review_required != null) {
            html += '<div><strong>' + escapeHtml(tLabel('review_required', lang)) + ':</strong> '
                + escapeHtml(yesNo(confidence.review_required, lang)) + '</div>';
        }
        if (Array.isArray(confidence.reason_codes) && confidence.reason_codes.length > 0) {
            html += '<div class="mt-1"><strong>' + escapeHtml(tLabel('reason_codes', lang)) + ':</strong></div>';
            html += listHtml(confidence.reason_codes.map(function (c) { return tReasonCode(c, lang); }), '');
        }
        html += '</div></section>';
        return html;
    }

    function decisionConfidenceAlertHtml(confidence, lang) {
        if (!confidence || typeof confidence !== 'object') {
            return '';
        }
        var html = '<div class="alert alert-light border small mb-3">';
        html += '<div class="fw-bold mb-1">' + escapeHtml(tLabel('decision_confidence', lang)) + '</div>';
        if (confidence.level) {
            html += '<div><strong>' + escapeHtml(tLabel('level', lang)) + ':</strong> '
                + escapeHtml(confidenceLevelLabel(confidence.level, lang) || confidence.level) + '</div>';
        }
        if (confidence.review_required != null) {
            html += '<div><strong>' + escapeHtml(tLabel('review_required', lang)) + ':</strong> '
                + escapeHtml(yesNo(confidence.review_required, lang)) + '</div>';
        }
        if (Array.isArray(confidence.reason_codes) && confidence.reason_codes.length > 0) {
            html += '<div class="mt-1"><strong>' + escapeHtml(tLabel('reason_codes', lang)) + ':</strong></div>';
            html += listHtml(confidence.reason_codes.map(function (c) { return tReasonCode(c, lang); }), '');
        }
        html += '</div>';
        return html;
    }

    function jobGuardrailsHtml(guardrails, lang) {
        if (!guardrails || typeof guardrails !== 'object' || Object.keys(guardrails).length === 0) {
            return '';
        }
        var html = '<div class="alert alert-light border small mb-3">';
        html += '<div class="fw-bold mb-1">' + escapeHtml(tLabel('job_confidence_guardrails', lang)) + '</div>';
        if (guardrails.level) {
            html += '<div><strong>' + escapeHtml(tLabel('level', lang)) + ':</strong> '
                + escapeHtml(confidenceLevelLabel(guardrails.level, lang) || guardrails.level) + '</div>';
        }
        if (guardrails.review_required != null) {
            html += '<div><strong>' + escapeHtml(tLabel('review_required', lang)) + ':</strong> '
                + escapeHtml(yesNo(guardrails.review_required, lang)) + '</div>';
        }
        if (Array.isArray(guardrails.reason_codes) && guardrails.reason_codes.length > 0) {
            html += '<div class="mt-1"><strong>' + escapeHtml(tLabel('reason_codes', lang)) + ':</strong></div>';
            html += listHtml(guardrails.reason_codes.map(function (c) { return tReasonCode(c, lang); }), '');
        }
        html += '</div>';
        return html;
    }

    function candidateJobApiFromJob(job) {
        job = job || {};
        return {
            confidence_guardrails: job.job_confidence_guardrails || job.confidence_guardrails || null,
            explicit_technical_recovery_summary: job.explicit_technical_recovery_summary || null,
            promoted_requirements: job.promoted_requirements || null,
            technical_responsibility_candidates: job.technical_responsibility_candidates || null,
            open_set_requirements: job.open_set_requirements || null,
        };
    }

    function hasConfidenceFooterContent(job, decisionConfidence) {
        var jobApi = candidateJobApiFromJob(job);
        if (decisionConfidence && typeof decisionConfidence === 'object' && Object.keys(decisionConfidence).length > 0) {
            return true;
        }
        var guardrails = jobApi.confidence_guardrails;
        if (guardrails && typeof guardrails === 'object' && Object.keys(guardrails).length > 0) {
            return true;
        }
        if (jobApi.explicit_technical_recovery_summary) {
            return true;
        }
        if (Array.isArray(jobApi.promoted_requirements) && jobApi.promoted_requirements.length > 0) {
            return true;
        }
        if (Array.isArray(jobApi.technical_responsibility_candidates) && jobApi.technical_responsibility_candidates.length > 0) {
            return true;
        }
        if (Array.isArray(jobApi.open_set_requirements) && jobApi.open_set_requirements.length > 0) {
            return true;
        }
        return false;
    }

    function stringifyRecoverySummary(value) {
        return humanizeRecoverySummary(value, 'en');
    }

    function jobApiPhase33Html(jobApi, lang) {
        if (!jobApi || typeof jobApi !== 'object') {
            return '';
        }
        var html = '';
        var guardrails = jobApi.confidence_guardrails || {};
        if (guardrails && typeof guardrails === 'object' && Object.keys(guardrails).length > 0) {
            html += '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('job_confidence_guardrails', lang)) + '</h6><div class="small">';
            if (guardrails.level) {
                html += '<div><strong>' + escapeHtml(tLabel('level', lang)) + ':</strong> '
                    + escapeHtml(confidenceLevelLabel(guardrails.level, lang) || guardrails.level) + '</div>';
            }
            if (guardrails.review_required != null) {
                html += '<div><strong>' + escapeHtml(tLabel('review_required', lang)) + ':</strong> '
                    + escapeHtml(yesNo(guardrails.review_required, lang)) + '</div>';
            }
            if (Array.isArray(guardrails.reason_codes) && guardrails.reason_codes.length > 0) {
                html += '<div class="mt-1"><strong>' + escapeHtml(tLabel('reason_codes', lang)) + ':</strong></div>';
                html += listHtml(guardrails.reason_codes.map(function (c) { return tReasonCode(c, lang); }), '');
            }
            html += '</div></section>';
        }
        var recovery = humanizeRecoverySummary(jobApi.explicit_technical_recovery_summary, lang);
        if (recovery) {
            html += '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('technical_recovery_summary', lang)) + '</h6>';
            html += '<p class="small mb-0">' + escapeHtml(recovery) + '</p></section>';
        }
        if (Array.isArray(jobApi.promoted_requirements) && jobApi.promoted_requirements.length > 0) {
            html += '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('promoted_requirements', lang)) + '</h6>';
            html += listHtml(jobApi.promoted_requirements.map(function (item) {
                return formatRequirementItem(item);
            }).filter(Boolean), '') + '</section>';
        }
        if (Array.isArray(jobApi.technical_responsibility_candidates) && jobApi.technical_responsibility_candidates.length > 0) {
            html += '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('technical_responsibility_candidates', lang)) + '</h6>';
            html += listHtml(jobApi.technical_responsibility_candidates.map(formatRequirementItem).filter(Boolean), '') + '</section>';
        }
        if (Array.isArray(jobApi.open_set_requirements) && jobApi.open_set_requirements.length > 0) {
            html += '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('open_set_requirements', lang)) + '</h6>';
            html += listHtml(jobApi.open_set_requirements.map(formatRequirementItem).filter(Boolean), '') + '</section>';
        }
        return html;
    }

    function roleAdjustmentAlertHtml(job, lang) {
        var adjustment = job.role_score_adjustment;
        var impact = job.role_alignment_impact || {};
        var reason = impact.reason || '';
        var hasAdjustment = adjustment != null && Number(adjustment) !== 0;
        if (!hasAdjustment && !reason) {
            return '';
        }
        var html = '<div class="alert alert-info border-0 small py-2 px-3 mb-3">';
        html += '<div class="fw-bold mb-1">' + escapeHtml(tLabel('role_adjustment', lang)) + '</div>';
        if (reason) {
            html += '<div>' + escapeHtml(tText(reason, lang)) + '</div>';
        } else if (hasAdjustment) {
            html += '<div>' + escapeHtml(tLabel('role_score_adjustment', lang)) + ': ' + escapeHtml(adjustment) + '</div>';
        }
        html += '</div>';
        return html;
    }

    function buildEmployerModalBody(payload, lang) {
        payload = payload || {};
        var card = translateReviewCard(payload.card || {}, lang);
        var diagnostics = payload.diagnostics || {};
        var html = '';

        html += '<section class="mb-4"><h6 class="fw-bold text-success">' + escapeHtml(tLabel('summary', lang)) + '</h6>';
        html += '<p class="mb-0">' + escapeHtml(card.summary || tLabel('no_summary', lang)) + '</p></section>';
        html += '<section class="mb-4"><h6 class="fw-bold text-success">' + escapeHtml(tLabel('strengths', lang)) + '</h6>';
        html += listHtml(card.strengths || [], tLabel('no_strengths', lang)) + '</section>';
        html += '<section class="mb-4"><h6 class="fw-bold text-warning">' + escapeHtml(tLabel('concerns', lang)) + '</h6>';
        html += listHtml(card.concerns || [], tLabel('no_concerns', lang)) + '</section>';
        if (Array.isArray(card.requirement_notes) && card.requirement_notes.length > 0) {
            html += '<section class="mb-4"><h6 class="fw-bold text-secondary">' + escapeHtml(tLabel('requirement_notes', lang)) + '</h6>';
            html += listHtml(card.requirement_notes, '') + '</section>';
        }
        html += '<section class="mb-4"><h6 class="fw-bold text-primary">' + escapeHtml(tLabel('evidence_highlights', lang)) + '</h6>';
        html += listHtml(card.evidence_highlights || [], tLabel('no_evidence', lang)) + '</section>';
        html += '<section class="mb-4"><h6 class="fw-bold text-info">' + escapeHtml(tLabel('suggested_interview_questions', lang)) + '</h6>';
        html += listHtml(card.suggested_interview_questions || [], tLabel('no_questions', lang)) + '</section>';
        html += scoreFlowHtml(card, payload.score, lang);
        html += coreRequirementFitHtml(card.core_requirement_fit_summary || {}, lang);

        var hasConfidenceFooter = (payload.decision_confidence && Object.keys(payload.decision_confidence).length > 0)
            || jobApiPhase33Html(payload.job_api || {}, lang) !== '';
        if (hasConfidenceFooter) {
            html += '<hr class="my-4">';
            html += '<details class="mb-3"><summary class="fw-bold text-secondary small">'
                + escapeHtml(tLabel('confidence_technical_footer', lang)) + '</summary>';
            html += '<div class="mt-3">';
            html += decisionConfidenceHtml(payload.decision_confidence || null, lang);
            html += jobApiPhase33Html(payload.job_api || {}, lang);
            html += '</div></details>';
        }

        var screeningConfidence = diagnostics.screening_confidence || {};
        var hasDiag = diagnostics && (
            diagnostics.trace_id ||
            diagnostics.run_id ||
            (Array.isArray(diagnostics.job_flags) && diagnostics.job_flags.length > 0) ||
            diagnostics.candidate_flagged_count > 0 ||
            screeningConfidence.level ||
            (Array.isArray(screeningConfidence.warnings) && screeningConfidence.warnings.length > 0)
        );
        if (hasDiag) {
            html += '<hr class="my-4">';
            html += '<details><summary class="fw-bold text-muted">' + escapeHtml(tLabel('ai_diagnostics', lang)) + '</summary>';
            html += '<div class="small text-muted mt-2">';
            if (diagnostics.trace_id) {
                html += '<div><strong>' + escapeHtml(tLabel('trace_id', lang)) + ':</strong> ' + escapeHtml(diagnostics.trace_id) + '</div>';
            }
            if (diagnostics.run_id) {
                html += '<div><strong>' + escapeHtml(tLabel('run_id', lang)) + ':</strong> ' + escapeHtml(diagnostics.run_id) + '</div>';
            }
            if (Array.isArray(diagnostics.job_flags) && diagnostics.job_flags.length > 0) {
                html += '<div><strong>' + escapeHtml(tLabel('job_payload_flags', lang)) + ':</strong> ' + escapeHtml(diagnostics.job_flags.join(', ')) + '</div>';
            }
            if (diagnostics.candidate_flagged_count > 0) {
                html += '<div><strong>' + escapeHtml(tLabel('candidate_flagged_count', lang)) + ':</strong> ' + escapeHtml(diagnostics.candidate_flagged_count) + '</div>';
            }
            if (diagnostics.job_quality && diagnostics.job_quality.quality_label) {
                html += '<div><strong>' + escapeHtml(tLabel('job_quality_label', lang)) + ':</strong> ' + escapeHtml(diagnostics.job_quality.quality_label) + '</div>';
            }
            if (diagnostics.job_quality && Array.isArray(diagnostics.job_quality.reasons) && diagnostics.job_quality.reasons.length > 0) {
                html += '<div class="mt-1"><strong>' + escapeHtml(tLabel('job_quality_reasons', lang)) + ':</strong></div>';
                html += listHtml(tList(diagnostics.job_quality.reasons, lang), '');
            }
            html += screeningConfidenceHtml(screeningConfidence, lang);
            html += '</div></details>';
        }

        return html;
    }

    function buildCandidateModalBody(job, lang) {
        var displayJob = translateCandidateJob(job, lang);
        var gaps = job.skill_gaps || {};
        var reviewCard = displayJob.review_card || {};
        var evidence = reviewCard.evidence_highlights || displayJob.matched_must_have_skills || [];
        var html = '<div class="accordion" id="recDetailAccordion">';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#recWhyFit">'
            + escapeHtml(tLabel('why_fit', lang)) + '</button></h2>';
        html += '<div id="recWhyFit" class="accordion-collapse collapse show" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += '<p>' + escapeHtml(displayJob.fit_summary || '') + '</p>';
        html += roleAdjustmentAlertHtml(job, lang);
        html += listHtml(displayJob.why_fit || [], tLabel('no_reasons', lang));
        html += '</div></div></div>';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recGaps">'
            + escapeHtml(tLabel('gaps', lang)) + '</button></h2>';
        html += '<div id="recGaps" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += coreRequirementFitCompactHtml(job.core_requirement_fit_summary || {}, lang);
        var s = job.skill_gap_summary || {};
        var gapLine = [];
        if (s.missing_must_have_count > 0) {
            gapLine.push(tLabel('missing_must_have_count', lang) + ': ' + s.missing_must_have_count);
        }
        if (s.weak_evidence_count > 0) {
            gapLine.push(tLabel('weak_evidence_count', lang) + ': ' + s.weak_evidence_count);
        }
        if (s.optional_growth_count > 0) {
            gapLine.push(tLabel('optional_growth_count', lang) + ': ' + s.optional_growth_count);
        }
        html += '<p class="small text-muted">' + escapeHtml(gapLine.join(' · ') || tLabel('no_major_gaps', lang)) + '</p>';
        html += '<h6 class="fw-bold small">' + escapeHtml(tLabel('missing_must_have', lang)) + '</h6>' + gapListHtml(gaps, 'missing_must_have', lang);
        html += '<h6 class="fw-bold small mt-3">' + escapeHtml(tLabel('weak_evidence', lang)) + '</h6>' + gapListHtml(gaps, 'weak_evidence', lang);
        html += '<h6 class="fw-bold small mt-3">' + escapeHtml(tLabel('optional_growth', lang)) + '</h6>' + gapListHtml(gaps, 'optional_growth', lang);
        html += '</div></div></div>';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recImprove">'
            + escapeHtml(tLabel('improve_cv', lang)) + '</button></h2>';
        html += '<div id="recImprove" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += '<h6 class="fw-bold small">' + escapeHtml(tLabel('next_actions', lang)) + '</h6>' + listHtml(displayJob.next_best_actions || [], tLabel('no_suggestions', lang));
        html += '<h6 class="fw-bold small mt-3">' + escapeHtml(tLabel('cv_suggestions', lang)) + '</h6>' + listHtml(displayJob.cv_improvement_suggestions || [], tLabel('no_suggestions', lang));
        html += '</div></div></div>';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recEvidence">'
            + escapeHtml(tLabel('skill_evidence', lang)) + '</button></h2>';
        html += '<div id="recEvidence" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += '<h6 class="fw-bold small">' + escapeHtml(tLabel('matched_skills', lang)) + '</h6>';
        html += listHtml(displayJob.matched_must_have_skills || [], tLabel('no_matched_skills', lang));
        html += '<h6 class="fw-bold small mt-3">' + escapeHtml(tLabel('evidence_highlights', lang)) + '</h6>';
        html += listHtml(Array.isArray(evidence) ? evidence : [], tLabel('no_evidence', lang));
        html += '</div></div></div>';

        var requirementNotes = reviewCard.requirement_notes || [];
        if (Array.isArray(requirementNotes) && requirementNotes.length > 0) {
            html += '<div class="accordion-item"><h2 class="accordion-header">';
            html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recReqNotes">'
                + escapeHtml(tLabel('requirement_notes', lang)) + '</button></h2>';
            html += '<div id="recReqNotes" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
            html += listHtml(requirementNotes, '');
            html += '</div></div></div>';
        }

        var jq = displayJob.job_quality || {};
        if (jq.quality_label) {
            html += '<div class="accordion-item"><h2 class="accordion-header">';
            html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recJdQuality">'
                + escapeHtml(tLabel('jd_quality', lang)) + '</button></h2>';
            html += '<div id="recJdQuality" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
            html += '<p class="small mb-2"><strong>' + escapeHtml(tLabel('quality_score', lang)) + ':</strong> '
                + escapeHtml(jq.quality_score != null ? jq.quality_score : '—') + '</p>';
            html += '<p class="small mb-2"><strong>' + escapeHtml(tLabel('label', lang)) + ':</strong> '
                + escapeHtml(jq.quality_label || '—') + '</p>';
            if (Array.isArray(jq.reasons) && jq.reasons.length > 0) {
                html += '<h6 class="fw-bold small">' + escapeHtml(tLabel('reasons', lang)) + '</h6>' + listHtml(jq.reasons, '');
            }
            html += '</div></div></div>';
        }

        if (Array.isArray(job.open_set_requirements) && job.open_set_requirements.length > 0) {
            html += '<div class="accordion-item"><h2 class="accordion-header">';
            html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recOpenSet">'
                + escapeHtml(tLabel('open_set_requirements', lang)) + '</button></h2>';
            html += '<div id="recOpenSet" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
            html += listHtml(job.open_set_requirements.map(formatRequirementItem).filter(Boolean), '');
            html += '</div></div></div>';
        }

        html += '</div>';

        if (hasConfidenceFooterContent(job, job.decision_confidence)) {
            html += '<hr class="my-3">';
            html += '<details class="mb-2"><summary class="fw-bold text-secondary small">'
                + escapeHtml(tLabel('confidence_technical_footer', lang)) + '</summary>';
            html += '<div class="mt-3">';
            html += decisionConfidenceAlertHtml(job.decision_confidence || null, lang);
            html += jobApiPhase33Html(candidateJobApiFromJob(job), lang);
            html += '</div></details>';
        }

        return html;
    }

    function renderEmployerModal(payload, lang) {
        lang = normalizeLang(lang);
        payload = payload || employerRawPayload;
        if (!payload) {
            return;
        }
        employerRawPayload = payload;

        var metaParts = [];
        if (payload.rank != null) {
            metaParts.push(tLabel('rank', lang) + ' #' + payload.rank);
        }
        if (payload.score != null) {
            metaParts.push(tLabel('score', lang) + ' ' + payload.score);
        }
        if (payload.recommendation) {
            metaParts.push(tEnum('recommendation', payload.recommendation, lang));
        }

        var titleEl = document.getElementById('aiReviewModalTitle');
        var metaEl = document.getElementById('aiReviewModalMeta');
        var bodyEl = document.getElementById('aiReviewModalBody');
        if (titleEl) {
            titleEl.textContent = tLabel('ai_review_title', lang) + ' — ' + (payload.name || '');
        }
        if (metaEl) {
            metaEl.textContent = metaParts.join(' · ');
        }
        if (bodyEl) {
            bodyEl.innerHTML = buildEmployerModalBody(payload, lang);
        }
        updateLangToggle('aiReviewLangToggle', lang);
    }

    function renderCandidateModal(job, lang) {
        lang = normalizeLang(lang);
        job = job || candidateRawJob;
        if (!job) {
            return;
        }
        candidateRawJob = job;

        var titleEl = document.getElementById('recDetailTitle');
        var metaEl = document.getElementById('recDetailMeta');
        var bodyEl = document.getElementById('recDetailBody');
        if (titleEl) {
            titleEl.textContent = job.job_title || tLabel('rec_detail_title', lang);
        }
        if (metaEl) {
            var displayJob = translateCandidateJob(candidateRawJob, lang);
            var metaParts = [];
            if (displayJob.fit_label) {
                metaParts.push(displayJob.fit_label);
            }
            if (candidateRawJob.fit_score != null) {
                metaParts.push(candidateRawJob.fit_score + ' ' + tLabel('points', lang));
            }
            metaEl.textContent = metaParts.join(' · ');
        }
        if (bodyEl) {
            bodyEl.innerHTML = buildCandidateModalBody(candidateRawJob, lang);
        }
        updateLangToggle('recDetailLangToggle', lang);
    }

    function openEmployerModal(payload) {
        var lang = getLang();
        renderEmployerModal(payload, lang);
        var modalEl = document.getElementById('aiReviewModal');
        if (modalEl && global.bootstrap) {
            global.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function openCandidateModal(job) {
        var lang = getLang();
        renderCandidateModal(job, lang);
        var modalEl = document.getElementById('recDetailModal');
        if (modalEl && global.bootstrap) {
            global.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function init() {
        bindLangToggle('aiReviewLangToggle', function (lang) {
            renderEmployerModal(null, lang);
        });
        bindLangToggle('recDetailLangToggle', function (lang) {
            renderCandidateModal(null, lang);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    global.TopCvAiUiI18n = {
        getLang: getLang,
        setLang: setLang,
        isEnglish: isEnglish,
        tLabel: tLabel,
        tText: tText,
        tList: tList,
        translateReviewCard: translateReviewCard,
        translateCandidateJob: translateCandidateJob,
        renderLangToggleHtml: renderLangToggleHtml,
        renderEmployerModal: renderEmployerModal,
        renderCandidateModal: renderCandidateModal,
        openEmployerModal: openEmployerModal,
        openCandidateModal: openCandidateModal,
        escapeHtml: escapeHtml,
    };
})(window);
