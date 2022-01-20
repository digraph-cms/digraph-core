Digraph.CodeMirror = {
    config: {
        theme: 'digraph',
        lineWrapping: true,
        indentWithTabs: true,
        indentUnit: 4,
        extraKeys: {
            'Enter': 'newlineAndIndentContinueMarkdownList',
            'Ctrl-/': 'toggleComment'
        }
    },
    fromTextArea: function (textarea, config = {}) {
        return this.prep(CodeMirror.fromTextArea(
            textarea,
            Object.assign(this.config, config)
        ));
    },
    CodeMirror: function (t, config = {}) {
        return this.prep(CodeMirror(
            t,
            Object.assign(this.config, config)
        ));
    },
    prep: function (cm) {
        // currently does nothing, but could be used to add automatic event listeners
        return cm;
    }
};

document.addEventListener('DigraphDOMReady', (e) => {
    var divs = e.target.getElementsByTagName('div');
    for (let i = 0; i < divs.length; i++) {
        const div = divs[i];
        if (div.classList.contains('codemirror-input-wrapper') && !div.classList.contains('codemirror-input-wrapper--active')) {
            div.classList.add('codemirror-input-wrapper--active');
            if (div.dataset.codeMirrorConfig) {
                var config = JSON.parse(div.dataset.codeMirrorConfig);
            } else {
                var config = {};
            }
            if (div.dataset.codemirrorMode) {
                config.mode = div.dataset.codemirrorMode;
            }
            Digraph.CodeMirror.fromTextArea(
                div.getElementsByTagName('textarea')[0],
                config
            );
        }
    }
});

/**
 * Markdown editing commands adapted from:
 * 
 * SimpleMDE - https://simplemde.com/
 * 
 * The MIT License (MIT)
 * 
 * Copyright (c) 2015 Next Step Webs, Inc.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */

(() => {
    CodeMirror.commands.markdownToggleBold = function (cm) {
        _toggleBlock(cm, "bold", "**");
    };

    CodeMirror.commands.markdownToggleItalic = function (cm) {
        _toggleBlock(cm, "italic", "*");
    };

    CodeMirror.commands.markdownToggleStrikethrough = function (cm) {
        _toggleBlock(cm, "strikethrough", "~~");
    };

    CodeMirror.commands.markdownToggleBulletList = function (cm) {
        _toggleLine(cm, "unordered-list");
    }

    CodeMirror.commands.markdownToggleNumberedList = function (cm) {
        _toggleLine(cm, "ordered-list");
    }

    CodeMirror.commands.markdownToggleHeading = function (cm) {
        _toggleHeading(cm, "smaller");
    }

    CodeMirror.commands.markdownToggleHeadingReverse = function (cm) {
        _toggleHeading(cm, "bigger");
    }

    CodeMirror.commands.markdownToggleQuote = function (cm) {
        _toggleLine(cm, "quote");
    }

    CodeMirror.commands.markdownToggleCodeBlock = function (cm) {
        var fenceCharsToInsert = '```';

        function fencing_line(line) {
            /* return true, if this is a ``` or ~~~ line */
            if (typeof line !== "object") {
                throw "fencing_line() takes a 'line' object (not a line number, or line text).  Got: " + typeof line + ": " + line;
            }
            return line.styles && line.styles[2] && line.styles[2].indexOf("formatting-code-block") !== -1;
        }

        function token_state(token) {
            // base goes an extra level deep when mode backdrops are used, e.g. spellchecker on
            return token.state.base.base || token.state.base;
        }

        function code_type(cm, line_num, line, firstTok, lastTok) {
            /*
             * Return "single", "indented", "fenced" or false
             *
             * cm and line_num are required.  Others are optional for efficiency
             *   To check in the middle of a line, pass in firstTok yourself.
             */
            line = line || cm.getLineHandle(line_num);
            firstTok = firstTok || cm.getTokenAt({
                line: line_num,
                ch: 1
            });
            lastTok = lastTok || (!!line.text && cm.getTokenAt({
                line: line_num,
                ch: line.text.length - 1
            }));
            var types = firstTok.type ? firstTok.type.split(" ") : [];
            if (lastTok && token_state(lastTok).indentedCode) {
                // have to check last char, since first chars of first line aren"t marked as indented
                return "indented";
            } else if (types.indexOf("comment") === -1) {
                // has to be after "indented" check, since first chars of first indented line aren"t marked as such
                return false;
            } else if (token_state(firstTok).fencedChars || token_state(lastTok).fencedChars || fencing_line(line)) {
                return "fenced";
            } else {
                return "single";
            }
        }

        function insertFencingAtSelection(cm, cur_start, cur_end, fenceCharsToInsert) {
            var start_line_sel = cur_start.line + 1,
                end_line_sel = cur_end.line + 1,
                sel_multi = cur_start.line !== cur_end.line,
                repl_start = fenceCharsToInsert + "\n",
                repl_end = "\n" + fenceCharsToInsert;
            if (sel_multi) {
                end_line_sel++;
            }
            // handle last char including \n or not
            if (sel_multi && cur_end.ch === 0) {
                repl_end = fenceCharsToInsert + "\n";
                end_line_sel--;
            }
            _replaceSelection(cm, false, [repl_start, repl_end]);
            cm.setSelection({
                line: start_line_sel,
                ch: 0
            }, {
                line: end_line_sel,
                ch: 0
            });
        }

        cur_start = cm.getCursor("start"),
            cur_end = cm.getCursor("end"),
            tok = cm.getTokenAt({
                line: cur_start.line,
                ch: cur_start.ch || 1
            }), // avoid ch 0 which is a cursor pos but not token
            line = cm.getLineHandle(cur_start.line),
            is_code = code_type(cm, cur_start.line, line, tok);
        var block_start, block_end, lineCount;

        if (is_code === "single") {
            // similar to some SimpleMDE _toggleBlock logic
            var start = line.text.slice(0, cur_start.ch).replace("`", ""),
                end = line.text.slice(cur_start.ch).replace("`", "");
            cm.replaceRange(start + end, {
                line: cur_start.line,
                ch: 0
            }, {
                line: cur_start.line,
                ch: 99999999999999
            });
            cur_start.ch--;
            if (cur_start !== cur_end) {
                cur_end.ch--;
            }
            cm.setSelection(cur_start, cur_end);
            cm.focus();
        } else if (is_code === "fenced") {
            if (cur_start.line !== cur_end.line || cur_start.ch !== cur_end.ch) {
                // use selection

                // find the fenced line so we know what type it is (tilde, backticks, number of them)
                for (block_start = cur_start.line; block_start >= 0; block_start--) {
                    line = cm.getLineHandle(block_start);
                    if (fencing_line(line)) {
                        break;
                    }
                }
                var fencedTok = cm.getTokenAt({
                    line: block_start,
                    ch: 1
                });
                var fence_chars = token_state(fencedTok).fencedChars;
                var start_text, start_line;
                var end_text, end_line;
                // check for selection going up against fenced lines, in which case we don't want to add more fencing
                if (fencing_line(cm.getLineHandle(cur_start.line))) {
                    start_text = "";
                    start_line = cur_start.line;
                } else if (fencing_line(cm.getLineHandle(cur_start.line - 1))) {
                    start_text = "";
                    start_line = cur_start.line - 1;
                } else {
                    start_text = fence_chars + "\n";
                    start_line = cur_start.line;
                }
                if (fencing_line(cm.getLineHandle(cur_end.line))) {
                    end_text = "";
                    end_line = cur_end.line;
                    if (cur_end.ch === 0) {
                        end_line += 1;
                    }
                } else if (cur_end.ch !== 0 && fencing_line(cm.getLineHandle(cur_end.line + 1))) {
                    end_text = "";
                    end_line = cur_end.line + 1;
                } else {
                    end_text = fence_chars + "\n";
                    end_line = cur_end.line + 1;
                }
                if (cur_end.ch === 0) {
                    // full last line selected, putting cursor at beginning of next
                    end_line -= 1;
                }
                cm.operation(function () {
                    // end line first, so that line numbers don't change
                    cm.replaceRange(end_text, {
                        line: end_line,
                        ch: 0
                    }, {
                        line: end_line + (end_text ? 0 : 1),
                        ch: 0
                    });
                    cm.replaceRange(start_text, {
                        line: start_line,
                        ch: 0
                    }, {
                        line: start_line + (start_text ? 0 : 1),
                        ch: 0
                    });
                });
                cm.setSelection({
                    line: start_line + (start_text ? 1 : 0),
                    ch: 0
                }, {
                    line: end_line + (start_text ? 1 : -1),
                    ch: 0
                });
                cm.focus();
            } else {
                // no selection, search for ends of this fenced block
                var search_from = cur_start.line;
                if (fencing_line(cm.getLineHandle(cur_start.line))) { // gets a little tricky if cursor is right on a fenced line
                    if (code_type(cm, cur_start.line + 1) === "fenced") {
                        block_start = cur_start.line;
                        search_from = cur_start.line + 1; // for searching for "end"
                    } else {
                        block_end = cur_start.line;
                        search_from = cur_start.line - 1; // for searching for "start"
                    }
                }
                if (block_start === undefined) {
                    for (block_start = search_from; block_start >= 0; block_start--) {
                        line = cm.getLineHandle(block_start);
                        if (fencing_line(line)) {
                            break;
                        }
                    }
                }
                if (block_end === undefined) {
                    lineCount = cm.lineCount();
                    for (block_end = search_from; block_end < lineCount; block_end++) {
                        line = cm.getLineHandle(block_end);
                        if (fencing_line(line)) {
                            break;
                        }
                    }
                }
                cm.operation(function () {
                    cm.replaceRange("", {
                        line: block_start,
                        ch: 0
                    }, {
                        line: block_start + 1,
                        ch: 0
                    });
                    cm.replaceRange("", {
                        line: block_end - 1,
                        ch: 0
                    }, {
                        line: block_end,
                        ch: 0
                    });
                });
                cm.focus();
            }
        } else if (is_code === "indented") {
            if (cur_start.line !== cur_end.line || cur_start.ch !== cur_end.ch) {
                // use selection
                block_start = cur_start.line;
                block_end = cur_end.line;
                if (cur_end.ch === 0) {
                    block_end--;
                }
            } else {
                // no selection, search for ends of this indented block
                for (block_start = cur_start.line; block_start >= 0; block_start--) {
                    line = cm.getLineHandle(block_start);
                    if (line.text.match(/^\s*$/)) {
                        // empty or all whitespace - keep going
                        continue;
                    } else {
                        if (code_type(cm, block_start, line) !== "indented") {
                            block_start += 1;
                            break;
                        }
                    }
                }
                lineCount = cm.lineCount();
                for (block_end = cur_start.line; block_end < lineCount; block_end++) {
                    line = cm.getLineHandle(block_end);
                    if (line.text.match(/^\s*$/)) {
                        // empty or all whitespace - keep going
                        continue;
                    } else {
                        if (code_type(cm, block_end, line) !== "indented") {
                            block_end -= 1;
                            break;
                        }
                    }
                }
            }
            // if we are going to un-indent based on a selected set of lines, and the next line is indented too, we need to
            // insert a blank line so that the next line(s) continue to be indented code
            var next_line = cm.getLineHandle(block_end + 1),
                next_line_last_tok = next_line && cm.getTokenAt({
                    line: block_end + 1,
                    ch: next_line.text.length - 1
                }),
                next_line_indented = next_line_last_tok && token_state(next_line_last_tok).indentedCode;
            if (next_line_indented) {
                cm.replaceRange("\n", {
                    line: block_end + 1,
                    ch: 0
                });
            }

            for (var i = block_start; i <= block_end; i++) {
                cm.indentLine(i, "subtract"); // TODO: this doesn't get tracked in the history, so can't be undone :(
            }
            cm.focus();
        } else {
            // insert code formatting
            var no_sel_and_starting_of_line = (cur_start.line === cur_end.line && cur_start.ch === cur_end.ch && cur_start.ch === 0);
            var sel_multi = cur_start.line !== cur_end.line;
            if (no_sel_and_starting_of_line || sel_multi) {
                insertFencingAtSelection(cm, cur_start, cur_end, fenceCharsToInsert);
            } else {
                _replaceSelection(cm, false, ["`", "`"]);
            }
        }
    }

    function _toggleHeading(cm, direction, size) {
        var startPoint = cm.getCursor("start");
        var endPoint = cm.getCursor("end");
        for (var i = startPoint.line; i <= endPoint.line; i++) {
            (function (i) {
                var text = cm.getLine(i);
                var currHeadingLevel = text.search(/[^#]/);

                if (direction !== undefined) {
                    if (currHeadingLevel <= 0) {
                        if (direction == "bigger") {
                            text = "###### " + text;
                        } else {
                            text = "# " + text;
                        }
                    } else if (currHeadingLevel == 6 && direction == "smaller") {
                        text = text.substr(7);
                    } else if (currHeadingLevel == 1 && direction == "bigger") {
                        text = text.substr(2);
                    } else {
                        if (direction == "bigger") {
                            text = text.substr(1);
                        } else {
                            text = "#" + text;
                        }
                    }
                } else {
                    if (size == 1) {
                        if (currHeadingLevel <= 0) {
                            text = "# " + text;
                        } else if (currHeadingLevel == size) {
                            text = text.substr(currHeadingLevel + 1);
                        } else {
                            text = "# " + text.substr(currHeadingLevel + 1);
                        }
                    } else if (size == 2) {
                        if (currHeadingLevel <= 0) {
                            text = "## " + text;
                        } else if (currHeadingLevel == size) {
                            text = text.substr(currHeadingLevel + 1);
                        } else {
                            text = "## " + text.substr(currHeadingLevel + 1);
                        }
                    } else {
                        if (currHeadingLevel <= 0) {
                            text = "### " + text;
                        } else if (currHeadingLevel == size) {
                            text = text.substr(currHeadingLevel + 1);
                        } else {
                            text = "### " + text.substr(currHeadingLevel + 1);
                        }
                    }
                }

                cm.replaceRange(text, {
                    line: i,
                    ch: 0
                }, {
                    line: i,
                    ch: 99999999999999
                });
            })(i);
        }
        cm.focus();
    }

    function _toggleLine(cm, name) {
        var stat = getState(cm);
        var startPoint = cm.getCursor("start");
        var endPoint = cm.getCursor("end");
        var repl = {
            "quote": /^(\s*)\>\s+/,
            "unordered-list": /^(\s*)(\*|\-|\+)\s+/,
            "ordered-list": /^(\s*)\d+\.\s+/
        };
        var map = {
            "quote": "> ",
            "unordered-list": "* ",
            "ordered-list": "1. "
        };
        for (var i = startPoint.line; i <= endPoint.line; i++) {
            (function (i) {
                var text = cm.getLine(i);
                if (stat[name]) {
                    text = text.replace(repl[name], "$1");
                } else {
                    text = map[name] + text;
                }
                cm.replaceRange(text, {
                    line: i,
                    ch: 0
                }, {
                    line: i,
                    ch: 99999999999999
                });
            })(i);
        }
        cm.focus();
    }

    function _toggleBlock(cm, type, start_chars, end_chars) {
        end_chars = (typeof end_chars === "undefined") ? start_chars : end_chars;
        var stat = getState(cm);

        var text;
        var start = start_chars;
        var end = end_chars;

        var startPoint = cm.getCursor("start");
        var endPoint = cm.getCursor("end");

        if (stat[type]) {
            text = cm.getLine(startPoint.line);
            start = text.slice(0, startPoint.ch);
            end = text.slice(startPoint.ch);
            if (type == "bold") {
                start = start.replace(/(\*\*|__)(?![\s\S]*(\*\*|__))/, "");
                end = end.replace(/(\*\*|__)/, "");
            } else if (type == "italic") {
                start = start.replace(/(\*|_)(?![\s\S]*(\*|_))/, "");
                end = end.replace(/(\*|_)/, "");
            } else if (type == "strikethrough") {
                start = start.replace(/(\*\*|~~)(?![\s\S]*(\*\*|~~))/, "");
                end = end.replace(/(\*\*|~~)/, "");
            }
            cm.replaceRange(start + end, {
                line: startPoint.line,
                ch: 0
            }, {
                line: startPoint.line,
                ch: 99999999999999
            });

            if (type == "bold" || type == "strikethrough") {
                startPoint.ch -= 2;
                if (startPoint !== endPoint) {
                    endPoint.ch -= 2;
                }
            } else if (type == "italic") {
                startPoint.ch -= 1;
                if (startPoint !== endPoint) {
                    endPoint.ch -= 1;
                }
            }
        } else {
            text = cm.getSelection();
            if (type == "bold") {
                text = text.split("**").join("");
                text = text.split("__").join("");
            } else if (type == "italic") {
                text = text.split("*").join("");
                text = text.split("_").join("");
            } else if (type == "strikethrough") {
                text = text.split("~~").join("");
            }
            cm.replaceSelection(start + text + end);

            startPoint.ch += start_chars.length;
            endPoint.ch = startPoint.ch + text.length;
        }

        cm.setSelection(startPoint, endPoint);
        cm.focus();
    }

    function getState(cm, pos) {
        pos = pos || cm.getCursor("start");
        var stat = cm.getTokenAt(pos);
        if (!stat.type) return {};

        var types = stat.type.split(" ");

        var ret = {},
            data, text;
        for (var i = 0; i < types.length; i++) {
            data = types[i];
            if (data === "strong") {
                ret.bold = true;
            } else if (data === "variable-2") {
                text = cm.getLine(pos.line);
                if (/^\s*\d+\.\s/.test(text)) {
                    ret["ordered-list"] = true;
                } else {
                    ret["unordered-list"] = true;
                }
            } else if (data === "atom") {
                ret.quote = true;
            } else if (data === "em") {
                ret.italic = true;
            } else if (data === "quote") {
                ret.quote = true;
            } else if (data === "strikethrough") {
                ret.strikethrough = true;
            } else if (data === "comment") {
                ret.code = true;
            } else if (data === "link") {
                ret.link = true;
            } else if (data === "tag") {
                ret.image = true;
            } else if (data.match(/^header(\-[1-6])?$/)) {
                ret[data.replace("header", "heading")] = true;
            }
        }
        return ret;
    }

    function _replaceSelection(cm, active, startEnd, url) {
        var text;
        var start = startEnd[0];
        var end = startEnd[1];
        var startPoint = cm.getCursor("start");
        var endPoint = cm.getCursor("end");
        if (url) {
            end = end.replace("#url#", url);
        }
        if (active) {
            text = cm.getLine(startPoint.line);
            start = text.slice(0, startPoint.ch);
            end = text.slice(startPoint.ch);
            cm.replaceRange(start + end, {
                line: startPoint.line,
                ch: 0
            });
        } else {
            text = cm.getSelection();
            cm.replaceSelection(start + text + end);

            startPoint.ch += start.length;
            if (startPoint !== endPoint) {
                endPoint.ch += start.length;
            }
        }
        cm.setSelection(startPoint, endPoint);
        cm.focus();
    }
})();