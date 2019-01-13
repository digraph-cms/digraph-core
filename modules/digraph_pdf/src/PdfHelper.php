<?php
namespace Digraph\Modules\digraph_pdf;

use Mpdf\Mpdf;
use Flatrr\SelfReferencingFlatArray;

class PdfHelper extends \Digraph\Helpers\AbstractHelper
{
    protected function config($noun=null)
    {
        $config = new SelfReferencingFlatArray($this->cms->config->get('pdf'));
        if ($noun && $noun['pdf']) {
            $config->merge($noun['pdf'], null, true);
        }
        return $config;
    }

    public function pdfBook($noun)
    {
        $mpdf = $this->mpdf($noun);
        $this->mpdfNoun($mpdf, $noun);
        $tc = $this->config($noun)['toc'];
        $mpdf->TOCpagebreakByArray($tc);
        $this->buildPDFBook($mpdf, $noun);
        $mpdf->setHTMLHeader('', 'O');
        $mpdf->setHTMLHeader('', 'E');
        return $mpdf->output('', 'S');
    }

    protected function bookMeta($noun)
    {
        return $this->getTemplate('book_meta', $noun);
    }

    protected function buildPDFBook(&$mpdf, $noun, $level=0)
    {
        $this->mpdfNoun($mpdf, $noun);
        //write this noun's content to the PDF if it isn't a TOC
        if (!$this->isTOC($noun)) {
            //different behavior for first/root noun
            if ($level == 0) {
                //root noun doesn't get a page break, but does need headers and
                //footer set to its right ones
                $mpdf->SetHeaderByName($noun['dso.id'].'_right', 'O');
                $mpdf->SetHeaderByName($noun['dso.id'].'_left', 'E');
                $mpdf->SetFooterByName($noun['dso.id'].'_right', 'O');
                $mpdf->SetFooterByName($noun['dso.id'].'_left', 'E');
            } else {
                $mpdf->addPageByArray([
                    'type' => 'next-odd',
                    'odd-header-name' => 'html_'.$noun['dso.id'].'_right',
                    'odd-header-value' => 1,
                    'even-header-name' => 'html_'.$noun['dso.id'].'_left',
                    'even-header-value' => 1,
                    'odd-footer-name' => 'html_'.$noun['dso.id'].'_right',
                    'odd-footer-value' => 1,
                    'even-footer-name' => 'html_'.$noun['dso.id'].'_left',
                    'even-footer-value' => 1
                ]);
            }
            //add content to pdf
            $mpdf->TOC_Entry($noun->title(), $level);
            $mpdf->WriteHTML($this->content($noun));
        } else {
            $mpdf->TOC_Entry($noun->title(), $level);
        }
        //recurse, always
        foreach ($noun->children() as $child) {
            $this->buildPDFBook($mpdf, $child, $level+1);
        }
    }

    protected function isTOC($noun)
    {
        return false;
    }

    public function pdf($noun)
    {
        $mpdf = $this->mpdf($noun);
        //load from noun
        $this->mpdfNoun($mpdf, $noun);
        $mpdf->SetHeaderByName($noun['dso.id'].'_right', 'O');
        $mpdf->SetHeaderByName($noun['dso.id'].'_left', 'E');
        $mpdf->SetFooterByName($noun['dso.id'].'_right', 'O');
        $mpdf->SetFooterByName($noun['dso.id'].'_left', 'E');
        $mpdf->WriteHTML($this->content($noun));
        //return output
        return $mpdf->output('', 'S');
    }

    protected function content($noun)
    {
        $out = '<h1>'.$noun->title().'</h1>';
        //column markup
        if ($cols = $this->config($noun)['columns']) {
            $out .= '<columns column-count="'.$cols['count'].'" />';
            $out .= $noun->body();
            $out .= '<columns column-count="0" />';
        } else {
            $out .= $noun->body();
        }
        //unpublished warning
        if (!$noun->isPublished()) {
            $out = "<div class=\"digraph-pdf-unpublished\">".
                   "<div class=\"digraph-pdf-unpublished-warning\">".
                   $this->cms->helper('strings')->string('pdf.unpublished', [
                       'user' => $this->cms->helper('users')->id(),
                       'ip' => $_SERVER['REMOTE_ADDR']
                       ]).
                   "</div>".
                   "$out</div>";
        }
        return $out;
    }

    protected function mpdfNoun(&$mpdf, $noun=null)
    {
        if ($mpdf->mirrorMargins == 1) {
            $mpdf->DefHTMLHeaderByName(
                $noun['dso.id'].'_right',
                $this->getTemplate('header_right', $noun)
            );
            $mpdf->DefHTMLHeaderByName(
                $noun['dso.id'].'_left',
                $this->getTemplate('header_left', $noun)
            );
            $mpdf->DefHTMLFooterByName(
                $noun['dso.id'].'_right',
                $this->getTemplate('footer_right', $noun)
            );
            $mpdf->DefHTMLFooterByName(
                $noun['dso.id'].'_left',
                $this->getTemplate('footer_left', $noun)
            );
        } else {
            $mpdf->DefHTMLHeaderByName(
                $noun['dso.id'].'_right',
                $this->getTemplate('header', $noun)
            );
            $mpdf->DefHTMLFooterByName(
                $noun['dso.id'].'_right',
                $this->getTemplate('footer', $noun)
            );
        }
    }

    protected function mpdf($noun=null)
    {
        //instantiate Mpdf with config from CMS
        $mpdf = new Mpdf(
            $this->config($noun)['mpdf']
        );
        //set up for spread
        if ($this->spread($noun)) {
            $mpdf->mirrorMargins = 1;
        }
        //set up print styles from CMS
        $mpdf->WriteHTML(
            $this->css($noun),
            \Mpdf\HTMLParserMode::HEADER_CSS
        );
        //return
        return $mpdf;
    }

    protected function spread($noun=null)
    {
        if (isset($_GET['pdf_spread'])) {
            return boolval($_GET['pdf_spread']);
        }
        return $this->config($noun)['spread'];
    }

    protected function css($noun=null)
    {
        return $this->cms->helper('media')->getContent('pdf/pdf.css');
    }

    protected function getTemplate($name, $noun=null)
    {
        $t = $this->cms->helper('templates');
        //check if config wants to override this template name
        $config = $this->config($noun);
        if ($config['templates.'.$name]) {
            $name = $config['templates.'.$name];
        }
        //return rendered template
        return $t->render(
            $t->themeTemplate('pdf/'.$name.'.twig'),
            $this->templateFields($noun)
        );
    }

    protected function templateFields($noun)
    {
        $fields = new SelfReferencingFlatArray();
        $fields->merge($this->cms->config['package.defaults.fields']);
        $fields['noun'] = $noun;
        return $fields->get();
    }
}
