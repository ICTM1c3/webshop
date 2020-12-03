<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Bestelbevestiging | NerdyGadgets</title>
    <style>
        @media only screen and (max-width: 620px) {
            table[class=body] h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }

            table[class=body] p,
            table[class=body] ul,
            table[class=body] ol,
            table[class=body] td,
            table[class=body] span,
            table[class=body] a {
                font-size: 16px !important;
            }

            table[class=body] .wrapper,
            table[class=body] .article {
                padding: 10px !important;
            }

            table[class=body] .content {
                padding: 0 !important;
            }

            table[class=body] .container {
                padding: 0 !important;
                width: 100% !important;
            }

            table[class=body] .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }

            table[class=body] .btn table {
                width: 100% !important;
            }

            table[class=body] .btn a {
                width: 100% !important;
            }

            table[class=body] .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
        }
        @media all {
            .ExternalClass {
                width: 100%;
            }

            .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
                line-height: 100%;
            }

            .apple-link a {
                color: inherit !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                text-decoration: none !important;
            }

            #MessageViewBody a {
                color: inherit;
                text-decoration: none;
                font-size: inherit;
                font-family: inherit;
                font-weight: inherit;
                line-height: inherit;
            }

            .btn-primary table td:hover {
                background-color: #34495e !important;
            }

            .btn-primary a:hover {
                background-color: #34495e !important;
                border-color: #34495e !important;
            }
        }
    </style></head>
<body class="" style="background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
<span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">This is preheader text. Some clients will show this text as a preview.</span>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" width="100%" bgcolor="#f6f6f6">
    <tr>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
        <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto;" width="580" valign="top">
            <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">

                <!-- START CENTERED WHITE CONTAINER -->
                <table role="presentation" class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #ffffff; border-radius: 3px; width: 100%;" width="100%">

                    <!-- START MAIN CONTENT AREA -->
                    <tr>
                        <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;" valign="top">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Beste klant,</p>
                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Bedankt voor uw bestelling. We zullen deze zo spoedig mogelijk versturen. Hieronder staan de bestelgegevens.</p>
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                                            <thead class="align-left" style="text-align: left;" align="left">
                                            <tr>
                                                <th style="text-align: left;">Naam</th>
                                                <th style="text-align: left;">Aantal</th>
                                                <th style="text-align: left;">Bedrag</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $products = $_SESSION['shopping_cart'];
                                            $item_total = 0;
                                            $receipt_lines = ($_SESSION["receipt_lines"]) ? $_SESSION["receipt_lines"] : array();

                                            foreach ($products as $product) {
                                                $productPrice = $product['Price'] * $product['amount'];
                                                $item_total += $productPrice;
                                                ?>
                                                <tr>
                                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top"><?= $product['StockItemName'] ?></td>
                                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top"><?= $product['amount'] ?></td>
                                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&euro;<?= number_format($productPrice, 2, ',', '.') ?> (&euro;<?= number_format($product['Price'], 2, ',', '.') ?> per stuk)</td>
                                                </tr>
                                                <?php
                                            }

                                            foreach ($receipt_lines as $key => $line) { ?>
                                                <tr>
                                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top"><?= $line["NAME"] ?></td>
                                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top"></td>
                                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; font-weight: bold;" valign="top"><?= $line["VALUE"] ?></td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                        <br>
                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Mocht u hier vragen over hebben kunt u altijd  contact met ons opnemen!</p>
                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Met vriendelijke groeten,<br>Het team van NerdyGadgets.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- END MAIN CONTENT AREA -->
                </table>
                <!-- END CENTERED WHITE CONTAINER -->

                <!-- START FOOTER -->
                <div class="footer" style="clear: both; margin-top: 10px; text-align: center; width: 100%;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                        <tr>
                            <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;" valign="top" align="center">
                                <span class="apple-link" style="color: #999999; font-size: 12px; text-align: center;">NerdyGadgets B.V., Campus 2, 8017CA Zwolle</span>.
                            </td>
                        </tr>
                        <tr>
                            <td class="content-block powered-by" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;" valign="top" align="center">
                                &copy; <a href="https://ictm1c3.ward.nl/" style="color: #999999; font-size: 12px; text-align: center; text-decoration: none;">NerdyGadgets</a>.
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- END FOOTER -->

            </div>
        </td>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
    </tr>
</table>
</body>
</html>