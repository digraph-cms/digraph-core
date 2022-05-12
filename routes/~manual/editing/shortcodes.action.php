<h1>ShortCode formatting</h1>

<p>
    ShortCodes are a formatting tool available on this site that uses small text "tags"
    surrounded in square brackets <code>[</code> and <code>]</code> that can be used to achieve various
    formatting and content-management goals.
</p>

<h2>Concepts</h2>

<p>
    Most ShortCode tags consist of both an "opening" and a "closing" tag, which may surround text and will
    impart their effects on whatever text is inside them. For example, the <code>[i]</code> tag can be used
    to italicize text:
</p>

<pre><code>Within this sentence [i]this text is italic[/i]</code></pre>

<p>
    Some tags, known as "self-closing tags" do not require a closing tag. In many cases this is because
    it is a tag that does not require (or even ignores) any text within it if it is written as a normal tag.
    Self-closing tags should always have a forward slash before their last square bracket, like <code>[tag/]</code>.
    They can also have a space before the slash, if you prefer the appearance, like <code>[tag /]</code>.
    For example, a rich media embedding tag might look something like <code>[media=abcXYZ/]</code>.
</p>