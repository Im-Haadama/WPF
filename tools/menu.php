<?php include "tools_wp_login.php";
require_once( "tools_wp_login.php" );
?>
<html dir="rtl">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>ניהול החנות</title>
</head>

<body>

<h2 style="text-align:center; margin-bottom: 0cm;">ממשק לניהול החנות</h2>
<div style="text-align:center; ">
    <table style="margin-top: 0cm;">
        <tr>
            <td>
                <img style="padding:0;" src="<?php print get_logo_url(); ?>">
            </td>
            <td>
                <table style="page-break-before: always;" cellpadding="0"
                       cellspacing="0">
                    <col width="128">
                    <col width="174">
                    <col width="122">
                    <tbody>
                    <tr>
                        <td style="border: medium none ; padding: 0in;">
                            <h3 class="ctl" align="center"><span lang="he-IL">ניהול שבועי</span></h3>
                        </td>
                        <td style="vertical-align: top; text-align: center;">
                            <h3>משלוחים</h3>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <h3 class="ctl" align="center"><span lang="he-IL">ניהול חנות</span></h3>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <h3 class="ctl" align="center"><span lang="he-IL">שונות</span></h3>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <h3 class="ctl" align="center"><span lang="he-IL">ספקים</span></h3>
                        </td>

                        <td style="border: medium none ; padding: 0in;">
                            <h3 class="ctl" align="center"><span lang="he-IL">מבצעים</span></h3>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <h3 class="ctl" align="center"><span lang="he-IL">רב אתר</span></h3>
                        </td>

                    </tr>
                    <tr>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="orders/orders-get.php" target="doc_frame"><span lang="he-IL">הזמנות
פעילות</span></a></span></p>
                        </td>
                        <td style="vertical-align: top;">
                            <meta http-equiv="content-type" content="text/html; charset=utf-8">
                            <p align="center"><span lang="he-IL"><a
                                            href="delivery/get-driver-multi.php"><span lang="he-IL">פלט
לנהגים</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="pricelist/pricelist-get.php" target="doc_frame"><span
                                                lang="he-IL">מחירוני ספקים</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="orders/orders-get-by-date.php?last_days=30" target="doc_frame"><span
                                                lang="he-IL">הזמנות בחודש החולף</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="business/get_all.php?week=<?php print( sunday( date( "Y-m-d" ) )->format( "Y-m-d" ) ); ?>"
                                            target="doc_frame"><span
                                                lang="he-IL">תעודות משלוח</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="catalog/sale.php" target="doc_frame"><span
                                                lang="he-IL">מבצעים</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="multi-site/multi-catalog.php" target="doc_frame"><span
                                                lang="he-IL">מיפוי בין אתרי</span></a></span></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="orders/get-total-orders.php"><span
                                                lang="he-IL">פריטים להזמין</span></a></span></p>
                        </td>
                        <td style="vertical-align: top;"><a href="people/drivers.php">משלוחים
                                שבוצעו</a>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="baskets/show_baskets.php" target="doc_frame"><span lang="he-IL">סלי
השבוע</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="account/get-accounts-status.php" target="doc_frame"><span
                                                lang="he-IL">סטטוס תשלומים</span></a></span></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="supplies/supplies-get.php" target="doc_frame"><span lang="he-IL">אספקות</span></a></span>
                            </p>
                        </td>
                        <td style="vertical-align: top;">
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="catalog/catalog-map.php" target="doc_frame"><span lang="he-IL">מיפוי
מחירון</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a href="orders/margin.php"
                                                                    target="doc_frame"><span lang="he-IL">מרווח הזמנות פעילות</span></a></span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="weekly/prepare.php"><span
                                                lang="he-IL">הכנת סבב</span></a></span></p>
                        </td>

                        </td>
                        <td style="vertical-align: top;"><br>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="catalog/catalog-update-prices.php" target="doc_frame"><span
                                                lang="he-IL">עדכון מחירון</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;" valign="top"
                            width="122">
                            <p align="center"><font style="font-size: 12pt;" size="3"><span
                                            lang="he-IL"><a href="catalog/catalog-get.php" target="doc_frame"><span
                                                    lang="he-IL">חיפוש פריטים ושינוי מעם</span></a></span></font></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><br>
                                <a href="inventory/display.php">מצב המלאי</a>
                            </p>
                        </td>
                        <td style="vertical-align: top;"><br>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><span lang="he-IL"><a
                                            href="catalog/bundles-get.php" target="doc_frame"><span lang="he-IL">מארזי
כמות</span></a></span></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;" valign="top"
                            width="122">
                            <p align="center"><font style="font-size: 12pt;" size="3"><span lang="he-IL"><a
                                                href="people/volunteers.php"
                                                target="doc_frame">מעקב התנדבויות</a></span></font></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: medium none ; padding: 0in;"><br>
                        </td>
                        <td style="vertical-align: top;"><br>
                        </td>
                        <td style="border: medium none ; padding: 0in;">
                            <p align="center"><font style="font-size: 12pt;" size="3"><span
                                            lang="he-IL"><a href="catalog/cost-price-list.php" target="doc_frame"><span
                                                    lang="he-IL">מחירון עלות</span></a></span></font></p>
                        </td>
                        <td style="border: medium none ; padding: 0in;" valign="top"
                            width="122"><br>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>


    <!--    <table>-->
    <!--	<tr>-->
    <!--	<tr>-->
    <!--		<td><h3>ניהול שבועי</h3></td>-->
    <!--		<td><h3>ניהול מוצרים</h3></td>-->
    <!--		<td><h3>דוחות<h3></td>-->
    <!--	</tr>-->
    <!--	<tr>-->
    <!--		<td><a href="orders/orders-get.php" target="doc_frame">הזמנות פעילות</a></td>-->
    <!--		<td><a href="pricelist/pricelist-get.php" target="doc_frame">מחירוני ספקים</a></td>-->
    <!--		<td><a href="orders/orders-get-by-date.php%3Flast_days=30" target="doc_frame">הזמנות בחודש החולף</a></td>-->
    <!--	</tr>-->
    <!--	<tr>-->
    <!--		<td><a href="orders/get-total-orders.php" target="doc_frame">סהכ פריטים מוזמנים</a></td>-->
    <!--        <td><a href="catalog/catalog-get.php" target="doc_frame">חיפוש פריטים ושינוי מעם</a></td>-->
    <!--        <td><a href="account/get-accounts-status.php" target="doc_frame">סטטוס תשלומים</a></td>-->
    <!--	</tr>-->
    <!--	<tr>-->
    <!--        <td><a href="supplies/supplies-get.php" target="doc_frame">אספקות</a></td>-->
    <!--        <td><a href="baskets/show_baskets.php" target="doc_frame">סלי השבוע</a></td>-->
    <!--        <td><a href="orders/margin.php" target="doc_frame">מרווח הזמנות פעילות</a></td>-->
    <!--	</tr>-->
    <!--    <tr>-->
    <!--        <td><a href="delivery/get-driver.php" target="doc_frame">פלט לנהגים</a></td>-->
    <!--        <td><a href="catalog/catalog-map.php" target="doc_frame">מיפוי מחירון</a></td>-->
    <!--    </tr>-->
    <!--    <tr>-->
    <!--        <td><a href="catalog/siton.php" target="doc_frame">מחירון סיטונאי</a></td>-->
    <!--        <td><a href="catalog/catalog-update-prices.php" target="doc_frame">עדכון מחירון</a></td>-->
    <!--    </tr>-->
    <!--    <tr>-->
    <!--        <td></td>-->
    <!--        <td><a href="catalog/bundles-get.php" target="doc_frame">מארזי כמות</a></td>-->
    <!--    </tr>-->
    <!---->
    <!--</table>-->
    <iframe name="doc_frame" width="1000" height="600">
    </iframe>

</div>

<br><br>

</body>
</html>
