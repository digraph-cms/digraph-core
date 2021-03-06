<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

use Digraph\Filters\AbstractFilter;

/**
 * This abstract filter locates and processes Digraph system tags, and is
 * meant to be extended to build all the system tag filters.
 */
abstract class AbstractBBCodeFilter extends AbstractFilter
{
    const TEMPLATEPREFIX = false;

    public function tag($tag, $context, $text, $args)
    {
        $nouns = $this->cms->locate($context);
        if (count($nouns) < 2) {
            // zero or one valid context matches, return a single tag
            return $this->buildTag($tag, $context, $text, $args);
        } else {
            // multiple matches, return tags built from all matches, wrapped in information block
            $out = '<div class="notification notification-warning notification-bbcode-ambiguity">';
            $out .= '<strong>This <code>' . $tag . '</code> tag has the ambiguous context <code>' . $context . '</code></strong> and could potentially be rendered as any of the following outputs:';
            foreach ($nouns as $n) {
                $out .= '<div class="bbcode-ambiguity-option">' . $this->buildTag($tag, $n['dso.id'], $text, $args) . '</div>';
            }
            $out .= '</div>';
            return $out;
        }
    }

    protected function buildTag($tag, $context, $text, $args)
    {
        //first try to get output from templates
        $out = $this->fromTemplate(
            $tag, //tag
            $context, //context
            $text, //text
            $args
        );
        //then try named method
        $method = 'tag_' . $tag;
        if (!$out && method_exists($this, $method)) {
            $out = $this->$method(
                $context, //context
                $text, //text
                $args
            );
        }
        //return output
        return $out;
    }

    protected function fromTemplate($tag, $context, $text, $args)
    {
        if (static::TEMPLATEPREFIX === false || preg_match('/[^a-z0-9\-_\/]/i', $tag)) {
            return false;
        }
        $t = $this->cms->helper('templates');
        $template = static::TEMPLATEPREFIX . $tag . '.twig';
        if (!$t->exists($template)) {
            return false;
        }
        $fields = $args;
        $fields['args'] = $args;
        $fields['noun'] = $fields['context'] = $this->cms->read($context);
        $fields['text'] = $text;
        $fields['tag'] = $tag;
        if (!$fields['noun']) {
            return false;
        }
        return trim($t->render(
            $template,
            $fields
        ));
    }

    public function tagsProvided()
    {
        $tags = [];
        //get from methods
        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'tag_') === 0) {
                $tags[] = substr($method, 4);
            }
        }
        //get from templates
        if (static::TEMPLATEPREFIX !== false) {
            foreach ($this->cms->config['templates.paths'] as $path) {
                $path .= '/' . static::TEMPLATEPREFIX;
                if (is_dir($path)) {
                    $files = glob($path . '*.twig');
                    foreach ($files as $path) {
                        $name = preg_replace('/^.+\/(.+)\.twig$/', '$1', $path);
                        if (preg_match('/^[a-z]/', $name)) {
                            $tags[] = $name;
                        }
                    }
                }
            }
        }
        //unique/sorted
        $tags = array_unique($tags);
        asort($tags);
        return $tags;
    }

    public function tagsProvidedString()
    {
        $tags = $this->tagsProvided();
        return $tags ? '[' . implode('], [', $tags) . ']' : '';
    }

    public function filter(string $text, array $opts = []): string
    {
        $tags = $this->tagsProvided();
        /* clean up tags inside paragraphs */
        $text = preg_replace_callback(
            '/<(p)>(\[\/?([^\?\= \/]+).*?\/?\])<\/\1>/i',
            function ($matches) use ($tags) {
                if (in_array(strtolower($matches[3]), $tags)) {
                    return $matches[2];
                }
                return $matches[0];
            },
            $text
        );
        /* clean up tags encompassing entire paragraphs */
        $text = preg_replace_callback(
            '/<(p)>(' . $this->regex(2) . ')<\/\1>/ims',
            function ($matches) use ($tags) {
                if (in_array(strtolower($matches[3]), $tags)) {
                    return $matches[2];
                }
                return $matches[0];
            },
            $text
        );
        /* do replacements */
        $text = preg_replace_callback(
            '/' . $this->regex() . '/ims',
            function ($matches) use ($opts) {
                //figure out method name
                $tag = strtolower($matches[1]);
                //parse args
                $args = [];
                preg_match_all(
                    '/ *([^\= ]+)(=(([\'"]|&quot;)?)(.+?)\3)?( |$)/',
                    $matches[6],
                    $argMatches
                );
                foreach ($argMatches[1] as $i => $name) {
                    $args[$name] = $argMatches[5][$i] ? $argMatches[5][$i] : true;
                }
                //sort out context and get text
                $context = @$matches[3];
                $text = @$matches[11];
                if (!$context) {
                    $context = $this->context;
                }
                //get equals arg
                if (@$matches[5]) {
                    $args['equals'] = $matches[5];
                }
                //try to build tag
                $out = $this->tag($tag, $context, $text, $args);
                //return output if it exists
                return $out ? $this->filter($out) : $matches[0];
            },
            $text
        );
        return $text;
    }

    protected function regex($depth = 0)
    {
        $regex = '';
        $regex .= '\[([a-z][a-z0-9\-]*)'; //open opening tag
        $regex .= '(:([^\] ]+))?'; //context argument
        $regex .= '(=([^\] ]+))?'; //bbcode style "equals" argument
        $regex .= '(( +[a-z0-9\-_]+(=.+?)?)*)'; //named args
        $regex .= ' *(\/\]|\]'; //self-close opening tag, or not self-closed so we might have text
        $regex .= '((.*?)'; //content -- plus opening paren for making closing tag optional
        $regex .= '\[\/\\' . ($depth + 1) . '\])?)'; //closing tag
        return $regex;
    }
}
