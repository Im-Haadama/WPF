/**
 * Created by agla on 03/03/19.
 */

var circles = [
    "רצון פעיל",
    "כנות",
    "סליחה",
    "אהבה",
    "אחריות",
    "סבלנות",
    "איפשור",
    "חופש",
    "עוצמה",
    "תקווה",
    "נדיבות",
    "חוק המשיכה",
    "יחסי גומלין",
    "מימוש מועצם"];

var stages = [
    ["סקרנות, פתיחות וגמישות", "הקשבה פנימית", "יצירת בהירות וזיהוי רצון", "הכרה בשפע הפנימי המבקש לבוא לכדי ביטוי",
        "התמקדות בשעלה מה?", "תשוקה להגשים את השפע הפנימי", "שוויון נפש המחובה לחזון ותכלית", "העצמת החוויה של ביטחון קיומי וגיבוי נפשי",
        "כוח התנעה ודחיפה פנימי של רצון בלתי תלוי"],
    ["מחויבות כלפי העצמי המזוקק", "קבלה עצמית מתוך אהבה והעדר שפיטה", "חשיפות ומוכנות לעבור דרך כאב",
        "כנות: שקיפות, ערות, בהירות, ישירות, פתיחות", "פתיחה של שדה הראייה אל עבר אזורים שלא נוח או לא נעים להתבונן עליהם",
        "לקיחת אחריות ובחירה חופשית מודעת", "שינוי נקודת המוצא התפישתית"],
    ["כנות", "מוכנות להשתנות והשלה", "פרספקטיבה קוסמית והבנת שיעורי החיים", "סליחה ושלום פנימי", "מעבר מקורבנות לאחריות", " שחרור מטעני עבר והסכמה לנוע עם הפנים קדימה",
        "נוכחות בזמן הווה נתון המשנה את העבר ומשפיעה על העתיד", "שוויון נפש", "התחדשות", "חופש"],
    ["כנות", "אותנטיות", "שלום פנימי", "קבלה עצמית", "אהבה עצמית", "הרמוניה", "ערכיות",
        "אחדותיות", "שייכות", "אהבה ללא תנאי"],
    ["חיבור לחזון", "מהות ומשמעות", "יעוד", "תכלית", "מובהקות", "התפעמות", "מחויבות", "אחריות",
        "משמעות הנובעת ממשמעות", "התמדה", "חציית סף ושפעה על הממשות", "מעבר למדרגה חדשה"],
    ["מיקוד ברצון המבקש להתממש", "קבלה עצמית ונאמנות למקצב האישי", "שחרור תלות בתוצאה מתוך שוויון נפש",
        "שהות בזמן הווה נתון", "ביסוס סבלנות הנובעת מתודעת שפע", "בניית גשר בין הרצוי למצוי, נדבך על גבי נדבך ליצירת תשתית שלמה ויציבה",
        "תנועה רציפה להגשמת הרצון מתוך התפעות וחדוות יצירה"],
    ["איפשור", "התנסות", "מיומנות", "ביטחון", "פתיחות"],
    ["העזה לחלום", "הקשבה לנביעה פנימית", "הגדרת מסלול לגיבוי הנביעה", "נאמנות למסלול וסדרי עדיפיות ברורים", "משמעות והתמדה",
        "התרחבות וצמחיה", "חופש"],
    ["הרכנת ראש בפני האנושיות ומוכנות לבטא עוצמה", "אחריות", "מובהקות", "מחויבות לטרנספורציה", "היעדר פשרה", "התמסרות לשינוי",
        "הליכה אל הלא נודע", "מוליכות נפשית", "טרנספורמציה", "הגברה של העוצמה"],
    ["שינוי התודעה ומודעות", "שינוי פרספקטיבה ותפיסה עולם", "פיגוג תקרת זכוכית", "פתיחת מרחב חדש של הזדמנויות", "מוכנות להשתנות",
        "תקווה", "התעוררות של רצון-פעיל", "פרואקטיביות"],
    ["חיבור לעצמי המזוקק", "קבלה עצמית מתוך ערכיות", "שלמות ושלום", "חיבור לנשמה", "אחדותיות", "הזנה אנרגטית", "חוויה של שפע",
        "ראיית היש", "שביעות רצון והכרת תודה", "רצון לחלוק ולהעביר את השפע הלאה", "נדיבות", "קבלה והנחלה"],
    ["הכרה בחוק המשיכה", "העזה לחלום", "לקיחת אחריות על המרחב הפנימי", "ניהול מודע של פני הנפש", "יצירת מרחב פנימי רצוי והפיכתות להדמיה חיה",
        "מיגנט הרצוי והפיכתו למצוי"],
    ["מוכנות לטרנספורציה בתהליך שלם ומלא: נשמע, נטמיע, נגבה, נעשה", "פתיחות לחדש אמון, התמסרות ואיפשור", "מוכנות להיחנך לחוקיות חדשה למה שהיא מבטאת",
        "מחויבות לסולם הערכים לעידן החדש ויישום שלו הלכה למעשה", "ערכיות ושייכות הכרה בעוצמה האישית ובשייכות לשדה המאוחד",
        "מעבר מנפרדות לאחדותיות וקיום הערך היללהאר", "לקיחת אחריות על נתיב החיים האישי מתוך הכרה בהשפעתות על המציאות הקולקטיבית",
        "יצירת דרישה מתוך רצון-פעיל ומשאלת-לב קבלה והנחלה"],
    ["העזה לחלום", "חיבור לעצמי המזוקק", "בירור החזון והרצון", "הכרה כי הכל אפשרי", "התמסרות וחיבור לצדקת הדרך",
        "מובהקות", "חלוציות ופרו-אקטיביות", "חיבור למימון והזנה", "מימוש מועצם", "חיבור לחוקיות חדשה"]
];

async
function start(pause) {

    var circle = Math.floor(Math.random() * 14);
    var output = document.getElementById("output");

    output.innerHTML += "המעגל הנבחר... ";

    await
    sleep(500 * pause);

    output.innerHTML += circles[circle];

    await
    sleep(2000 * pause);

    output.innerHTML += "<br/>" +
        "אבן הדרך... ";

    await
    sleep(500 * pause);

    var stage = Math.floor(Math.random() * stages[circle].length);
    output.innerHTML += stages[circle][stage] + "<br/>";

    await
    sleep(1000 * pause);
    var list = "<ul>";
    for (var i = 0; i < stages[circle].length; i++) {
        list += "<li>";
        if (i === stage) list += "<b>";
        list += stages[circle][i].trim();
        if (i === stage) list += "</b>";
        list += "</li>";
    }
    list += "</ul>";
    output.innerHTML += list;

}

function sleep(ms) {
    return new Promise(resolve = > setTimeout(resolve, ms)
)
    ;
}

async
function test(count) {
    var output = document.getElementById("output");
    for (var i = 0; i < count; i++) {
        var circle = Math.floor(Math.random() * 14);

//        output.innerHTML += circle + " ";
        output.innerHTML += circles[circle] + " ";

        var stage = Math.floor(Math.random() * stages[circle].length);

        output.innerHTML += stages[circle][stage] + ";";

        await
        sleep(10);

    }
}
