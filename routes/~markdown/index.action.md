# Markdown formatting

## What is Markdown?

Markdown is a lightweight language for adding formatting to text. Its primary advantage is that it is highly *human readable*, meaning that it is relatively comprehensible at a glance, even to people unfamiliar with its details.
For the most part Markdown documents will be easy to read and edit, even for non-technical users, and the rules will feel intuitive to many people.

## The basics

### Paragraphs and headers

A paragraph is just any chunk of consecutive text, separated by at least one blank line from other paragraphs. Single line breaks within paragraphs are ignored, and will appear as spaces in the final output.

Headers can be marked two different ways:

* Prefixing a line with between one and six pound signs <code class='lang-plaintext'>#</code> to indicate various heading levels from one to six.
* Underlining the heading line with either equals signs `=` or dashes `-` to indicate a level one or level two header, respectively.

#### Example

```markdown
Top-level header
================

This is a paragraph of text. All the content is on one line, and it is separated from neighboring content by blank lines.

Second-level header
-------------------

This is also a paragraph of text.
This one has some single line breaks,
which will be ignored when the content is turned into HTML.

# Alternative style of top-level header

## Alternative style second-level header

### Alternative style third-level header
```