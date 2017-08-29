<html dir="rtl">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>ניהול החנות</title>

</head>
<?php include "tools_wp_login.php";

?>
<body>

<center><img src="http://store.im-haadama.co.il/wp-content/uploads/2014/11/cropped-imadama-logo-7x170.jpg">
    <h2>ממשק לניהול החנות</h2>
</center>
<center>
    <table>
        <tr>
        <tr>
            <td><h3>ניהול שבועי</h3></td>
            <td><h3>ניהול מוצרים</h3></td>
            <td>
                <h3>דוחות<h3>
            </td>
        </tr>
        <tr>
            <td><a href="orders/orders-get.php" target="doc_frame">הזמנות פעילות</a></td>
            <td><a href="pricelist/pricelist-get.php" target="doc_frame">מחירוני ספקים</a></td>
            <td><a href="orders/orders-get-by-date.php%3Flast_days=30" target="doc_frame">הזמנות בחודש החולף</a></td>
        </tr>
        <tr>
            <td><a href="orders/get-total-orders.php" target="doc_frame">סהכ פריטים מוזמנים</a></td>
            <td><a href="catalog/catalog-get.php" target="doc_frame">חיפוש פריטים ושינוי מעם</a></td>
            <td><a href="account/get-accounts-status.php" target="doc_frame">סטטוס תשלומים</a></td>
        </tr>
        <tr>
            <td><a href="delivery/get-driver.php" target="doc_frame">פלט לנהגים</a></td>
            <td><a href="baskets/show_baskets.php" target="doc_frame">סלי השבוע</a></td>
            <td><a href="orders/margin.php" target="doc_frame">מרווח הזמנות פעילות</a></td>
        </tr>
        <tr>
            <td><a href="catalog/siton.php" target="doc_frame">מחירון סיטונאי</a></td>
            <td><a href="catalog/catalog-map.php" target="doc_frame">מיפוי מחירון</a></td>
        </tr>
        <tr>
            <td></td>
            <td><a href="catalog/catalog-update-prices.php" target="doc_frame">עדכון מחירון</a></td>
        </tr>
        <tr>
            <td></td>
            <td><a href="catalog/bundles-get.php" target="doc_frame">מארזי כמות</a></td>
        </tr>

    </table>
    <iframe name="doc_frame" width="1000" height="600">
    </iframe>
    <br><br>

</body>
</html>
