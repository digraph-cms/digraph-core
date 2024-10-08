<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\ExceptionLog;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Reader\MsDoc;
use PhpOffice\PhpWord\Reader\ODText;
use PhpOffice\PhpWord\Reader\RTF;
use PhpOffice\PhpWord\Reader\Word2007;
use Smalot\PdfParser\Parser;

class TextExtractor
{
    public static function extractFilestoreFile(FilestoreFile $file): string|null
    {
        return static::extract($file->path(), $file->hash(), $file->filename());
    }

    public static function extract(string $path, string|null $hash = null, string|null $filename = null): string|null
    {
        $filename = $filename ?? basename($path);
        $hash = $hash ?? md5($path);
        return Cache::get(
            'system/extracted_file_text/' . $hash,
            function () use ($path, $filename) {
                try {
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (method_exists(static::class, 'extract_' . $extension)) {
                        return call_user_func([static::class, 'extract_' . $extension], $path);
                    }
                    return null;
                } catch (\Throwable $th) {
                    ExceptionLog::log($th);
                    return null;
                }
            },
            -1
        );
    }

    protected static function extract_txt(string $path): string|null
    {
        return file_get_contents($path);
    }

    protected static function extract_html(string $path): string|null
    {
        return strip_tags(file_get_contents($path));
    }

    protected static function extract_docx(string $path): string|null
    {
        $reader = new Word2007();
        $doc = $reader->load($path);
        return static::phpWordToText($doc);
    }

    protected static function extract_doc(string $path): string|null
    {
        $reader = new MsDoc();
        $doc = $reader->load($path);
        return static::phpWordToText($doc);
    }

    protected function extract_rtf(string $path): string|null
    {
        $reader = new RTF();
        $doc = $reader->load($path);
        return static::phpWordToText($doc);
    }

    protected function extract_odf(string $path): string|null
    {
        $reader = new ODText();
        $doc = $reader->load($path);
        return static::phpWordToText($doc);
    }

    protected static function phpWordToText(PhpWord $doc): string|null
    {
        $text = '';
        foreach ($doc->getSections() as $section) {
            $section_text = trim(static::phpWordExtractText($section));
            if ($section_text) {
                $text .= $section_text . PHP_EOL;
            }
        }
        $text = trim($text);
        $text = mb_convert_encoding($text, mb_internal_encoding());
        $text = mb_trim($text);
        return $text ?: null;
    }

    protected static function phpWordExtractText(AbstractElement $element): string
    {
        $text = '';
        if ($element instanceof AbstractContainer) {
            foreach ($element->getElements() as $child) {
                static::phpWordExtractText($child);
            }
        } elseif ($element instanceof Text) {
            $element_text = trim($element->getText());
            if ($element_text) {
                $text .= $element_text . PHP_EOL;
            }
        }
        return $text;
    }

    protected static function extract_pdf(string $path): string|null
    {
        // take as much memory as needed
        ini_set('memory_limit', '4G');
        $parser = new Parser();
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText();
        $text = mb_convert_encoding($text, mb_internal_encoding());
        $text = mb_trim($text);
        return $text ?: null;
    }
}
