<?php
namespace Digraph\Modules\digraph_pdf;

use Mpdf\Mpdf;
use Flatrr\SelfReferencingFlatArray;

class PdfHelper extends \Digraph\Helpers\AbstractHelper
{
    public function pdfBook($noun)
    {
        $mpdf = $this->mpdf($noun);
        $mpdf->TOCpagebreakByArray($this->cms->config['pdf.toc']);
        $this->buildPDFBook($mpdf, $noun);
        $this->mpdfNoun($mpdf, $noun);
        return $mpdf->output('', 'S');
    }

    protected function buildPDFBook(&$mpdf, $noun, $level=0)
    {
        //write this noun's content to the PDF if it isn't a TOC
        if (!$this->isTOC($noun)) {
            $this->mpdfNoun($mpdf, $noun);
            //add page break
            if ($level == 0) {
                $mpdf->AddPageByArray([
                    'type' => 'odd'
                ]);
            } else {
                $mpdf->AddPageByArray([
                    'type' => 'next-odd'
                ]);
            }
            $mpdf->TOC_Entry($noun->title().' ('.$level.')', $level);
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
        $this->mpdfNoun($mpdf, $noun);
        $mpdf->WriteHTML($this->content($noun));
        return $mpdf->output('', 'S');
    }

    protected function content($noun)
    {
        $out = '<h1>'.$noun->title().'</h1>';
        $out .= $noun->body();
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
        if ($this->spread($noun)) {
            $mpdf->mirrorMargins = 1;
            $mpdf->setHTMLHeader($this->getTemplate('header_right', $noun), 'O');
            $mpdf->setHTMLFooter($this->getTemplate('footer_right', $noun), 'O');
            $mpdf->setHTMLHeader($this->getTemplate('header_left', $noun), 'E');
            $mpdf->setHTMLFooter($this->getTemplate('footer_left', $noun), 'E');
        } else {
            $mpdf->setHTMLHeader($this->getTemplate('header', $noun), true);
            $mpdf->setHTMLFooter($this->getTemplate('footer', $noun), true);
        }
    }

    protected function mpdf($noun=null)
    {
        //instantiate Mpdf with config from CMS
        $mpdf = new Mpdf(
            $this->cms->config['pdf.mpdf']
        );
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
        return $this->cms->config['pdf.spread'];
    }

    protected function css($noun=null)
    {
        return $this->cms->helper('media')->getContent('pdf/pdf.css');
    }

    protected function getTemplate($name, $noun=null)
    {
        $t = $this->cms->helper('templates');
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
