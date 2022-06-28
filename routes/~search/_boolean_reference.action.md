# Boolean search syntax guide

The default search mode uses natural language processing tools to generate results using complex automatic methods that will return intuitive and useful results in most situations.
However, some users may be interested in giving themselves more direct control over the way their search is executed and ranked.

The boolean search mode allows you to manually give specific guidance for how each word in your search is treated and ranked.
For example, it can be used to require or exclude specific words, or make particular words in your query push search results up or down in the list.
Most boolean operators are single special characters which can be put either at the beginning or end of a word to indicate extra information about how you would like the search engine to treat that word.

By default all words in your query are treated as `OR` operations, meaning that the results will contain pages that include any one or more words.
For example, if you searched for `apple computer` you would get results containing the word "apple" *or* the word "computer" which may not be quite what you want.

## Match phrases with quotes

Enclosing multiple words will cause the entire phrase to be treated as a single unit for matching results. For example, you might search for `"apple computer"` to only return results that contain the words "apple" and "computer" next to each other in that order.

Note that punctuation is ignored, so `"apple computer"` would match a document containing `apple, computer`.

A phrase wrapped in quotes can have any of the following operators attached to it as well, as if it were a single word.

## Require a word with `+`

If you place a `+` sign at the beginning or end of a word, then *only* pages that contain that word will return, even if they include other words in the query.
For example, if you wanted to search for pages containing *both* the words "apple" and "computer" you could search for `+apple +computer`.

## Exclude a word with `-`

If you place a `-` sign at the beginning or end of a word, then only pages that *do not* contain that word will return, even if they match the rest of your query.
For example, if you were only interested in apple trees, and wanted to avoid results about apple computers, you might search for `+apple +tree -computer` to get pages that include the words "apple" and "tree" but not "computer."

## Controlling result sorting

`<` before a word indicates that a given word should have less impact on search ranking.

`>` before a word indicates that it should have more impact on search ranking.

`~` before a word indicates that it should rank a page lower. This is useful for words that are creating noise in your search results, but cannot be excluded entirely, as the `-` operator would.

## Wildcard searches

Placing a `*` after a word matches longer words that begin with the specified characters. For example searching `apple*` would match "apple" or "applesauce." Note that `*` can only be used at the <em>end</em> of words to search for prefixes. Placing it at the beginning to search for suffixes is not supported, nor is placing it in the middle of a word.

## Further reading

There are more caveats and more available features, but the above guide is intended to be a simple primer to enable beginners to get started with boolean search syntax.
For a more in-depth explanation of the underlying technology and information about more options that may be available, please consult the [MySQL Boolean Full-Text Searches documentation](https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html).
Note that some features may or may not be available on this specific site, depending on its underlying database configuration.