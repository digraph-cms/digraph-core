<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\UI\Templates;
use DigraphCMS\UI\Theme;

/** @var Email */
$email = Context::fields()['email'];
$variables = Theme::variables('light');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <base target="_blank"/>
    <title><?php echo $email->subject(); ?></title>
</head>

<body style="margin:0;">
<table border="0" cellpadding="20" cellspacing="0" height="100%" width="100%" id="bodyTable">
    <tr>
        <td align="center" valign="top">
            <table border="0" cellpadding="0" cellspacing="0" width="600" id="emailTable">
                <?php
                if (Templates::exists('/email/html/header_' . $email->category() . '.php')) {
                    echo '<tr><td align="center" valign="top" style="background:' . $variables['background-darker'] . ';border-bottom: 1px solid ' . $variables['theme-neutral'] . ';font-family:' . $variables['font-ui'] . '">';
                    echo Templates::render('/email/html/header_' . $email->category() . '.php');
                    echo "</td></tr>";
                } elseif (Templates::exists('/email/html/header_default.php')) {
                    echo '<tr><td align="center" valign="top" style="background:' . $variables['background-darker'] . ';border-bottom: 1px solid ' . $variables['theme-neutral'] . ';font-family:' . $variables['font-ui'] . '">';
                    echo Templates::render('/email/html/header_default.php');
                    echo "</td></tr>";
                }
                ?>
                <tr>
                    <td align="center" valign="top">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" id="contentTable">
                            <tr>
                                <td align="left" valign="top">
                                    <?php echo $email->body_html(); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center" valign="top">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" id="footerTable">
                            <tr>
                                <td align="left" valign="top" style="font-size:small;">
                                    <?php
                                    if (Templates::exists('/email/html/footer_' . $email->category() . '.php')) {
                                        echo Templates::render('/email/html/footer_' . $email->category() . '.php');
                                    } else {
                                        echo Templates::render('/email/html/footer_default.php');
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td align="right" valign="top" style="font-size:small;" id="emailIDCell">
                                    <small>
                                        Email ID: <?php echo $email->uuid(); ?>
                                    </small>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>

</html>