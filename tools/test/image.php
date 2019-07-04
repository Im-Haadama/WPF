<?php

if ( ! defined( "TOOLS_DIR" ) ) {
	define( "TOOLS_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once (TOOLS_DIR . "/im_tools.php");
require_once (TOOLS_DIR . '/wp/Product.php');

$prod_ids = sql_query_array_scalar("select id from wp_posts where post_type = 'product'");

foreach ($prod_ids as $prod) {
	$p = new Product( $prod );

	$post_thumbnail_id = $p->getImageId();

	if ( ! $post_thumbnail_id ) {
		print "product " . get_product_name( $prod ) . "($prod) missing image ";
		fix_image( $prod );
//		die( 1 );
	}
}

// fix_image(3049);
function fix_image($prod_id)
{
//		print "product " . get_product_name( $prod_id ) . "($prod_id) missing image ";
		$result = sql_query("select id, guid from wp_posts_att where post_parent = " . $prod_id);
//		var_dump($result);
		while ($row = sql_fetch_row($result))
		{
			var_dump($row);
		}
//		if ($result) {
//			$attachment_id = $result[0];
//			$path = $result[1];
//			print " found " . $attachment_id . " " .$path;
//
////			var_dump(get_post($id));
////			$data = wp_generate_attachment_metadata( $attachment_id, $path );
//
////			 print "result = " . wp_update_attachment_metadata($id, $r);
//			// $attachment_id = (int) $attachment_id;
//
////			set_post_meta_field( $prod_id, '_thumbnail_id', $attachment_id );
//
//
//			//var_dump($r);
//
//			// set_post_meta_field( $prod_id, '_thumbnail_id', $id );
//
////			 update_post_meta($prod, '_thumbnail_id', $thumb);
//		}
		print "<br/>";
}

//$prod_ids = sql_query_array_scalar("select id from wp_posts where post_type = 'product'");
//
//foreach ($prod_ids as $prod)
//{
//	$p = new Product($prod);
//
//	$post_thumbnail_id = $p->getImageId();
//
//	if (! $post_thumbnail_id) {
//		print "product " . get_product_name( $prod ) . "($prod) missing image ";
//		$thumb = sql_query_single_scalar("select min(id) from wp_posts where post_parent = " . $prod . " and guid like '%uploads%'");
//		if ($thumb) {
//			print " found " . $thumb;
//			 update_post_meta($prod, '_thumbnail_id', $thumb);
//		}
//		print "<br/>";
//	}
//
//}

//$pid = 874;
//
//print "checking prod " . get_product_name($pid) ."<br/>";
//$p = new Product($pid);
//
//$post_thumbnail_id = $p->getImageId();
//print "thunmb = " .$post_thumbnail_id . "<br/>";
//
//if (! $post_thumbnail_id) {
//	$thumb = sql_query_single_scalar("select min(id) from wp_posts where post_parent = " . $pid );
//	print "found " . $thumb. '<br/>';
//	update_post_meta($pid, '_thumbnail_id', $thumb);
//}
//
//
//// update_post_meta($pid)
//
//$html = wc_get_gallery_image_html( $post_thumbnail_id, true );
//print $html;

//product פלפל כתום (ק''ג)(157) missing image
//product חומוס אורגני תבואות 500 ג(193) missing image
//product אבוקדו אטינגר(120) missing image
//product אבקת הלבנה Ecofriend(221) missing image
//product מרכך כביסה 750 מ"ל ecover(243) missing image found 2384
//product ברוקולי (ק''ג)(296) missing image
//product צנון שחור אורגני קג(344) missing image
//product אגוזי מקדמיה 100 גרם(365) missing image
//product תמר ברהי סוג ב(374) missing image
//product פתיתי קוקוס אורגני 300 ג(393) missing image
//product אגוזי מקדמיה 100 גרם לא מקולף(418) missing image
//product אגוזי מקדמיה 100 גרם מקולף(419) missing image
//product אורז אדום אורגני 500 גר' תבואות(422) missing image
//product אורז בר שחור 400 ג(428) missing image found 2480
//product בייגלה כוסמין(448) missing image
//product חלה מתוקה כוסמין(449) missing image
//product לחם כוסמין דר מרק(452) missing image
//product לחם ללא גלוטן וללא סוכר(453) missing image
//product לחם מחמצת קמח שיפון מלא טאוברד(454) missing image
//product לחם פרוס ללא גלוטן Green Lite(455) missing image
//product לחם פרוס ללא גלוטן Green Lite לחם זיתי(456) missing image
//product לחם שיפון פרוס ד"ר מרק(462) missing image
//product לחמניות Green Lite(463) missing image
//product לחמניות Green Lite לחמניות המבורגר ללא(466) missing image
//product לחמניות Green Lite לחמניות שליש באגט(470) missing image
//product לחמניות כוסמין(472) missing image
//product פיתה חיטה מלאה(475) missing image
//product פיתה כוסמין(476) missing image
//product פיתה מיני חיטה(477) missing image
//product פיתה מיני כוסמין(478) missing image
//product פריכיות אורז מאורז חום מלא B(479) missing image
//product קרקר בריאות כוסמין אריזה(480) missing image
//product אננס קפוא 300 גרם מאמא מרי(485) missing image
//product בננה קפואה(486) missing image
//product מלון קפוא(497) missing image
//product תות שדה קפוא - 500 גרם(502) missing image
//product תמר קפוא(503) missing image
//product שמן רוזמרין(522) missing image
//product אורז עגול מלא 1 ק"ג(527) missing image
//product אטריות מאורז תאילנדי 250 גר' השדה(541) missing image
//product אצות ווקמי(552) missing image
//product גבינה טבעונית 230 ג einut’s גבינת זיתי(563) missing image
//product גבינה טבעונית 230 ג einut’s מוצרלה קשיו(569) missing image
//product גבינות קשות טבעוניות 200 ג משוMASHU גו(576) missing image
//product גבינות קשות טבעוניות 200 ג משוMASHU מל(577) missing image
//product גבינה טבעונית 230 ג einut’s(589) missing image
//product גלידת בן & גריס טבעונית(590) missing image
//product זיתים ברנע(596) missing image
//product זרעי פשתן 300 גרם - הרדוף(598) missing image found 2459
//product גרעיני חמניה קלופים 300 גרם - הרדוף(600) missing image
//product דבש טבעי 1.5 ק"ג (לא מחומם)(608) missing image
//product דוחן אורגני 500 גרם הרדוף(615) missing image found 2497
//product חומץ תפוחים All Natural 473 מל(626) missing image
//product מגדרה קינואה אורגנית 500 גר' תבואות(641) missing image found 2571
//product חלב אורז VITARIZ ליטר 1(649) missing image
//product גריסי שיבולת שועל תבואות 500 ג(667) missing image
//product מוצרלה קשיו(694) missing image
//product מי קוקוס עם ליצי 330 מ"ל - FOCO(715) missing image
//product ממרח עללחם 180 ג חזרת(727) missing image
//product ממרח עללחם 180 ג חציל עם נגיעות עגבניה(728) missing image
//product ממרח עללחם 180 ג חריף ביתי(731) missing image
//product ממרח עללחם 180 ג טפנד זיתי קלמטה(733) missing image
//product ממרח עללחם 180 ג צימיצורי(734) missing image
//product ממרח עללחם 180 ג טפנד זיתים ירוקים(735) missing image
//product ממרח עללחם 180 ג טפנד זיתים שחורים(737) missing image
//product ממרח עללחם 180 ג שום(738) missing image
//product מלבי רינתי 150 ג בוטנים(741) missing image
//product ממרח עללחם 180 ג לימון כבוש(742) missing image
//product ממרח קרם אגוזים שוקולד מריר 85% מוצקי קק(743) missing image
//product מלבי רינתי 150 ג פיסטוקים(746) missing image
//product ממרח עללחם 180 ג עגבניות מיובשות(747) missing image
//product ממרח עללחם 180 ג פלפלים קלויים(751) missing image
//product מיפל טבעי 1 ק"ג(753) missing image
//product ממרח עללחם 180 ג פסטו ביתי(754) missing image
//product ממרח עללחם 180 ג ציליפיניו(758) missing image
//product מעדן מדברי 4 יח שזיף(766) missing image
//product ממרח על בסיס סויה 225 ג Tofutti(777) missing image
//product מרציפן 40 גרם השוקולד מעודד(781) missing image
//product ממרח עללחם 180 ג(782) missing image
//product ממרח עללחם 180 ג ארטישוק(784) missing image
//product עדשים חומות אורגני 500 גר' הרדוף(803) missing image found 2500
//product סבון גוף ארומתרפי(853) missing image
//product פוזיטיב חטיף אנרגיה(861) missing image
//product פסטה פוזילי מקמח כוסמין מלא 400 ג הרדוף(864) missing image
//product סבון מוצק ד"ר ברונר(873) missing image
//product צהובה טבעונית פרוסות 200 ג VEGA מוצרלה(883) missing image
//product פקצוי(887) missing image
//product פסטה השדה(888) missing image
//product פרוסות בסגנון קלאסי 200 ג משוMASHU(889) missing image
//product צהובה טבעונית פרוסות 200 ג VEGA גבינת(917) missing image
//product צהובה טבעונית פרוסות 200 ג VEGA זיתים(929) missing image
//product קינואה אורגנית 500 גר' הרדוף(943) missing image found 2478
//product שוקולד מריר 60% 100 גר Just Justin(949) missing image
//product קינואה טריקולור מעורבת 500 גר' הרדוף(954) missing image
//product שמן זית שרוליק(959) missing image
//product שמן קוקוס 1 ל ONE TRIBE(962) missing image
//product שעועית אורגנית 500 גרם - תבואות(978) missing image found 1813
//product צלחת מרובעת קטנה מעלי דקל רעיונות ירוקים(1012) missing image
//product צלחת עגולה גדולה מעוטרת מקנה סוכר רעיונו(1013) missing image
//product ריבת fiordifrutta משקל 250 ג(1019) missing image
//product רימוניץ 1 ליטר(1020) missing image
//product חרדל(1031) missing image
//product דבש טבעי 500 גר לא מחומם(1051) missing image
//product זרעי צ'יה 650 גרם - תבואות(1056) missing image found 2461
//product חומוס אורגני במשקל(1060) missing image
//product חומץ תפוחים אורגני(1063) missing image
//product חמאת בוטנים(1064) missing image
//product פולי סויה(1086) missing image
//product ספגטי כוסמין 500 ג(1123) missing image
//product מעדן מדברי 4 יח(1126) missing image
//product שמן זית 16 קג סינדיאנת הגליל(1135) missing image
//product מלפפון הולנדי(1148) missing image
//product חציל אסיאתי(1705) missing image
//product קוקוס לא אורגני - יחידה(1754) missing image
//product דקל נור על סנסנים(1759) missing image
//product תמר זהידי(1760) missing image
//product לימון ננסי - אריזה(1835) missing image
//product פירות סוג ב'(1876) missing image found 1877
//product אבוקדו ארדית אורגני(1915) missing image
//product אפונה שלג(1916) missing image
//product חסה מקורזלת מרירה(1918) missing image
//product טאטסוי עלים(1919) missing image
//product כרוב לבן בייבי(1920) missing image
//product כרוב סגול בייבי(1921) missing image
//product מיזונה אדומה(1922) missing image
//product צנון שחור צרור(1923) missing image found 2293
//product צנונית צבעונית(1924) missing image
//product קייל מיקס(1925) missing image
//product חסה ירוקה מסולסלת(1917) missing image found 2180
//product מארז 5 ק"ג תמר ברהי סוג ב(1947) missing image
//product כרובית אלמוגים משקל(1960) missing image
//product תפוח עץ גרני סמיט - יבוא (ק''ג)(1962) missing image
//product תפוח עץ קרימסון - יבוא(1963) missing image
//product תות אורגני משק 6 150 ג'(2029) missing image found 2156
//product חומץ תפוחים 950 מ"ל All Natural(2054) missing image
//product חומץ תפוחים טבעי אורגני 750 מ"ל נטורפוד(2058) missing image
//product מצות כוסמין מלא פריכות שמורות - 1/2 ק"ג(2125) missing image
//product מצות חיטת ארץ ישראל מלאה אורגנית שמורות - 1/2 ק"ג(2126) missing image
//product תפוח עץ פינק ליידי - יבוא (ק''ג)(2177) missing image
//product מלפפון הולנדי ארוז(2426) missing image
//product אספרגוס אורגני (330 גרם)(2427) missing image found 3597
//product פול - ק"ג(2484) missing image
//product גויאבה(2655) missing image
//product צנון אדום ארוז יח(2657) missing image
//product קוֹלְרַבִּי סגול(2658) missing image
//product דובדבן ללא ריסוס - ק"ג(2685) missing image
//product ליים (ק''ג)(2703) missing image found 3261
//product משמש(2742) missing image
//product תאנים אורגני 250 גרם - תבואות(2751) missing image found 3176
//product תות עץ אורגני אוזבקי (מארז)(2767) missing image
//product ריגלה אורגני יחידה(2797) missing image
//product שזיף אדום מטלי ארוז כ600 גר(2826) missing image
//product דובדבן לבן לא אורגני - ק"ג(2850) missing image found 2851
//product אננס אורגני ליחידה(2981) missing image found 3287
//product שזיף אדום בלק אמבר(2979) missing image found 3079
//product ענבים אדומים(2982) missing image found 3324
//product ליצ'י - 4 ק"ג (ללא כימיקלים או גופרית. ללא פיקוח)(3010) missing image found 3152
//product שרי ויטני(3027) missing image found 3594
//product חציל בלאדי(3028) missing image found 3350
//product דלעת ערמונים צהובה(3030) missing image found 3355
//product מגבונים טבעיים ואקולוגיים(3040) missing image
//product חטיף פירות וסילאן - אברהמסון(3046) missing image found 3140
//product עגבניה מיוחדת(3184) missing image
//product תפוח עץ אדום(3219) missing image
//product ליצ'י - (ללא כימיקלים או גופרית. ללא פיקוח)(3222) missing image
//product מנגו היידן (אורגני ללא אישור)(3249) missing image
//product עגבנית מגי(3251) missing image
//product עגבנית תמר זוהרה(3252) missing image
//product מנגו זן שלי (העתק)(3256) missing image
//product מנגו מאיה - ללא ריסוס. קרטון 10 ק"ג(3257) missing image
//product גויאבה תותית - אריזה(3301) missing image
//product גרגר פנמה - סלסלה(3302) missing image
//product וומפי - סלסלה(3303) missing image
//product ספודילה - אריזה(3304) missing image
//product פסיפלורה - סלסלה(3306) missing image
//product מנגו מיה (אורגני ללא פיקוח) (העתק)(3326) missing image
//product מנגו טומי (אורגני ללא פיקוח)(3329) missing image
//product מנגו שלי - 10 ק"ג (אורגני ללא פיקוח)(3330) missing image
//product עגבנית תמר אשכולות אורגנית(3334) missing image
//product מנגו לילי (חקלאות בת קיימא)(3364) missing image
//product מנגו לילי - 10 ק"ג (חקלאות בת קיימא)(3365) missing image
//product שזיף מגולען אמריקאי - 500 גרם(3375) missing image
//product במיה 250 גרם(3377) missing image
//product מנגו טומי - בלה מאיה(3403) missing image
//product מנגו טומי (ללא פיקוח)(3406) missing image
//product מעדן אוכמניות אורגני - פיורדיפרוטה(3466) missing image
//product מעדן דובדבן אורגני פיורדיפרוטה(3467) missing image
//product מיץ סלק אורגני(3469) missing image
//product שזיף אדום לריאן(3472) missing image
//product אבוקדו גליל(3473) missing image found 3592
//product שמן זית כתית מעולה זן ברנע 1 ליטר (ללא פיקוח)(3477) missing image
//product שמן זית כתית מעולה זן מעלות 1 ליטר (ללא פיקוח)(3480) missing image found 3486
//product מיונז אמיתי אורגני(3499) missing image
//product מנגו שלי (ללא פיקוח)(3502) missing image
//product מנטה(3518) missing image found 3603
//product מנגו זן קנט יונתן(3540) missing image
//product מנגו פאירי ללא פיקוח(3555) missing image
//product תמר ברהי(3559) missing image
//product אבוקדו גליל לא מרוסס(3608) missing image
//product אבוקדו האס לא מרוסס(3609) missing image
//product לונגן - חצי קג(3610) missing image
//product מנגו קיט ללא פיקוח(3611) missing image
//product מנגו קיט ללא פיקוח (העתק)(3642) missing image
//product ארגז - 10 ק"ג מנגו קיט ללא פיקוח(3643) missing image
//product מנגו ללא ריסוס - אריזה(3715) missing image
//product פיטאיה ללא ריסוס - יחידה(3717) missing image
//product שעועית תאילנדית ללא ריסוס - אריזה(3719) missing image
//product תפוח עץ חרמון סטארקינג יבוא(3747) missing image
//product אפרסמון אורגני(3752) missing image
//product קולורבי(3753) missing image
//product גויבה(3754) missing image
//product אנונה(3755) missing image
//product מארז 6 ק"ג אנונה(3756) missing image
//product מארז 5 ק"ג אבוקדו האס(3757) missing image
//product מארז 5 ק"ג אבוקדו אטינגר(3759) missing image
//product מארז 8 ק"ג אגס(3767) missing image
//product מארז 5 ק"ג תפוז(3869) missing image
//product קרמבולה(3884) missing image
//product רימונים זן וונדרפול(3885) missing image
//product גרין קוקו - מי קוקוס אורגני 1 ליטר(3894) missing image
//product הייר ליבינג - תה ירוק(3904) missing image
//product ויטריז אורז עם קקאו(3907) missing image
//product ליליה - זיתים שחורים קלמנטה מגולען(3914) missing image
//product מלפפון בייבי ק"ג(3944) missing image
//product מנדרינה(3954) missing image
//product מארז 10 ק"ג מנדרינה(3956) missing image
//product מארז 8 ק"ג אפרסמון אורגני(3990) missing image
//product זוג סלק עם עלים(4054) missing image found 4056
//product חסה מסולסלת אדומה(4055) missing image
//product פטרית שמפיניון לבנה(4277) missing image
//product פטרית שמפניון חומה(4278) missing image
//product אפונת "שלג" סלסלה(4279) missing image
//product חוביזה(4280) missing image
//product לקט ירקות מיני חדש!!(4282) missing image
//product תפוח עץ סאן דאונר - יבוא(4283) missing image
//product תפוח עץ פינובה ייבוא(4284) missing image
//product שמן זית אורגני לצינו 750 מל(4285) missing image
//product שמן זית אורגני קלמטה 750 מל(4288) missing image
//product לפת (ק''ג)(4289) missing image
//product תה חורפי(4290) missing image
//product תה מרוקאי(4291) missing image
//product סלק לבן(4292) missing image
//product חליטת עלים טריים(4293) missing image
//product חמוציות בתוספת ויטמין C סולגאר(4794) missing image found 5370
//product עגבניה בלה מאיה(4875) missing image
//product שרי מוסקט (ק''ג)(4876) missing image
//product צנון (ק''ג)(4886) missing image found 5265
//product אבוקדו גולד גרין(4936) missing image
//product תפוח עץ בוניטה יבוא (ק"ג)(5126) missing image found 5127
//product שזיפים מיובשים אורגניים 200 ג(5251) missing image found 5269
//product נקטרינה ארטיק אורגני(5278) missing image
//product שזיף אדום רויאל לק"ג(5279) missing image
//product איסוף(1841) missing image
//product סלרי עלים (יחידה)(145) missing image found 2661
//product קולרבי ק"ג(161) missing image
//product קנה סוכר גבעול (ק"ג)(169) missing image
//product בורגול מלא אורגני 500 גר' תבואות(185) missing image found 2514
//product עדשים ירוקות אורגני 500 גר' תבואות(198) missing image
//product קינואה אדומה אורגנית 500 גר' תבואות(205) missing image
//product אבקת כביסה ecover רגיל(224) missing image
//product גל כביסה 4 ל Ecofriend(226) missing image found 2392
//product מסיר אבנית בתרסיס ecover(235) missing image
//product נוזל הברקה למדיח כלים 500 מ"ל Ecofriend(248) missing image found 2388
//product נוזל לניקוי כלים 1 ל Ecofriend(256) missing image
//product נוזל לניקוי כלים 1 ליטר ecover אשכוליו(263) missing image
//product נוזל לניקוי כלים 1 ל' אלוורה ולימון - ecover(266) missing image found 2565
//product תוסף הברקה למדיח כלים 500 מ"ל Ecover(277) missing image
//product בצל אדום (ק''ג)(290) missing image
//product מלפפון בייבי (מארז)(321) missing image
//product סוכר קנים(323) missing image
//product פלפל צהוב (ק''ג)(342) missing image found 2290
//product רוזמרין יחידה(351) missing image
//product בננה מיובשת טבעית 100 גרם(372) missing image
//product חמוציות מיובשות ללא סוכר ברכז תפוחים 1(379) missing image found 2612
//product משמש מיובש טבעי 100 גרם(385) missing image found 2620
//product שקד 100 גרם(406) missing image found 2623
//product דפי אורז ריבוע 2222 300 ג ASIA(444) missing image
//product ביסקידס ביסקוויט לתינוקות 150 ג(548) missing image
//product ביצי חופש 12(559) missing image
//product דבש טבעי 1 ק"ג (לא מחומם)(606) missing image
//product דפי אורז ריבוע 2222 300 ג ASIA 16 ס"מ(621) missing image
//product חיטה מלאה אורגנית 1 ק"ג תבואות(634) missing image
//product כוסות מתקלות מקנה סוכר אקולוגי 20 יח(684) missing image
//product מזון מלכות(700) missing image
//product מי קוקוס טבעי 330 מ"ל - FOCO(711) missing image
//product מקלוני שעועית 200 ג ASIA 3 ס"מ(736) missing image
//product מקלוני שעועית 200 ג ASIA 5 ס"מ(740) missing image
//product מקלוני שעועית 200 ג ASIA(773) missing image
//product מקלוני שעועית 200 ג ASIA 10 ס"מ(774) missing image
//product מרציפן 40 גרם השוקולד מעודד דובדבן(783) missing image
//product מרציפן 40 גרם השוקולד מעודד פיסטוק(790) missing image
//product סובין חיטה אורגני 500 גר מנחת הארץ(846) missing image
//product צלחת עגולה קטנה מעוטרת מקנה סוכר רעיונות(904) missing image
//product פתיתים מקמח כוסמין מלא 400 ג השדה(909) missing image found 2340
//product שוקולד ראו(950) missing image
//product נבטי קייל(1036) missing image found 2464
//product מיקס עשבים לתיבול ולחליטה(1045) missing image
//product חומוס פרוטי 500 ג(1059) missing image
//product קמח שיפון מלא - מנחת הארץ(1099) missing image found 3129
//product נבטי חרדל(1100) missing image found 2469
//product קמח שעורה - מנחת הארץ(1107) missing image found 3136
//product סל פירות(1121) missing image
//product שמן זית 100 מ"ל סינדיאנת הגליל(1134) missing image
//product שמן זית סינדיאנת הגליל 1 ליטר מילוי עצמי - זן פיקואל(1138) missing image
//product עדשים אדומות 500 גרם(858) missing image
//product קוסקוס אורגני(914) missing image
//product שעועית לבנה אורגנית 500 גר' תבואות(992) missing image
//product שעועית מנומרת אורגנית 500 גר' תבואות(996) missing image
//product שומשום שחור 400 גרם - תבואות(1025) missing image
//product חרדל גרגרים(1033) missing image
//product טחינא 5 קג(1035) missing image
//product פקאן אורגני קלוף - 100 גרם(1744) missing image found 2696
//product תפוח עץ פוגי - יבוא (ק''ג)(1837) missing image
//product תפוח עץ גולייט - יבוא (ק''ג)(1961) missing image found 2176
//product מיקס ענק(2018) missing image found 2474
//product נבטי אפונה(2020) missing image found 2472
//product פטריות שמפיניון - 200 גרם (יבוא)(2021) missing image found 3433
//product מצות כוסמין מלא רכות שמורות - 1 ק"ג(2124) missing image
//product לחם כוסמין מלא טאוברד 500 גר(2194) missing image
//product פירות קפואים אננס 300 גר' - מאמא מרי(2359) missing image found 2578
//product פירות קפואים מנגו 300 גר' - מאמא מרי(2360) missing image
//product צנוברים 100 גרם(2363) missing image found 2635
//product נקטרינה (ק''ג)(2456) missing image found 3077
//product תפוח עץ ברייבורן - יבוא (ק''ג)(2457) missing image
//product כוסברה אורגנית (יבש)(2492) missing image found 2643
//product ציפורן (טחון)(2496) missing image found 3155
//product זרעי כוסברה אורגנית (טחון)(2491) missing image found 2639
//product פפריקה מתוקה אורגנית(2495) missing image
//product משמש יבש אורגני 500 גרם(2646) missing image found 2692
//product דובדבן (מארז)(2650) missing image
//product זרעי חיטה - מנחת הארץ(2651) missing image found 3083
//product זרעי כוסמין - מנחת הארץ(2652) missing image found 2690
//product סובין כוסמין אורגני 500 גר מנחת הארץ(2653) missing image
//product שמן זית סינדיאנת הגליל 750 מ"ל מילוי עצמי - זן פיקואל(2725) missing image
//product דובדבן לבן אורגני לסלסלה(2729) missing image
//product תאנים יבשות אורגני 500 גרם(2753) missing image
//product אגוז קשיו 150 גרם(2746) missing image found 3086
//product סירופ מייפל טהור ורמונט 1 ליטר(2748) missing image
//product שמן המפ אורגני 250 מ"ל - אלפא וואלי(2749) missing image found 3616
//product חומץ תפוח אורגני לא מסונן 1 ליטר אלפא וא(2750) missing image
//product מלון אננס - יחידה (לפי ק''ג)(2763) missing image
//product תאנים אורגניות - ארוז חצי ק"ג(2765) missing image found 3071
//product ארגניה מבושם 30 מ"ל - Fancy White(2841) missing image found 3113
//product ל-ליזין 500 מ"ג - סולגאר(2845) missing image found 3115
//product פירידוקסאל-5-פוספאט - סולגאר(2834) missing image found 3117
//product תמצית אכינצאה - אלטמן(2835) missing image found 3123
//product MSM רליף - אלטמן(2836) missing image found 3119
//product אומגה 3 - סולגאר(2839) missing image found 3126
//product סופרלס - אלטמן(2847) missing image found 3111
//product ענבים ירוקים(2928) missing image found 3073
//product שרי תמר צהוב אריזה(2929) missing image
//product מנגו טומי(2978) missing image found 3075
//product שמן זית ברנע סינדיאנת הגליל 750 מל(2991) missing image
//product שמן זית קורטינה סינדיאנת הגליל 750 מל(2992) missing image
//product גרין קוקו - שמן קוקוס אורגני 300 מ"ל(3038) missing image
//product גל כביסה מרוכז 1 ליטר- Ecofriend(3036) missing image found 3107
//product משקה שיבולת שועל אורגני - פרימוורה(3043) missing image found 3142
//product ממרח זיתים קלמנטה(3044) missing image
//product שמן אתרי לבנדר 10 מ"ל - עומר הגליל(3049) missing image found 3102
//product שמן בושם לגוף 10 מ"ל - yoo papa(3050) missing image found 3069
//product שמן ציטרונלה 10 מ"ל - עומר הגליל(3052) missing image found 3067
//product שמן עץ התה 10 מ"ל - עומר הגליל(3160) missing image found 3174
//product ויניה - זרעי ענבים(3163) missing image found 3172
//product מנגו זן שלי(3250) missing image found 3263
//product לוח שנה בגינה 2018-2019(3294) missing image found 3295
//product ספוטה לבנה - אריזה(3305) missing image
//product ספירולינה אורגנית 500 מ"ג, 500 טבליות(3358) missing image found 3618
//product כלורלה 600 טבליות - ג'מוקה(3359) missing image found 3620
//product קטשופ אורגני 750 גר תבואות(3376) missing image found 5428
//product פולי קקאו גרוס נא 200 גרם(3381) missing image found 5426
//product קוקוס טחון אורגני 250 גרם - תבואות(3382) missing image found 3436
//product ויטריז לבישול(3414) missing image
//product אבקת שום(3449) missing image
//product גיגגר (טחון)(3450) missing image
//product סומק(3451) missing image
//product שמן זית כתית מעולה חצי ליטר (ללא פיקוח)(3483) missing image
//product חטיף ציפס אורגני ללא מלח(3498) missing image found 3615
//product לחם טף של טליה(3542) missing image
//product כרוב כבוש אורגני(3584) missing image
//product פקאן קלוף טבעי 500 ג(3703) missing image
//product גוגי אורגני 300 ג(3704) missing image
//product אנונה ללא ריסוס - אריזה(3714) missing image
//product פיג'ויה ללא ריסוס - אריזה(3716) missing image
//product קרמבולה ללא ריסוס - אריזה(3718) missing image
//product מארז 10 ק"ג בננה אורגנית לא מובחלת - סוטו(3763) missing image
//product צימוק אורגני 300 ג(3789) missing image
//product אגוז מלך חצאים 300 ג(3790) missing image
//product מיץ תפוחים אורגני(3895) missing image found 5418
//product קנמלה - בזיליקום טחון אורגני(3898) missing image found 5416
//product הייר ליבינג - ארל גרי אורגני(3902) missing image
//product הייר ליבינג - חליטת כורכום אורגני(3903) missing image
//product ויטריז - אורז קוקוס(3906) missing image found 5414
//product לב הטבע קרקר כוסמין(3911) missing image found 5410
//product ריויטה - פת פריכה מקמח שיפון מלא(3912) missing image found 5412
//product עדשים צהובות 500 גר תבואות(3915) missing image found 5408
//product ארגז אננס (לא אורגני)(3837) missing image
//product ממרח קוקוס אורגני 200 גר(4037) missing image found 5406
//product שעועית אדומה אורגנית 500 גר(4038) missing image
//product ניוקי כוסמין מלא 500 גר אורגני(4041) missing image found 5404
//product קדרה אפריקאית אורגנית 500 גר(4043) missing image found 5400
//product שבבי קוקוס אורגני קלוי 100גר קוקו(4047) missing image found 5398
//product חילבה+ כרום 90 כמוסות אלטמן(4107) missing image found 5396
//product אובליפיכה רול און דאודורנט 60 מ"ל ללא א(4109) missing image found 5394
//product שמן אתרי מנטה 10 מ"ל - עומר הגליל(4111) missing image found 5392
//product יעוץ ומרשם לצמחי מרפא(4222) missing image
//product משלוח(4229) missing image
//product תמצית וניל 30 מ"ל(4252) missing image
//product גבינשיו עדיקא(4253) missing image found 5390
//product שמן זית אורגני קורטינה 750 מל(4287) missing image found 5387
//product שמרים אורגניים(4358) missing image found 5385
//product ארגז קטיף(4402) missing image
//product זרעי שיבולת שועל(4403) missing image
//product בננה ציפס אורגני 150 גר תבואות(4406) missing image found 5376
//product חמוציות בתפוח 170 ג' אורגני תבואות(4407) missing image found 5378
//product חצאי עגבניות אורגני מיובש 250 ג תבואות(4411) missing image found 5374
//product חילבה סולגאר(4793) missing image found 5372
//product אורז בר טבעי 400 ג - תבואות(4918) missing image found 4923
//product אורז יסמין טבעי - 1 ק"ג (אריזה עצמית)(4919) missing image
//product נייר טואלט 48 גלילים - סופט(4942) missing image found 4943
//product תרכיז לניקוי פירות וירקות 500 מ"ל סטרי(4979) missing image
//product חילבה(5027) missing image found 5366
//product לואיזה (יבש)(5029) missing image
//product קארי(5030) missing image found 5363
//product תבלין פיצה(5031) missing image
//product קמח אורז מלא 1 ק"ג מנחת הארץ(5034) missing image found 5362
//product אברהמסון מארז חטיף טחינה וסילאן 121 גר(5131) missing image found 5359
//product אברהמסון מארז חטיף קקאו וסילאן 121 גר(5170) missing image found 5357
//product אברהמסון מוזלי פירות ושיבולת שועל מלאה(5174) missing image found 5355
//product אקופרנד נוזל כלים תפוז ללא SLS 750 מ"ל(5176) missing image found 5353
//product אקופרנד – טבליות למדיח 500 גרם(5177) missing image
//product ביוטרנטינו – מיץ אוכמניות אורגני 100% 33(5179) missing image found 5351
//product גו פיור חטיף ציפס מירקות שורש אורגניים(5183) missing image found 5349
//product גרין קוקו מי קוקוס אורגני 330 מ"ל(5185) missing image found 5347
//product גרין קוקו – חטיף קוקוס וקינמון אורגני 80(5187) missing image found 5345
//product גרין קוקו – חטיף קוקוס קקאו אורגני 80 גר(5189) missing image found 5343
//product דגש קורנפלקס ללא סוכר 500 גרם(5191) missing image found 5341
//product דה לה נונה רוטב עגבניות אורגני בזיל(5193) missing image found 5339
//product דה לה נונה רוטב עגבניות אורגני עם פלפל(5195) missing image found 5337
//product הייר ליבינג ארל גריי 45 ג(5197) missing image found 5335
//product הייר ליבינג חליטת כורכום אורגנית 30 גר(5199) missing image found 5328
//product הייר ליבינג פירמידה ארל גריי אורגני 30(5201) missing image found 5326
//product הייר ליבינג תה ירוק 50 ג(5203) missing image found 5324
//product השקד – 100% חמאת שקדים 250 גרם 250 גרם(5205) missing image found 5322
//product ויטריז לבישול – תחליף שמנת צמחי אורגני ע(5207) missing image found 5320
//product חומץ תפוחים אורגני 750 מ"ל(5209) missing image found 5318
//product טרטקס – ממרח ירקות השוק אורגני תרד וצנוב(5211) missing image found 5316
//product כרוב כבוש 670 ג(5213) missing image found 5314
//product לב הטבע קרקר אורז 140 גרם(5215) missing image found 5312
//product ליליה ממרח זיתי קלמטה 200 ג(5217) missing image found 5309
//product מאונט האגן – קפה נמס אורגני מיובש בהקפאה(5219) missing image found 5307
//product מיץ סלק אורגני נטורפוד 750 מ"ל(5221) missing image found 5305
//product מלפפון כבוש אורגני 670 ג(5223) missing image found 5303
//product מעדן דובדבנים אורגני 250 ג(5225) missing image found 5301
//product משקה אורז עם קקאו 1 ליטר(5227) missing image found 5299
//product משקה כוסמין 1 ליטר(5229) missing image found 5297
//product משקה שקדים 1 ליטר(5231) missing image found 5295
//product נטורה נובה מחית אגס אורגנית 100% פרי 2(5233) missing image found 5293
//product נטורה נובה מחית תפוח משמש אורגנית 100%(5235) missing image found 5291
//product נטורה נובה – סמוזי מנגו רביעיה 400 ג(5237) missing image found 5289
//product עגבניות אורגניות מרוסקות 700 ג(5241) missing image found 5283
//product פליסיה קופסא פנה אורז תירס אורגני 340(5243) missing image found 5277
//product פת פריכה אורגינל 250 גרם(5245) missing image found 5275
//product קנהמלה – כורכום טחון אורגני 47 ג(5247) missing image found 5273
//product קנהמלה – צילי טחון אורגני 50 ג(5249) missing image found 5271
//product שעועית אדומה אורגנית 500 ג תבואות(4045) missing image found 5402
//product תרכיז לניקוי פירות וירקות 500 מ"ל סטרי(4980) missing image found 5368
//product אברהמסון מארז חטיף פירות וסילאן 121 ג(5169) missing image
//product אברהמסון מארז חטיף אגוזים וסילאן 121 ג(5172) missing image
//product אברהמסון מארז חטיף טחינה וסילאן 121 גר(5173) missing image
//product בונומלי חליטת קמומיל 28 ג(5180) missing image
//product אקופרנד – טבליות למדיח 500 גרם(5181) missing image
//product סקיני פסטה שיבולת שועל פטוציני 540 גרם(5240) missing image found 5285
//
